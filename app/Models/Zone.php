<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Zona klinik (dulu di-hardcode sebagai DisplayController::ZONE_NAMES/ZONE_PAIRS)
 * - dikelola admin lewat halaman Zona, dibaca DisplayController untuk kartu
 * "Zona Klinik" di menu display + header "ZONE {code}" saat Main Display
 * dibuka per-zona (?floor=). `rooms` = array pasangan kode ruang per Client
 * Display, mis. [["1101"],["1102","1103"]].
 *
 * Fisik ada di DB lokal `antrian` (koneksi default) - data konfigurasi khas
 * app ini, sama sifatnya dengan Setting/Banner/Video/AntrianAccess. Sengaja
 * TIDAK ditaruh di `master` (cihos_master): semua tabel di sana konsisten
 * read-only hasil sync MEDINFRAS, biar konvensi itu tetap bersih.
 */
class Zone extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'rooms' => 'array',
    ];

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('zones:all'));
        static::deleted(fn () => Cache::forget('zones:all'));
    }

    /**
     * Semua zona, keyed by code, di-cache - dibaca layar TV tiap poll (4 detik)
     * lewat DisplayController::json()/show(), jadi jangan query mentah tiap kali.
     */
    public static function allCached()
    {
        return Cache::remember('zones:all', 300, function () {
            return static::orderBy('sort')->orderBy('code')->get()->keyBy('code');
        });
    }

    /** Representasi baris "rooms" jadi teks 1 grup/baris, koma dalam grup - untuk form admin. */
    public function getRoomsTextAttribute(): string
    {
        return collect($this->rooms ?? [])
            ->map(fn ($group) => implode(',', $group))
            ->implode("\n");
    }

    /** Kebalikan dari getRoomsTextAttribute() - parse textarea admin jadi array pasangan. */
    public static function parseRoomsText(string $text): array
    {
        $groups = [];
        foreach (preg_split('/\r\n|\r|\n/', trim($text)) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $group = collect(explode(',', $line))->map(fn ($r) => trim($r))->filter()->values()->all();
            if ($group) {
                $groups[] = $group;
            }
        }

        return $groups;
    }
}
