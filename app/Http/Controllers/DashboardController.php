<?php

namespace App\Http\Controllers;

use App\Models\Antrian;
use App\Models\Doctor;
use App\Services\AntrianSync;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request, AntrianSync $sync)
    {
        // Dashboard cuma BACA tabel antrian lokal - tanpa ini, kartu bisa
        // tampil 0 walau pasien sudah check-in di appointment, kalau kebetulan
        // belum ada yang buka halaman Klinik (satu-satunya trigger manual) dan
        // scheduled task antrian:sync (tiap 1 menit) belum sempat jalan.
        // Murah & di-throttle 3 detik (lihat AntrianSync::pullThrottled) -
        // aman dipanggil di tiap load Dashboard.
        $sync->pullThrottled();

        // Dokter (role Klinik) tak butuh dashboard kartu - halaman utamanya adalah
        // ANTRIAN miliknya. Arahkan langsung ke sana.
        if (session('role') === \App\Models\AntrianAccess::ROLE_KLINIK) {
            return redirect()->route('queue.index');
        }

        // Farmasi → beranda counter (pilih counter dulu bila belum).
        if (session('role') === 'farmasi') {
            return redirect()->route(session('farmasi_counter') ? 'farmasi.beranda' : 'farmasi.pilih-counter');
        }

        // Kasir → beranda counter kasir.
        if (in_array(session('role'), ['kasir_administrasi', 'kasir_farmasi'], true)) {
            return redirect()->route(session('kasir_counter') ? 'kasir.beranda' : 'kasir.pilih-counter');
        }

        // SPV → monitor antrian per klinik. Kalau belum pernah pilih klinik,
        // beranda() sendiri yang menampilkan prompt "Pilih Klinik" (modal),
        // bukan redirect ke halaman terpisah.
        if (session('role') === 'spv') {
            return redirect()->route('spv.beranda');
        }

        // Registrasi → beranda counter registrasi (panggil tiket RG dari kiosk).
        if (session('role') === 'registrasi') {
            return redirect()->route(session('registrasi_counter') ? 'registrasi.beranda' : 'registrasi.pilih-counter');
        }

        // Dokter aktif (dari mirror appointment, read-only). Aman bila DB mati.
        $activeDoctors = rescue(
            fn () => Doctor::where('is_available', 1)->orderBy('paramedic_name')->limit(30)->get(),
            collect(),
            false
        );

        // Kartu antrian: dihitung dari tabel antrian lokal. Pasien BOOKING
        // (belum check-in) tidak ikut dihitung - lihat scope nyata().
        // Dibungkus rescue() agar dashboard tetap tampil bila DB bermasalah.
        $counts = rescue(fn () => $this->queueCounts(), [
            'total' => 0, 'klinik' => 0, 'kasir' => 0, 'farmasi' => 0,
        ], false);

        $queues = [
            ['label' => 'Total Pasien',    'value' => $counts['total'],   'c' => '#16a34a', 'bg' => '#eafaf0', 'ic' => 'user'],
            ['label' => 'Antrian Klinik',  'value' => $counts['klinik'],  'c' => '#2563eb', 'bg' => '#eff4ff', 'ic' => 'activity'],
            ['label' => 'Antrian Kasir',   'value' => $counts['kasir'],   'c' => '#d97706', 'bg' => '#fff5e6', 'ic' => 'chart'],
            ['label' => 'Antrian Farmasi', 'value' => $counts['farmasi'], 'c' => '#7c3aed', 'bg' => '#f3edff', 'ic' => 'pill'],
        ];

        // visitorTrend() selalu mengembalikan 10 elemen, jadi array kosong
        // hanya terjadi bila rescue() menangkap error → pakai deret nol.
        $visitors = rescue(fn () => $this->visitorTrend(), [], false)
            ?: $this->emptyTrend();

        return view('dashboard', [
            'queues'        => $queues,
            'visitors'      => $visitors,
            'activeDoctors' => $activeDoctors,
        ]);
    }

    /**
     * Jumlah HARI INI: total pasien + yang masih menunggu per tahap.
     *
     * Selalu kembalikan KEEMPAT kunci sekaligus (jangan dibangun bertahap) -
     * pemanggilnya membungkus ini dengan rescue() yang bersifat semua-atau-
     * tidak sama sekali, jadi kunci yang hilang akan jadi error di view.
     */
    private function queueCounts(): array
    {
        return [
            'total'   => Antrian::query()->today()->nyata()->count(),
            'klinik'  => Antrian::query()->today()->nyata()->menunggu(Antrian::TAHAP_KLINIK)->count(),
            'kasir'   => Antrian::query()->today()->nyata()->menunggu(Antrian::TAHAP_KASIR)->count(),
            // Farmasi bekerja berdasar STATUS RESEP, bukan tahap: pasien
            // tetap di tahap kasir sepanjang alur (lihat scopeFarmasiQueue).
            'farmasi' => Antrian::query()->today()->nyata()
                ->whereIn('status_resep', [Antrian::RESEP_RACIK, Antrian::RESEP_NON_RACIK])
                ->where('resep_clear', false)
                ->count(),
        ];
    }

    /**
     * Grafik 10 hari terakhir - jumlah pasien nyata per tanggal.
     *
     * Satu query yang di-groupBy, lalu dipetakan ke deret tanggal lengkap
     * supaya hari tanpa pasien tetap muncul sebagai 0 (bukan bolong).
     */
    private function visitorTrend(): array
    {
        $start = Carbon::today()->subDays(9);

        $rows = Antrian::query()
            ->nyata()
            ->whereBetween('tanggal', [$start->toDateString(), Carbon::today()->toDateString()])
            ->groupBy('tanggal')
            ->pluck(DB::raw('COUNT(*)'), 'tanggal');

        return collect($this->emptyTrend())
            ->map(fn ($d) => ['date' => $d['date'], 'value' => (int) ($rows[$d['date']] ?? 0)])
            ->all();
    }

    /** Deret 10 hari terakhir bernilai 0 - dasar grafik & fallback bila DB mati. */
    private function emptyTrend(): array
    {
        $out = [];
        for ($i = 9; $i >= 0; $i--) {
            $out[] = ['date' => Carbon::today()->subDays($i)->format('Y-m-d'), 'value' => 0];
        }

        return $out;
    }
}
