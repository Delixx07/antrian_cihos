<?php

namespace App\Http\Controllers;

use App\Models\Antrian;
use App\Models\Doctor;
use App\Services\AntrianSync;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Antrian klinik (per dokter). Dokter (role Klinik) MEMANGGIL pasiennya dari
 * tabel `antrian` lokal (tahap klinik), lalu menyelesaikan → dorong ke KASIR
 * (administrasi). Admin/SPV melihat gambaran umum (read-only dari tabel lokal).
 */
class QueueController extends Controller
{
    private const TAHAP = Antrian::TAHAP_KLINIK;

    public function index(Request $request, AntrianSync $sync)
    {
        $role    = (string) session('role', '');
        $isAdmin = in_array($role, ['administrator', 'spv'], true);
        $date    = $request->query('date') ?: now()->toDateString();

        // Auto-sync ringan saat halaman dibuka — membaca tabel appointment di
        // MySQL lokal (bukan HTTP), jadi murah & tidak mungkin timeout.
        $sync->pullThrottled($date);

        // ── Admin/SPV: gambaran umum read-only (boleh pilih dokter) ──
        if ($isAdmin) {
            $paramedicId = (int) $request->query('paramedic_id', 0);
            $doctor  = $paramedicId ? Doctor::find($paramedicId) : null;

            // Dibaca dari tabel antrian LOKAL (hasil sync), bukan HTTP ke
            // aplikasi appointment. Pemanggilan API di sini pernah membuat
            // halaman menggantung sampai timeout (cURL error 28) sehingga
            // petugas terlempar ke halaman login.
            $rows = $paramedicId
                ? Antrian::whereDate('tanggal', $date)
                    ->where('paramedic_id', $paramedicId)
                    ->orderBy('queue_no')
                    ->get()
                    ->map(fn ($a) => [
                        'ticket'         => $a->no_antrian,
                        'queue'          => $a->queue_no,
                        'patient'        => $a->pasien_nama,
                        'medicalNo'      => $a->pasien_nomrn,
                        'doctor'         => $a->poli_dokter_nama,
                        'unit'           => $a->poli_nama,
                        'room'           => $a->room_code,
                        'appointmentNo'  => $a->appointment_no,
                        'registrationNo' => $a->registration_no,
                        'timeRegis'      => optional($a->klinik_tunggu_at)->format('H:i') ?: '—',
                        'status'         => $a->tahap,
                    ])->all()
                : [];

            $result  = ['ok' => true, 'rows' => $rows, 'total' => count($rows), 'message' => null];
            $doctors = Doctor::orderBy('paramedic_name')->get(['paramedic_id', 'paramedic_name', 'paramedic_code']);

            return view('queue.admin', [
                'paramedicId' => $paramedicId,
                'doctor'      => $doctor,
                'doctors'     => $doctors,
                'date'        => $date,
                'rows'        => $result['rows'],
                'total'       => $result['total'],
                'apiOk'       => $result['ok'],
                'apiMessage'  => $result['message'] ?? null,
            ]);
        }

        // ── Dokter (role Klinik): konsol panggil dari tabel antrian lokal ──
        // (data pasien baru sudah ditarik oleh pullThrottled di atas)
        $paramedicId = (int) session('paramedic_id', 0);

        return view('queue.klinik', $this->klinikData($paramedicId));
    }

    /** Data konsol klinik untuk seorang dokter. */
    private function klinikData(int $paramedicId): array
    {
        $doctor = $paramedicId ? Doctor::find($paramedicId) : null;

        // Pasien milik dokter ini hari ini (tahap klinik = belum selesai konsultasi,
        // ATAU sudah selesai = riwayat, tetap milik dokter ini).
        $ownToday = fn () => Antrian::today()->where('paramedic_id', $paramedicId);

        // Menunggu: di tahap klinik, belum dipanggil. Urut ASCENDING nomor antrian.
        $menunggu  = $ownToday()->where('tahap', self::TAHAP)->nyata()
            ->whereNull('klinik_panggil_at')
            ->orderByRaw('LENGTH(no_antrian), no_antrian')->get();

        // Sudah dipanggil, konsultasi belum diselesaikan (aktif di ruang).
        $dipanggil = $ownToday()->where('tahap', self::TAHAP)
            ->whereNotNull('klinik_panggil_at')->whereNull('klinik_selesai_at')
            ->orderByDesc('klinik_panggil_at')->get();

        // Riwayat: sudah diselesaikan dokter (klinik_selesai_at terisi) — bisa
        // dipanggil ulang bila pasien perlu balik. Terbaru di atas.
        $selesai = $ownToday()->whereNotNull('klinik_selesai_at')
            ->orderByDesc('klinik_selesai_at')->get();

        $current   = $dipanggil->first();

        return [
            'paramedicId' => $paramedicId,
            'doctor'      => $doctor,
            'menunggu'    => $menunggu,
            'dipanggil'   => $dipanggil,
            'selesai'     => $selesai,
            'current'     => $current,
            'sisa'        => $menunggu->count(),
            'berikutnya'  => $menunggu->first(),
        ];
    }

