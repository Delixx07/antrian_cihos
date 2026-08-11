<?php

namespace App\Services;

use App\Models\Antrian;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Tarik registrasi hari ini → tabel antrian lokal.
 *
 * SUMBER DATA: tabel `appointments` di database appointment (MySQL lokal).
 * Semua registrasi — lewat aplikasi appointment, TapTalk/WA bot, maupun
 * Kiosk — PASTI melewati tabel ini sebelum dikirim ke MEDINFRAS. Membaca
 * langsung dari sini punya beberapa keuntungan penting:
 *
 *   1. NOMOR TIKET BENAR. `RegistrationTicketNo` di tabel ini sudah berisi
 *      nomor SLOT (mis. FD015), bukan QueueNo mentah MEDINFRAS (FD001).
 *   2. SEKETIKA. Begitu pasien registrasi, datanya langsung terbaca —
 *      tidak menunggu cache/polling API.
 *   3. TIDAK BISA TIMEOUT. Sebelumnya memakai HTTP ke aplikasi appointment
 *      yang query-nya berat (view vRegistration SQL Server) dan sering
 *      `cURL error 28`, membuat halaman antrian menggantung lalu petugas
 *      terlempar ke halaman login.
 *
 * Idempoten: pasien yang sudah ada (per tanggal + appointment_no) tidak
 * digandakan, dan tahap yang sedang berjalan TIDAK ditimpa.
 */
class AntrianSync
{
    /** Jeda minimal antar-sync SUKSES (detik).
     *  Membaca MySQL lokal sangat murah, jadi jeda dibuat pendek supaya
     *  pasien yang baru registrasi muncul hampir seketika. */
    private const THROTTLE_SECONDS = 3;

    /** Back-off setelah percobaan sync GAGAL sebelum dicoba lagi (detik). */
    private const FAIL_BACKOFF_SECONDS = 10;

    /**
     * Sync yang di-throttle — dipanggil saat halaman antrian dibuka supaya
     * pasien baru muncul otomatis. Aman dipanggil sesering apa pun.
     */
    public function pullThrottled(?string $date = null): void
    {
        $date ??= today()->toDateString();
        $key = 'antrian_sync_last:'.$date;

        if (Cache::get($key)) {
            return;
        }

        // Tandai lebih dulu supaya request lain yang bersamaan tidak ikut
        // menarik data yang sama.
        Cache::put($key, now()->timestamp, self::FAIL_BACKOFF_SECONDS);

        $res = rescue(fn () => $this->pull($date), ['ok' => false], false);

        if (($res['ok'] ?? false) === true) {
            Cache::put($key, now()->timestamp, self::THROTTLE_SECONDS);
        }
    }

    /**
     * Sinkron antrian untuk sebuah tanggal (default hari ini).
     *
     * @return array{ok:bool, added:int, total:int, message:?string}
     */
    public function pull(?string $date = null): array
    {
        $date ??= today()->toDateString();

        try {
            $rows = $this->registrations($date);
        } catch (\Throwable $e) {
            return [
                'ok'      => false,
                'added'   => 0,
                'total'   => 0,
                'message' => 'Gagal membaca data appointment: '.$e->getMessage(),
            ];
        }

        // Ambil sekali saja: baris yang SUDAH ada hari ini (beserta status
        // booking-nya), supaya tidak query berulang di dalam loop.
        $existing = Antrian::whereDate('tanggal', $date)
            ->whereNotNull('appointment_no')
            ->get(['id', 'appointment_no', 'is_booking'])
            ->keyBy('appointment_no');

        // Nama dokter & poli dari master (untuk ditampilkan di layar).
        $doctors = $this->doctorInfo($rows->pluck('ParamedicID')->filter()->unique()->all());

        $added = $promoted = 0;
        foreach ($rows as $r) {
            $apptNo    = trim((string) $r->AppointmentNo);
            $isBooking = trim((string) ($r->RegistrationNo ?? '')) === '';

            if ($apptNo === '') {
                continue;   // butuh identitas unik per hari
            }

            // Sudah ada di antrian.
            if ($prev = $existing->get($apptNo)) {
                // Pasien BOOKING yang baru saja check-in → naikkan statusnya
                // menjadi antrian nyata, lengkapi nomor tiket & registrasi.
                // Tahap yang sedang berjalan TIDAK disentuh.
                if ($prev->is_booking && ! $isBooking) {
                    Antrian::whereKey($prev->id)->update([
                        'is_booking'       => false,
                        'no_antrian'       => $r->RegistrationTicketNo ?: null,
                        'registration_no'  => $r->RegistrationNo ?: null,
                        'klinik_tunggu_at' => $this->waitingAt($date, $r),
                        'updated_at'       => now(),
                    ]);
                    $promoted++;
                }

                continue;
            }

            $pid = (int) $r->ParamedicID ?: null;
            $doc = $doctors[$pid] ?? null;

            Antrian::create([
                'is_booking'       => $isBooking,
                'tanggal'          => $date,
                // Booking belum punya nomor tiket resmi. Diberi nomor
                // PERKIRAAN dari prefix ruang + slot supaya urutannya di layar
                // sudah benar sejak awal; diganti nomor asli saat check-in.
                'no_antrian'       => $r->RegistrationTicketNo
                                        ?: $this->tentativeTicket($r, $doc['room'] ?? null),
                'queue_no'         => (int) ($r->QueueNo ?? 0),
                'pasien_nama'      => $r->PatientName ?: null,
                'pasien_nomrn'     => $r->MedicalNo ?: null,
                'pasien_jk'        => $this->gender($r->Gender ?? null),
                'paramedic_id'     => $pid,
                'poli_dokter_nama' => $doc['name'] ?? null,
                'poli_nama'        => $doc['unit'] ?? null,
                'poli_kode'        => $r->ServiceUnitCode ?: null,
                'room_code'        => $r->RoomCode ?: ($doc['room'] ?? null),
                'appointment_no'   => $apptNo,
                'registration_no'  => $r->RegistrationNo ?: null,
                'tahap'            => Antrian::TAHAP_KLINIK,
                'klinik_tunggu_at' => $this->waitingAt($date, $r),
            ]);
            $added++;
        }

        return [
            'ok'      => true,
            'added'   => $added,
            'total'   => Antrian::whereDate('tanggal', $date)->count(),
            'message' => null,
        ];
    }

