<?php

namespace App\Http\Controllers;

use App\Models\Antrian;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Counter Farmasi. Saat login (role farmasi) → pilih jenis (Racik/Non-Racik) +
 * nomor counter (disimpan di sesi). Lalu beranda: list antrian farmasi, panggil
 * nomor (Ulang/Lanjut/Selesai). Alur mengikuti sistem lama.
 */
class FarmasiController extends Controller
{
    /** Halaman "Silahkan Pilih Counter Pemanggil". */
    public function pilihCounter()
    {
        return view('farmasi.pilih-counter', [
            'jenis'   => session('farmasi_jenis'),
            'counter' => session('farmasi_counter'),
        ]);
    }

    /** Simpan pilihan counter ke sesi. */
    public function setCounter(Request $request)
    {
        $data = $request->validate([
            'jenis'   => ['required', Rule::in([Antrian::FARMASI_RACIK, Antrian::FARMASI_NON_RACIK])],
            'counter' => ['required', 'string', 'max:50'],
        ]);

        session([
            'farmasi_jenis'   => $data['jenis'],
            'farmasi_counter' => $data['counter'],
        ]);

        return redirect()->route('farmasi.beranda');
    }

    /** Beranda counter farmasi. */
    public function beranda(Request $request)
    {
        if (! session('farmasi_counter')) {
            return redirect()->route('farmasi.pilih-counter');
        }

        $jenis   = session('farmasi_jenis');
        $counter = session('farmasi_counter');

        // Farmasi HANYA melihat pasien ber-resep sesuai jenis counter ini
        // (racik / non_racik) yang BELUM clear — berdasar status resep, bukan
        // tahap (pasien tetap di tahap kasir sepanjang alur).
        $q = fn () => Antrian::today()->farmasiQueue($jenis);

        // Menunggu = belum dipanggil.
        $menunggu = $q()->whereNull('farmasi_panggil_at')->orderBy('farmasi_tunggu_at')->get();
        // Dipanggil = sudah dipanggil, belum selesai (masih diproses).
        $dipanggil = $q()->whereNotNull('farmasi_panggil_at')->whereNull('farmasi_selesai_at')
            ->orderByDesc('farmasi_panggil_at')->get();

        // Yang sedang aktif dipanggil di COUNTER ini.
        $current = $q()->where('counter', $counter)
            ->whereNotNull('farmasi_panggil_at')->whereNull('farmasi_selesai_at')
            ->orderByDesc('farmasi_panggil_at')->first();

        // Riwayat: resep yang SUDAH selesai diproses farmasi hari ini (jenis ini).
        // Bisa dipanggil ulang bila pasien perlu balik ke farmasi.
        $selesai = Antrian::today()
            ->where('farmasi_jenis', $jenis)
            ->whereNotNull('farmasi_selesai_at')
            ->orderByDesc('farmasi_selesai_at')->get();

        $sisa = $menunggu->count();
        $berikutnya = $menunggu->first();

        return view('farmasi.beranda', [
            'jenis'      => $jenis,
            'counter'    => $counter,
            'menunggu'   => $menunggu,
            'dipanggil'  => $dipanggil,
            'current'    => $current,
            'selesai'    => $selesai,
            'sisa'       => $sisa,
            'berikutnya' => $berikutnya,
        ]);
    }

    /** Panggil nomor berikutnya. */
    public function panggilBerikutnya(Request $request)
    {
        $counter = session('farmasi_counter');
        $jenis   = session('farmasi_jenis');
        if (! $counter) {
            return redirect()->route('farmasi.pilih-counter');
        }

        $next = Antrian::today()->farmasiQueue($jenis)
            ->whereNull('farmasi_panggil_at')
            ->orderBy('farmasi_tunggu_at')
            ->first();

        if (! $next) {
            return back()->with('error', 'Tidak ada antrian menunggu.');
        }

        $next->panggil(Antrian::TAHAP_FARMASI, $counter);

        return back()
            ->with('ok', 'Memanggil '.$next->no_antrian)
            ->with('say', $this->saySession($next, 'farmasi', $counter));
    }

    /** Panggil pasien TERTENTU (dari tombol di barisnya). */
    public function panggil(Antrian $antrian)
    {
        $counter = session('farmasi_counter');
        $jenis   = session('farmasi_jenis');
        if (! $counter) {
            return redirect()->route('farmasi.pilih-counter');
        }

        // Hanya pasien ber-resep jenis ini yang BELUM clear yang boleh dipanggil.
        if ($antrian->farmasi_jenis !== $jenis || ! $antrian->adaResep() || $antrian->resep_clear) {
            return back()->with('error', $antrian->no_antrian.' bukan antrian farmasi '.$jenis.' ini.');
        }

        $antrian->panggil(Antrian::TAHAP_FARMASI, $counter);

        return back()
            ->with('ok', 'Memanggil '.$antrian->no_antrian)
            ->with('say', $this->saySession($antrian, 'farmasi', $counter));
    }

    /** Ulangi panggilan nomor yang sedang aktif. */
    public function ulang(Antrian $antrian)
    {
        $antrian->ulang(Antrian::TAHAP_FARMASI);

        return back()
            ->with('ok', 'Panggilan diulang: '.$antrian->no_antrian)
            ->with('say', $this->saySession($antrian, 'farmasi'));
    }

    /**
     * Panggil ULANG resep dari RIWAYAT (sudah selesai) — buka kembali ke farmasi
     * bila pasien perlu balik. Reset status clear & waktu selesai, panggil lagi.
     */
    public function panggilUlangRiwayat(Antrian $antrian)
    {
        $counter = session('farmasi_counter');
        $jenis   = session('farmasi_jenis');
        if (! $counter) {
            return redirect()->route('farmasi.pilih-counter');
        }
        if ($antrian->farmasi_jenis !== $jenis) {
            return back()->with('error', $antrian->no_antrian.' bukan resep '.$jenis.' ini.');
        }

        // Buka kembali: batal clear, hapus jejak selesai, panggil lagi.
        $antrian->forceFill([
            'resep_clear'        => false,
            'farmasi_selesai_at' => null,
        ])->save();
        $antrian->panggil(Antrian::TAHAP_FARMASI, $counter);

        return back()
            ->with('ok', 'Memanggil ulang '.$antrian->no_antrian.' ke Farmasi.')
            ->with('say', $this->saySession($antrian, 'farmasi', $counter));
    }

    /** Lanjut = selesaikan resep (Resep CLEAR → balik kasir) + panggil berikutnya. */
    public function lanjut(Antrian $antrian)
    {
        $antrian->selesaiFarmasi();

        return $this->panggilBerikutnya(request());
    }

    /** Selesai = resep selesai (Resep CLEAR) → pasien balik ke KASIR utk bayar obat. */
    public function selesai(Antrian $antrian)
    {
        $antrian->selesaiFarmasi();

        return back()->with('ok', 'Resep '.$antrian->no_antrian.' selesai — pasien kembali ke Kasir untuk pembayaran.');
    }
}
