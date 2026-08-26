<?php

namespace App\Http\Controllers;

use App\Models\KioskRegistration;
use Illuminate\Http\Request;

/**
 * Counter Registrasi (role registrasi) - panggil tiket RG dari kiosk (lihat
 * App\Models\KioskRegistration, tabel `kiosk_registrations` fisik ada di app
 * `appointment`). Pola SAMA PERSIS dengan KasirController, tapi "Selesai" di
 * sini cuma menandai RG sudah ditangani - TIDAK membuat appointment resmi
 * (itu alur terpisah, staf pakai aplikasi appointment sendiri).
 */
class RegistrasiController extends Controller
{
    /**
     * Counter ini masih punya RG yang dipanggil TAPI belum Selesai? Dipakai
     * supaya tak bisa memanggil RG baru sebelum yang sekarang benar-benar
     * selesai - kalau tidak, "Sedang Dipanggil" numpuk.
     */
    private function hasActive(string $counter): bool
    {
        return KioskRegistration::hasActiveCall($counter);
    }

    public function pilihCounter()
    {
        return view('registrasi.pilih-counter', [
            'counter' => session('registrasi_counter'),
        ]);
    }

    public function setCounter(Request $request)
    {
        $data = $request->validate([
            'counter' => ['required', 'string', 'max:50'],
        ]);
        session(['registrasi_counter' => $data['counter']]);

        return redirect()->route('registrasi.beranda');
    }

    public function beranda(Request $request)
    {
        if (! session('registrasi_counter')) {
            return redirect()->route('registrasi.pilih-counter');
        }

        $counter = session('registrasi_counter');
        $base = fn () => KioskRegistration::whereDate('tanggal', today());

        $menunggu  = $base()->where('status', 'menunggu')->orderBy('created_at')->get();
        $dipanggil = $base()->whereNotNull('panggil_at')->whereNull('selesai_at')
            ->orderByDesc('panggil_at')->get();
        $current   = $base()->where('counter', $counter)
            ->whereNotNull('panggil_at')->whereNull('selesai_at')
            ->orderByDesc('panggil_at')->first();

        // Riwayat: RG yang sudah ditandai selesai hari ini (bisa dipanggil ulang).
        $selesai = $base()->where('status', 'selesai')->orderByDesc('selesai_at')->get();

        return view('registrasi.beranda', [
            'counter'    => $counter,
            'menunggu'   => $menunggu,
            'dipanggil'  => $dipanggil,
            'current'    => $current,
            'selesai'    => $selesai,
            'sisa'       => $menunggu->count(),
            'berikutnya' => $menunggu->first(),
        ]);
    }

    /** Panggil tiket RG TERTENTU (dari tombol di barisnya). */
    public function panggil(KioskRegistration $registrasi)
    {
        $counter = session('registrasi_counter');
        if (! $counter) {
            return redirect()->route('registrasi.pilih-counter');
        }
        if ($this->hasActive($counter)) {
            return back()->with('error', 'Selesaikan dulu RG yang sedang dilayani sebelum memanggil yang baru.');
        }

        // forceFill selesai_at=null juga di sini supaya tombol yang sama bisa
        // dipakai memanggil ULANG dari riwayat (RG yang sudah ditandai selesai).
        $registrasi->forceFill([
            'status'        => 'dipanggil',
            'counter'       => $counter,
            'panggil_at'    => now(),
            'selesai_at'    => null,
            'panggil_count' => $registrasi->panggil_count + 1,
        ])->save();

        return back()
            ->with('ok', 'Memanggil '.$registrasi->rg_no)
            ->with('say', ['no' => $registrasi->rg_no, 'dest' => $this->cleanDest($counter), 'area' => 'registrasi']);
    }

    /** Recall (panggil ulang) tiket RG yang sedang dipanggil. */
    public function ulang(KioskRegistration $registrasi)
    {
        // "Sedang Dipanggil" ditampilkan LINTAS counter, tapi Recall cuma boleh
        // dari counter yang memanggil RG itu - kalau tidak, counter mana pun
        // bisa mengulang panggilan RG counter lain.
        if ($registrasi->counter !== session('registrasi_counter')) {
            return back()->with('error', 'RG ini sedang ditangani counter lain.');
        }

        $registrasi->forceFill([
            'panggil_at'    => now(),
            'panggil_count' => $registrasi->panggil_count + 1,
        ])->save();

        return back()
            ->with('ok', 'Panggilan diulang: '.$registrasi->rg_no)
            ->with('say', ['no' => $registrasi->rg_no, 'dest' => $this->cleanDest($registrasi->counter), 'area' => 'registrasi']);
    }

    /** Selesai - tandai RG sudah ditangani (BUKAN membuat appointment resmi). */
    public function selesai(KioskRegistration $registrasi)
    {
        $registrasi->forceFill([
            'status'     => 'selesai',
            'selesai_at' => now(),
        ])->save();

        return back()->with('ok', 'RG '.$registrasi->rg_no.' selesai ditangani.');
    }
}