    /**
     * TARIK ULANG pasien dari appointment SEKARANG (tombol manual).
     *
     * Sync otomatis berjalan saat halaman dibuka, tapi di-throttle beberapa
     * detik. Bila pasien baru saja registrasi dan belum muncul, tombol ini
     * memaksa tarik data tanpa menunggu throttle.
     */
    public function syncNow(Request $request, AntrianSync $sync)
    {
        $date = $request->query('date') ?: now()->toDateString();

        // Buang penanda throttle supaya benar-benar menarik data, bukan dilewati.
        Cache::forget('antrian_sync_last:'.$date);

        $r = $sync->pull($date);

        if (! ($r['ok'] ?? false)) {
            return back()->with('error', 'Gagal menarik data: '.($r['message'] ?? 'tidak diketahui'));
        }

        $added = (int) ($r['added'] ?? 0);

        return back()->with('ok', $added > 0
            ? "Berhasil menarik {$added} pasien baru dari appointment."
            : 'Data sudah terbaru — tidak ada pasien baru.');
    }

    /** Panggil pasien berikutnya (dokter) — ambil nomor terkecil yg menunggu. */
    public function panggil(Request $request)
    {
        $paramedicId = (int) session('paramedic_id', 0);

        $next = Antrian::today()->where('tahap', self::TAHAP)->nyata()
            ->where('paramedic_id', $paramedicId)
            ->whereNull('klinik_panggil_at')
            ->orderByRaw('LENGTH(no_antrian), no_antrian')->first();

        if (! $next) {
            return back()->with('error', 'Tidak ada antrian menunggu.');
        }

        $next->panggil(self::TAHAP, $request->input('room') ?: ($next->room_name ?: 'Ruang Praktik'));

        return back()
            ->with('ok', 'Memanggil '.$next->no_antrian)
            ->with('say', $this->saySession($next, 'klinik'));
    }

    /** Panggil pasien TERTENTU dari barisnya (dokter). */
    public function panggilSatu(Request $request, Antrian $antrian)
    {
        $paramedicId = (int) session('paramedic_id', 0);
        if ($antrian->paramedic_id !== $paramedicId || $antrian->tahap !== self::TAHAP) {
            return back()->with('error', 'Pasien bukan di antrian klinik Anda.');
        }

        $antrian->panggil(self::TAHAP, $antrian->room_name ?: 'Ruang Praktik');

        return back()
            ->with('ok', 'Memanggil '.$antrian->no_antrian)
            ->with('say', $this->saySession($antrian, 'klinik'));
    }

    /**
     * Panggil ulang (recall). Berlaku untuk pasien yang sedang di ruang MAUPUN
     * yang sudah selesai (riwayat) — bila pasien perlu kembali ke dokter.
     */
    public function ulang(Antrian $antrian)
    {
        $paramedicId = (int) session('paramedic_id', 0);
        if ($antrian->paramedic_id !== $paramedicId) {
            return back()->with('error', 'Pasien bukan pasien Anda.');
        }

        // Bila pasien sudah diselesaikan (transfer ke kasir/farmasi), tarik kembali
        // ke tahap klinik. Reset PENUH jejak kasir/farmasi/resep supaya pasien tak
        // tampil dobel di display farmasi/kasir (mereka difilter by status_resep).
        if ($antrian->klinik_selesai_at !== null || $antrian->tahap !== self::TAHAP) {
            $antrian->tarikKeKlinik();
        }

        $antrian->ulang(self::TAHAP);

        return back()
            ->with('ok', 'Panggilan diulang: '.$antrian->no_antrian)
            ->with('say', $this->saySession($antrian, 'klinik'));
    }

    /**
     * Selesai → dokter memilih status resep, pasien transfer ke KASIR:
     *   non_resep | racik | non_racik
     */
    public function selesai(Request $request, Antrian $antrian)
    {
        $status = $request->input('status_resep');
        if (! in_array($status, [Antrian::RESEP_NON, Antrian::RESEP_RACIK, Antrian::RESEP_NON_RACIK], true)) {
            return back()->with('error', 'Pilih status resep dulu.');
        }

        $antrian->transferKeKasir($status);

        return back()->with('ok', 'Antrian '.$antrian->no_antrian.' ('.$antrian->statusLabel().') diteruskan ke Administrasi.');
    }
}
