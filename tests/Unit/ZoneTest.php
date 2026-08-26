<?php

namespace Tests\Unit;

use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Zone menggantikan DisplayController::ZONE_NAMES/ZONE_PAIRS yang dulu
 * di-hardcode - parsing teks admin <-> array pasangan ruang, dan cache
 * allCached() yang dipoll layar TV tiap beberapa detik, harus benar-benar
 * akurat (salah parse = ruang salah pasangan di layar publik).
 */
class ZoneTest extends TestCase
{
    use RefreshDatabase;

    public function test_parse_rooms_text_pisahkan_baris_jadi_grup_dan_koma_dalam_grup(): void
    {
        $parsed = Zone::parseRoomsText("1101\n1102,1103\n1105,1106");

        $this->assertSame([['1101'], ['1102', '1103'], ['1105', '1106']], $parsed);
    }

    public function test_parse_rooms_text_abaikan_baris_kosong_dan_spasi_berlebih(): void
    {
        $parsed = Zone::parseRoomsText("  1101  \n\n  1102 , 1103  \n\n");

        $this->assertSame([['1101'], ['1102', '1103']], $parsed);
    }

    public function test_parse_rooms_text_kosong_menghasilkan_array_kosong(): void
    {
        $this->assertSame([], Zone::parseRoomsText(''));
        $this->assertSame([], Zone::parseRoomsText("   \n  \n"));
    }

    public function test_rooms_text_accessor_adalah_kebalikan_persis_dari_parse(): void
    {
        // Kode "ZT1" (bukan "11" dkk) sengaja dipakai - migration zones sudah
        // seed zona asli (11,12,15,16,17,18), jadi kode uji harus tak bentrok.
        $rooms = [['1101'], ['1102', '1103'], ['1105', '1106']];
        $zone = Zone::create(['code' => 'ZT1', 'name' => 'Zona Uji', 'rooms' => $rooms, 'sort' => 1]);

        $roundTrip = Zone::parseRoomsText($zone->rooms_text);

        $this->assertSame($rooms, $roundTrip);
    }

    public function test_all_cached_mengembalikan_zona_keyed_by_code(): void
    {
        Zone::create(['code' => 'ZT8', 'name' => 'Zona Uji 8', 'rooms' => [], 'sort' => 998]);
        Zone::create(['code' => 'ZT1', 'name' => 'Zona Uji 1', 'rooms' => [], 'sort' => 991]);

        $zones = Zone::allCached();

        // Zona dari seed migration (11,12,dst) ikut ada di sini juga - itu
        // memang benar (allCached() = SEMUA zona) - cukup pastikan milik kita
        // ketemu & urutannya tepat relatif satu sama lain (via sort).
        $this->assertTrue($zones->has('ZT1'));
        $this->assertTrue($zones->has('ZT8'));
        $this->assertSame('Zona Uji 1', $zones->get('ZT1')->name);
        $ztKeys = $zones->keys()->filter(fn ($k) => str_starts_with($k, 'ZT'))->values()->all();
        $this->assertSame(['ZT1', 'ZT8'], $ztKeys, 'harus terurut berdasarkan sort');
    }

    public function test_cache_ter_invalidate_otomatis_saat_zona_diubah(): void
    {
        $zone = Zone::create(['code' => 'ZT1', 'name' => 'Nama Lama', 'rooms' => [], 'sort' => 1]);
        $this->assertSame('Nama Lama', Zone::allCached()->get('ZT1')->name);

        $zone->update(['name' => 'Nama Baru']);

        $this->assertSame(
            'Nama Baru',
            Zone::allCached()->get('ZT1')->name,
            'poll() layar TV (tiap 4 detik) harus langsung lihat perubahan admin, bukan data cache basi'
        );
    }

    public function test_cache_ter_invalidate_otomatis_saat_zona_dihapus(): void
    {
        $zone = Zone::create(['code' => 'ZT1', 'name' => 'Zona Uji', 'rooms' => [], 'sort' => 1]);
        Zone::allCached(); // pastikan ke-cache dulu

        $zone->delete();

        $this->assertFalse(Zone::allCached()->has('ZT1'));
    }
}
