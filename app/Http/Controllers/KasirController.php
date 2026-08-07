<?php

namespace App\Http\Controllers;

use App\Models\Antrian;
use Illuminate\Http\Request;

/**
 * Counter Kasir (role kasir_administrasi & kasir_farmasi). Pola sama dgn Farmasi
 * tapi tahap = KASIR, dan saat "Selesai" kasir MEMILIH tujuan: selesai total
 * (tak ada resep) atau lanjut ke Farmasi (ada resep) — alur bercabang sesuai
 * aplikasi lama (±36% pasien kasir lanjut ke farmasi).
 */
class KasirController extends Controller
{
    private const TAHAP = Antrian::TAHAP_KASIR;

    public function pilihCounter()
    {
        return view('kasir.pilih-counter', [
            'counter' => session('kasir_counter'),
        ]);
    }

    public function setCounter(Request $request)
    {
        $data = $request->validate([
            'counter' => ['required', 'string', 'max:50'],
        ]);
        session(['kasir_counter' => $data['counter']]);

        return redirect()->route('kasir.beranda');
    }

    public function beranda(Request $request)
    {
        if (! session('kasir_counter')) {
            return redirect()->route('kasir.pilih-counter');
        }

        $counter = session('kasir_counter');
        $base = fn () => Antrian::today()->where('tahap', self::TAHAP);

        $menunggu  = $base()->whereNull('kasir_panggil_at')->orderBy('kasir_tunggu_at')->get();
        $dipanggil = $base()->whereNotNull('kasir_panggil_at')->whereNull('kasir_selesai_at')
            ->orderByDesc('kasir_panggil_at')->get();
        $current   = $base()->where('counter', $counter)
            ->whereNotNull('kasir_panggil_at')->whereNull('kasir_selesai_at')
            ->orderByDesc('kasir_panggil_at')->first();

        // Riwayat: pasien yang SUDAH diselesaikan kasir hari ini (bisa dipanggil ulang).
        $selesai = Antrian::today()
            ->whereNotNull('kasir_selesai_at')
            ->orderByDesc('kasir_selesai_at')->get();

        return view('kasir.beranda', [
            'counter'    => $counter,
            'roleLabel'  => session('role') === 'kasir_farmasi' ? 'KASIR FARMASI' : 'KASIR ADMINISTRASI',
            'menunggu'   => $menunggu,
            'dipanggil'  => $dipanggil,
            'current'    => $current,
            'selesai'    => $selesai,
            'sisa'       => $menunggu->count(),
            'berikutnya' => $menunggu->first(),
        ]);
    }

    /** Panggil pasien TERTENTU (dari tombol di barisnya). */
    public function panggil(Antrian $antrian)
    {
        $counter = session('kasir_counter');
        if (! $counter) {
            return redirect()->route('kasir.pilih-counter');
        }

        // Guard: pasien ber-resep yang BELUM diproses farmasi (belum CLEAR)
        // tak boleh dipanggil kasir dulu — harus ke Farmasi lebih dulu.
        if ($antrian->adaResep() && ! $antrian->resep_clear) {
            return back()->with('error', $antrian->no_antrian.' masih menunggu Farmasi — belum bisa dipanggil kasir.');
        }

        if ($antrian->tahap !== self::TAHAP) {
            return back()->with('error', 'Pasien tidak berada di tahap kasir.');
        }

        $antrian->panggil(self::TAHAP, $counter);

        return back()
            ->with('ok', 'Memanggil '.$antrian->no_antrian)
            ->with('say', $this->saySession($antrian, 'kasir', $counter));
    }

    /** Recall (panggil ulang) pasien tertentu dari baris "Di Panggil". */
    public function ulang(Antrian $antrian)
    {
        $antrian->ulang(self::TAHAP);

        return back()
            ->with('ok', 'Panggilan diulang: '.$antrian->no_antrian)
            ->with('say', $this->saySession($antrian, 'kasir'));
    }

    /**
     * Panggil ULANG pasien dari RIWAYAT (sudah selesai) — tarik kembali ke kasir
     * bila perlu (mis. salah, atau ada transaksi tambahan). Reset jejak selesai.
     */
    public function panggilUlangRiwayat(Antrian $antrian)
    {
        $counter = session('kasir_counter');
        if (! $counter) {
            return redirect()->route('kasir.pilih-counter');
        }

        $antrian->forceFill([
            'tahap'            => self::TAHAP,
            'kasir_selesai_at' => null,
        ])->save();
        $antrian->panggil(self::TAHAP, $counter);

        return back()
            ->with('ok', 'Memanggil ulang '.$antrian->no_antrian.' ke Kasir.')
            ->with('say', $this->saySession($antrian, 'kasir', $counter));
    }

    /**
     * Selesai. Tujuan OTOMATIS dari status pasien (tak perlu pilih manual):
     *   - Tanpa resep         → selesai (pulang)
     *   - Ada resep, blm clear → farmasi
     *   - Resep sudah CLEAR    → selesai (bayar obat terakhir)
     */
    public function selesai(Antrian $antrian)
    {
        $antrian->selesaiKasir();

        $msg = match ($antrian->tahap) {
            Antrian::TAHAP_FARMASI => 'Antrian '.$antrian->no_antrian.' diteruskan ke Farmasi (ambil obat).',
            default                => 'Antrian '.$antrian->no_antrian.' selesai.',
        };

        return back()->with('ok', $msg);
    }
}
