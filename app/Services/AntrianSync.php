<?php

namespace App\Services;

use App\Models\Antrian;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Tarik registrasi hari ini dari appointment → tabel antrian lokal. Pasien baru
 * masuk pada tahap KLINIK (mengikuti alur asli: check-in → antrian klinik).
 * Idempoten: pasien yang sudah ada (per tanggal+appointment_no) tak digandakan,
 * dan tahap yang sedang berjalan TIDAK ditimpa.
 */
class AntrianSync
{
    /** Jeda minimal antar-sync SUKSES (detik).
     *  Halaman antrian auto-reload tiap 8 dtk; nilai ini dibuat 5 dtk agar
     *  SETIAP reload ikut menarik data — pasien yang baru registrasi muncul
     *  hampir seketika. API appointment sendiri sudah men-cache 20 dtk,
     *  jadi menarik sesering ini tidak membebani server RS. */
    private const THROTTLE_SECONDS = 5;

    /** Back-off setelah percobaan sync (sukses/gagal) sebelum coba lagi (detik).
     *  Menahan agar appointment yang lambat tak diketuk & ditunggu tiap load. */
    private const FAIL_BACKOFF_SECONDS = 20;

    public function __construct(private AppointmentApiClient $api) {}

    /**
     * Sync yang di-throttle: hanya benar-benar menarik data bila sync terakhir
     * >THROTTLE_SECONDS lalu. Dipanggil saat halaman antrian dibuka — supaya
     * pasien baru muncul otomatis tanpa membebani API bila halaman sering
     * di-refresh (mis. auto-refresh 15 dtk). Aman dipanggil sesering apa pun.
     */
    public function pullThrottled(?string $date = null): void
    {
        $date ??= today()->toDateString();
        $key = 'antrian_sync_last:'.$date;

        // Sudah sync (sukses ATAU baru saja gagal) → lewati. Ini KUNCI performa:
        // bila appointment lambat/down, kita TIDAK menghantam & menunggu tiap load
        // halaman — halaman tetap cepat memakai data lokal yang ada.
        if (Cache::get($key)) {
            return;
        }

        // Tandai "baru dicoba" LEBIH DULU (back-off pendek) supaya request lain yang
        // bersamaan / load berikutnya tak ikut menunggu bila sync ini lambat.
        Cache::put($key, now()->timestamp, self::FAIL_BACKOFF_SECONDS);

        $res = rescue(fn () => $this->pull($date), ['ok' => false], false);
        // Bila BERHASIL, perpanjang throttle ke jeda normal (sync tak perlu sering).
        if (($res['ok'] ?? false) === true) {
            Cache::put($key, now()->timestamp, self::THROTTLE_SECONDS);
        }
        // Bila gagal, biarkan back-off pendek di atas → retry setelah beberapa detik,
        // tapi tak memblok tiap load.
    }

    /**
     * Sinkron antrian untuk sebuah tanggal (default hari ini).
     *
     * @return array{ok:bool, added:int, total:int, message:?string}
     */
    public function pull(?string $date = null): array
    {
        $date ??= today()->toDateString();
        $res = $this->api->allRegistrations($date);

        if (! $res['ok']) {
            return ['ok' => false, 'added' => 0, 'total' => 0, 'message' => $res['message'] ?? 'Gagal ambil data.'];
        }

        $added = 0;
        foreach ($res['rows'] as $r) {
            $apptNo = $r['appointmentNo'] ?? null;
            if (! $apptNo) {
                continue; // butuh identitas unik per hari
            }

            $exists = Antrian::whereDate('tanggal', $date)
                ->where('appointment_no', $apptNo)
                ->exists();
            if ($exists) {
                continue; // sudah ada — jangan timpa tahap berjalan
            }

            Antrian::create([
                'tanggal'          => $date,
                'no_antrian'       => $r['ticket'] ?? null,
                'queue_no'         => (int) ($r['queue'] ?? 0),
                'pasien_nama'      => $r['patient'] ?? null,
                'pasien_nomrn'     => $r['medicalNo'] ?? null,
                'paramedic_id'     => (int) ($r['paramedicId'] ?? 0) ?: null,
                'poli_dokter_nama' => $r['doctor'] ?? null,
                'poli_nama'        => $r['unit'] ?? null,
                'room_code'        => $r['room'] ?? null,
                'room_name'        => $r['roomName'] ?? null,
                'appointment_no'   => $apptNo,
                'registration_no'  => $r['registrationNo'] ?? null,
                'tahap'            => Antrian::TAHAP_KLINIK,
                'klinik_tunggu_at' => Carbon::parse($date.' '.($r['timeRegis'] !== '—' ? $r['timeRegis'] : '00:00')),
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
}
