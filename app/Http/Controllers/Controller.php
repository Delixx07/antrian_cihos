<?php

namespace App\Http\Controllers;

use App\Models\Antrian;

abstract class Controller
{
    /**
     * Titipkan data pengumuman suara ke flash session.
     *
     * Dipakai oleh klinik / kasir / farmasi. Begitu halaman dimuat ulang
     * setelah tombol Panggil/Recall ditekan, layout memutar audionya —
     * tidak bergantung pada layar/display mana pun yang sedang terbuka.
     *
     * @param  string  $area   klinik | kasir | farmasi
     * @param  ?string $dest   nomor ruang/counter; null = ambil dari data
     */
    protected function saySession(Antrian $antrian, string $area, ?string $dest = null): array
    {
        $dest ??= $area === 'klinik' ? $antrian->room_code : $antrian->counter;
        $dest = trim((string) $dest);

        // Nilai counter kadang sudah memuat kata depannya sendiri
        // ("Counter 2") atau embel-embel zona ("1859 (Zona 18)") — dibuang
        // agar tidak terucap ganda / salah eja.
        $dest = preg_replace('/\s*\([^)]*\)\s*/', ' ', $dest);
        $dest = trim(preg_replace('/^(counter|loket|ruang|room)\s*/i', '', $dest));

        return [
            'no'   => $antrian->no_antrian,
            'dest' => $dest,
            'area' => $area,
        ];
    }
}
