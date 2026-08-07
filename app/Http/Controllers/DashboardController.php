<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // Dokter (role Klinik) tak butuh dashboard kartu — halaman utamanya adalah
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

        // Dokter aktif (dari mirror appointment, read-only). Aman bila DB mati.
        $activeDoctors = rescue(
            fn () => Doctor::where('is_available', 1)->orderBy('paramedic_name')->limit(30)->get(),
            collect(),
            false
        );

        // Kartu antrian & grafik pengunjung: placeholder sampai modul antrian
        // (tabel antrian lokal) dibuat. Struktur siap, tinggal ganti sumbernya.
        $queues = [
            ['label' => 'Antrian Admisi',  'value' => 0, 'c' => '#16a34a', 'bg' => '#eafaf0', 'ic' => 'user'],
            ['label' => 'Antrian Klinik',  'value' => 0, 'c' => '#2563eb', 'bg' => '#eff4ff', 'ic' => 'activity'],
            ['label' => 'Antrian Kasir',   'value' => 0, 'c' => '#d97706', 'bg' => '#fff5e6', 'ic' => 'chart'],
            ['label' => 'Antrian Farmasi', 'value' => 0, 'c' => '#7c3aed', 'bg' => '#f3edff', 'ic' => 'pill'],
        ];

        // 10 hari terakhir (label tanggal, nilai 0 dulu).
        $visitors = [];
        for ($i = 9; $i >= 0; $i--) {
            $visitors[] = ['date' => Carbon::today()->subDays($i)->format('Y-m-d'), 'value' => 0];
        }

        return view('dashboard', [
            'queues'        => $queues,
            'visitors'      => $visitors,
            'activeDoctors' => $activeDoctors,
        ]);
    }
}
