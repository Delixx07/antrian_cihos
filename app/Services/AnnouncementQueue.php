<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * ANTREAN PENGUMUMAN SUARA (satu speaker pusat se-rumah sakit).
 *
 * Masalah yang dipecahkan: bila dua petugas menekan "panggil/recall" pada
 * saat hampir bersamaan, kedua pengumuman bisa berbunyi bertumpuk sehingga
 * tidak ada yang terdengar jelas.
 *
 * Cara kerja: setiap panggilan dimasukkan ke ANTREAN di server (cache).
 * Perangkat pemutar (speaker pusat) mengambil satu per satu — item berikutnya
 * baru diberikan setelah item sebelumnya selesai diputar. Urutan mengikuti
 * siapa yang menekan lebih dulu (FIFO).
 */
class AnnouncementQueue
{
    /** Kunci cache. */
    private const KEY_ITEMS   = 'ann:items';    // daftar antre
    private const KEY_PLAYING = 'ann:playing';  // item yang sedang diputar
    private const KEY_SEQ     = 'ann:seq';      // penomoran urut
    private const KEY_SEEN    = 'ann:seen';     // panggilan yg sudah dicatat

    /** Batas usia antrean (detik). Panggilan basi tidak perlu diumumkan lagi. */
    private const TTL = 120;

    /**
     * Perangkat pemutar dianggap mati bila tidak mengabarkan "selesai" dalam
     * rentang ini — item yang sedang diputar dilepas agar antrean tidak macet.
     */
    private const PLAYING_TIMEOUT = 45;

    /** Simpan status maksimal selama ini. */
    private const STORE_TTL = 600;

    /**
     * Daftarkan satu pengumuman. Mengembalikan true bila benar-benar
     * ditambahkan (bukan duplikat).
     */
    public function push(string $no, ?string $dest, string $area, int $seq): bool
    {
        $items = $this->items();

        // Kunci unik per panggilan: nomor + berapa kali dipanggil. Panggilan
        // ulang (recall) punya seq berbeda sehingga tetap diumumkan lagi.
        $key = $area.'|'.$no.'|'.$seq;

        // Sudah ada di antrean atau sedang diputar → jangan dobel.
        foreach ($items as $it) {
            if (($it['key'] ?? null) === $key) {
                return false;
            }
        }
        $playing = Cache::get(self::KEY_PLAYING);
        if ($playing && ($playing['key'] ?? null) === $key) {
            return false;
        }

        $items[] = [
            'key'  => $key,
            'no'   => $no,
            'dest' => $dest,
            'area' => $area,
            'seq'  => $seq,
            'at'   => microtime(true),
            'id'   => (int) Cache::increment(self::KEY_SEQ),
        ];

        Cache::put(self::KEY_ITEMS, $items, self::STORE_TTL);

        return true;
    }

    /**
     * Ambil pengumuman berikutnya untuk diputar, atau null bila belum boleh
     * (masih ada yang berbunyi) / antrean kosong.
     */
    public function next(): ?array
    {
        $playing = Cache::get(self::KEY_PLAYING);

        if ($playing) {
            // Masih ada yang diputar → tahan dulu, KECUALI bila pemutarnya
            // tampak mati (tidak pernah lapor selesai).
            if ((microtime(true) - ($playing['started'] ?? 0)) < self::PLAYING_TIMEOUT) {
                return null;
            }
            Cache::forget(self::KEY_PLAYING);
        }

        $items = $this->items();
        if (! $items) {
            return null;
        }

        $item = array_shift($items);
        Cache::put(self::KEY_ITEMS, $items, self::STORE_TTL);

        $item['started'] = microtime(true);
        Cache::put(self::KEY_PLAYING, $item, self::STORE_TTL);

        return $item;
    }

    /** Tandai pengumuman selesai diputar sehingga berikutnya boleh jalan. */
    public function done(?string $key = null): void
    {
        $playing = Cache::get(self::KEY_PLAYING);
        if (! $playing) {
            return;
        }
        // Abaikan laporan "selesai" milik item lain (mis. datang terlambat).
        if ($key !== null && ($playing['key'] ?? null) !== $key) {
            return;
        }
        Cache::forget(self::KEY_PLAYING);
    }

    /** Isi antrean, sudah dibuang yang kedaluwarsa. */
    private function items(): array
    {
        $items = Cache::get(self::KEY_ITEMS, []);
        if (! is_array($items)) {
            return [];
        }

        $now = microtime(true);

        return array_values(array_filter(
            $items,
            fn ($it) => ($now - ($it['at'] ?? 0)) < self::TTL
        ));
    }

    /** Jumlah yang masih menunggu giliran diumumkan (untuk pemantauan). */
    public function pending(): int
    {
        return count($this->items());
    }

    /**
     * Tandai sebuah panggilan sebagai "sudah pernah dilihat" tanpa
     * mengumumkannya.
     *
     * Dipakai saat speaker BARU dinyalakan: panggilan yang sedang berjalan
     * tidak perlu diulang, hanya panggilan setelah ini yang diumumkan.
     */
    public function markSeen(string $no, string $area, int $seq): void
    {
        $seen = Cache::get(self::KEY_SEEN, []);
        if (! is_array($seen)) {
            $seen = [];
        }
        $seen[$area.'|'.$no.'|'.$seq] = time();
        // Buang catatan lama agar tidak menumpuk.
        $seen = array_filter($seen, fn ($t) => (time() - $t) < self::TTL);
        Cache::put(self::KEY_SEEN, $seen, self::STORE_TTL);
    }

    /** Apakah panggilan ini sudah pernah dicatat/diumumkan? */
    public function seen(string $no, string $area, int $seq): bool
    {
        $seen = Cache::get(self::KEY_SEEN, []);

        return is_array($seen) && isset($seen[$area.'|'.$no.'|'.$seq]);
    }
}
