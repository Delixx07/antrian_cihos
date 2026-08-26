<?php

namespace App\Http\Controllers;

use App\Models\Antrian;
use Illuminate\Http\Request;

/**
 * Counter Kasir (role kasir_administrasi & kasir_farmasi). Pola sama dgn Farmasi
 * tapi tahap = KASIR. Klinik tak lagi memilih status resep - pasien langsung
 * dikirim ke sini, dan saat "Selesai" KASIR yang MEMILIH tujuan: Tanpa Resep
 * (selesai total), Resep Racik, atau Resep Non-Racik (lanjut ke Farmasi -
 * farmasi adalah ujung alur, tak ada balik ke kasir untuk bayar obat).
 */
class KasirController extends Controller
{
    private const TAHAP = Antrian::TAHAP_KASIR;

    /**
     * Counter ini masih punya pasien yang dipanggil TAPI belum Selesai?
     * Dipakai supaya tak bisa memanggil pasien baru (atau panggil ulang dari
     * riwayat) sebelum yang sekarang benar-benar selesai - kalau tidak,
     * "Sedang Dipanggil" numpuk dan tidak jelas siapa yang benar dilayani.
     */
    private function hasActiveKasir(string $counter): bool
    {
        return Antrian::hasActiveCall('kasir', 'counter', $counter, self::TAHAP);
    }

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

        if ($antrian->tahap !== self::TAHAP) {
            return back()->with('error', 'Pasien tidak berada di tahap kasir.');
        }

        if ($this->hasActiveKasir($counter)) {
            return back()->with('error', 'Selesaikan dulu pasien yang sedang dilayani sebelum memanggil yang baru.');
        }

        $antrian->panggil(self::TAHAP, $counter);

        return back()
            ->with('ok', 'Memanggil '.$antrian->no_antrian)
            ->with('say', $this->saySession($antrian, 'kasir', $counter));
    }

    /** Recall (panggil ulang) pasien tertentu dari baris "Di Panggil". */
    public function ulang(Antrian $antrian)
    {
        // Pasien "Sedang Dipanggil" ditampilkan LINTAS counter (supaya semua
        // kasir bisa lihat siapa yang sedang dilayani di mana), tapi Recall
        // cuma boleh dari counter yang MEMANGGIL pasien itu - kalau tidak,
        // counter mana pun bisa mengulang panggilan pasien counter lain.
        if ($antrian->counter !== session('kasir_counter')) {
            return back()->with('error', 'Pasien ini sedang ditangani counter lain.');
        }

        $antrian->ulang(self::TAHAP);

        return back()
            ->with('ok', 'Panggilan diulang: '.$antrian->no_antrian)
            ->with('say', $this->saySession($antrian, 'kasir'));
    }

    /**
     * Panggil ULANG pasien dari RIWAYAT (sudah selesai) - tarik kembali ke kasir
     * bila perlu (mis. salah, atau ada transaksi tambahan). Reset jejak selesai.
     */
    public function panggilUlangRiwayat(Antrian $antrian)
    {
        $counter = session('kasir_counter');
        if (! $counter) {
            return redirect()->route('kasir.pilih-counter');
        }
        if ($this->hasActiveKasir($counter)) {
            return back()->with('error', 'Selesaikan dulu pasien yang sedang dilayani sebelum memanggil yang baru.');
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
     * Selesai → kasir MEMILIH status resep pasien:
     *   non_resep         → selesai total (pulang).
     *   racik | non_racik → diteruskan ke Farmasi (ujung alur untuk pasien ini).
     */
    public function selesai(Request $request, Antrian $antrian)
    {
        $status = $request->input('status_resep');
        if (! in_array($status, [Antrian::RESEP_NON, Antrian::RESEP_RACIK, Antrian::RESEP_NON_RACIK], true)) {
            return back()->with('error', 'Pilih status resep dulu.');
        }

        if ($status === Antrian::RESEP_NON) {
            $antrian->selesaiKasir();

            return back()->with('ok', 'Antrian '.$antrian->no_antrian.' selesai.');
        }

        $antrian->kirimKeFarmasi($status);

        return back()->with('ok', 'Antrian '.$antrian->no_antrian.' ('.$antrian->statusLabel().') diteruskan ke Farmasi.');
    }
}
