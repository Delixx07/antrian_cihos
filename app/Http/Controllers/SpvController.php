<?php

namespace App\Http\Controllers;

use App\Models\Antrian;
use App\Models\Clinic;
use Illuminate\Http\Request;

/**
 * Monitor SPV (role spv): pilih satu/lebih klinik, lalu lihat antrian KLINIK
 * (tahap konsultasi dokter) tiap klinik terpilih sekaligus - read-only,
 * lintas dokter. Ganti Klinik/Ganti Dokter berupa MODAL di halaman beranda
 * ini sendiri (bukan halaman terpisah) - jadi beranda() menyiapkan SEMUA
 * data yang dibutuhkan kedua modal itu sekaligus.
 */
class SpvController extends Controller
{
    /** Simpan pilihan klinik ke sesi (submit dari modal Ganti Klinik). */
    public function setKlinik(Request $request)
    {
        $data = $request->validate([
            'klinik'   => ['required', 'array', 'min:1'],
            'klinik.*' => ['string'],
        ], [
            'klinik.required' => 'Pilih minimal satu klinik.',
        ]);

        session(['spv_klinik' => $data['klinik']]);

        return redirect()->route('spv.beranda');
    }

    /**
     * Simpan pilihan dokter SEMUA klinik sekaligus (checkbox - BISA lebih dari
     * satu dokter per klinik, submit dari modal Ganti Dokter). Klinik yang
     * tak ada dokter dicentang sama sekali sengaja DIBUANG dari sesi (bukan
     * disimpan array kosong), artinya "tampilkan semua dokter" - lihat
     * pemakaiannya di beranda().
     */
    public function setDokter(Request $request)
    {
        $data = $request->validate([
            'dokter'     => ['nullable', 'array'],
            'dokter.*'   => ['nullable', 'array'],
            'dokter.*.*' => ['nullable', 'integer'],
        ]);

        $filter = collect($data['dokter'] ?? [])
            ->map(fn ($ids) => collect($ids)->filter()->map(fn ($v) => (int) $v)->values()->all())
            ->filter(fn ($ids) => count($ids) > 0)
            ->all();

        session(['spv_dokter' => $filter]);

        return redirect()->route('spv.beranda');
    }

    /**
     * Dashboard: satu tabel per klinik terpilih, live (auto-refresh halaman).
     * Kalau SPV belum pernah pilih klinik, TETAP dirender di sini (bukan
     * redirect ke halaman lain) - tinggal kosong dengan ajakan buka modal
     * Ganti Klinik.
     */
    public function beranda()
    {
        $codes = session('spv_klinik', []);

        // Daftar SEMUA klinik (utk checklist modal Ganti Klinik) - selalu
        // diambil, terlepas dari klinik mana yang sedang dipilih.
        $allClinics = Clinic::query()->orderBy('service_unit_name')->get();
        $clinics = $codes
            ? $allClinics->whereIn('service_unit_code', $codes)->values()
            : collect();

        // [kode_klinik => [paramedic_id, ...]] - filter dokter per klinik (BISA
        // lebih dari satu dokter sekaligus), disimpan di SESI (bukan cuma
        // dropdown JS) supaya TIDAK ikut ke-reset tiap auto-refresh halaman
        // (setiap 20 detik, lihat safeRefresh() di view).
        //
        // Dinormalkan ke array di sini: sesi yang MASIH TERBUKA dari sebelum
        // fitur ini multi-pilih (format lama: satu ID langsung, bukan array)
        // harus tetap jalan tanpa error - (array) $v membungkus int tunggal
        // jadi array 1 elemen, jadi diperlakukan sebagai "1 dokter terpilih".
        $dokterFilter = collect(session('spv_dokter', []))
            ->map(fn ($v) => is_array($v) ? $v : (array) $v)
            ->all();

        // Pasien NYATA (sudah check-in) yang masih di tahap KLINIK, dikelompokkan
        // per klinik. Status panggil dibaca lewat Antrian::isDipanggil('klinik').
        // BELUM difilter dokter - dipakai juga utk menyusun pilihan dokter di
        // modal Ganti Dokter (harus tetap menampilkan SEMUA dokter, bukan cuma
        // yang lolos filter saat ini).
        $allRows = $codes
            ? Antrian::today()->nyata()
                ->whereIn('poli_kode', $codes)
                ->where('tahap', Antrian::TAHAP_KLINIK)
                ->orderByRaw('LENGTH(no_antrian), no_antrian')
                ->get([
                    'poli_kode', 'poli_nama', 'no_antrian', 'pasien_nama',
                    'paramedic_id', 'poli_dokter_nama', 'room_code', 'klinik_panggil_at', 'klinik_selesai_at',
                ])
                ->groupBy('poli_kode')
            : collect();

        // Baris yang BENAR-BENAR ditampilkan - difilter per klinik bila SPV
        // sudah memilih 1+ dokter utk klinik itu; kalau tidak memilih satupun,
        // tampilkan semua dokter.
        $rows = $allRows->map(function ($list, $code) use ($dokterFilter) {
            $ids = $dokterFilter[$code] ?? [];

            return $ids ? $list->whereIn('paramedic_id', $ids)->values() : $list;
        });

        // Pilihan dokter per klinik utk modal Ganti Dokter.
        $doctorsByClinic = $allRows->map(fn ($list) => $list
            ->filter(fn ($a) => $a->paramedic_id)
            ->unique('paramedic_id')
            ->sortBy('poli_dokter_nama')
            ->values());

        return view('spv.beranda', [
            'clinics'         => $clinics,
            'allClinics'      => $allClinics,
            'selectedCodes'   => $codes,
            'rows'            => $rows,
            'allRows'         => $allRows,
            'dokterFilter'    => $dokterFilter,
            'doctorsByClinic' => $doctorsByClinic,
        ]);
    }
}