    /**
     * Appointment hari ini dari tabel appointment — termasuk yang BELUM
     * registrasi (booking), kecuali yang dibatalkan (IsVoided).
     *
     * Booking ikut ditarik supaya urutan antrian terlihat utuh sejak awal:
     * pasien nomor 32 tampil SAMAR sejak pagi, jadi ketika ia check-in ia
     * tidak "menyelip" di antara 31 dan 33 yang sudah tampil lebih dulu.
     */
    private function registrations(string $date)
    {
        return DB::connection('appointment')->table('appointments')
            ->whereDate('StartDate', $date)
            ->where(fn ($q) => $q->whereNull('IsVoided')->orWhere('IsVoided', 0))
            ->orderBy('QueueNo')
            ->get([
                'AppointmentNo', 'RegistrationNo', 'RegistrationTicketNo',
                'QueueNo', 'PatientName', 'MedicalNo', 'Gender',
                'ParamedicID', 'ServiceUnitCode', 'RoomCode',
                'StartTime', 'RegisteredAt',
            ]);
    }

    /**
     * Nomor tiket PERKIRAAN untuk pasien yang masih booking.
     *
     * Layar mengurutkan berdasarkan no_antrian, jadi booking perlu nomor
     * berformat sama (prefix ruang + slot) agar posisinya sudah tepat sejak
     * awal. Nomor ini diganti dengan nomor resmi begitu pasien check-in.
     */
    private function tentativeTicket($r, ?string $fallbackRoom): ?string
    {
        $slot = (int) ($r->QueueNo ?? 0);
        if ($slot < 1) {
            return null;
        }

        $room = trim((string) ($r->RoomCode ?: $fallbackRoom));
        if ($room === '') {
            return null;
        }

        $prefix = rescue(
            fn () => DB::connection('master')->table('room_ticket_prefixes')
                ->where('room_code', $room)->value('prefix'),
            null,
            false
        );

        return $prefix ? $prefix.str_pad((string) $slot, 3, '0', STR_PAD_LEFT) : null;
    }

    /**
     * Jenis kelamin → kode 1 huruf.
     *
     * Tabel appointment menyimpan teks penuh ("Laki-laki"/"Perempuan"),
     * sedangkan kolom antrian.pasien_jk hanya varchar(5) — tanpa konversi
     * ini penyimpanan gagal dengan "Data too long".
     */
    private function gender(?string $g): ?string
    {
        $g = trim((string) $g);
        if ($g === '') {
            return null;
        }

        return str_starts_with(mb_strtoupper($g), 'L') ? 'L' : 'P';
    }

    /** Nama dokter, poli, & ruang dari master — dipetakan per paramedic_id. */
    private function doctorInfo(array $ids): array
    {
        if (! $ids) {
            return [];
        }

        $out = [];

        rescue(function () use ($ids, &$out) {
            $docs = DB::connection('master')->table('doctors')
                ->whereIn('paramedic_id', $ids)
                ->pluck('paramedic_name', 'paramedic_id');

            // Poli & ruang diambil dari jadwal hari ini bila ada.
            $sched = DB::connection('master')->table('doctor_schedules')
                ->whereIn('paramedic_id', $ids)
                ->where('day_number', (int) now()->isoWeekday())
                ->get(['paramedic_id', 'service_unit_name', 'room_code'])
                ->keyBy('paramedic_id');

            foreach ($ids as $id) {
                $out[$id] = [
                    'name' => $docs[$id] ?? null,
                    'unit' => $sched[$id]->service_unit_name ?? null,
                    'room' => $sched[$id]->room_code ?? null,
                ];
            }
        }, null, false);

        return $out;
    }

    /**
     * Waktu pasien mulai menunggu. Pakai waktu registrasi bila ada
     * (itu saat pasien benar-benar check-in), selain itu jam appointment.
     */
    private function waitingAt(string $date, $r): Carbon
    {
        if (! empty($r->RegisteredAt)) {
            return rescue(fn () => Carbon::parse($r->RegisteredAt), Carbon::parse($date), false);
        }

        $time = trim((string) ($r->StartTime ?? '')) ?: '00:00';

        return rescue(fn () => Carbon::parse($date.' '.$time), Carbon::parse($date), false);
    }
}
