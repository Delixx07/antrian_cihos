<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Menggantikan DisplayController::ZONE_NAMES/ZONE_PAIRS yang sebelumnya
 * di-hardcode di kode - pindah ke DB supaya admin bisa ubah/tambah zona
 * tanpa perlu deploy. `rooms` = array pasangan ruang per layar Client Display
 * (mis. [["1101"],["1102","1103"]]), persis struktur ZONE_PAIRS lama.
 *
 * Ditaruh di DB lokal `antrian` (koneksi default), BUKAN di `master` -
 * data ini konfigurasi lokal khas app ini (siapa pun yang buka layar TV),
 * sama sifatnya dengan Setting/Banner/Video/AntrianAccess yang juga di sini.
 * Semua tabel di koneksi `master` konsisten READ-ONLY (mirror MEDINFRAS);
 * `zones` sengaja tidak ikut ke sana supaya konvensi itu tetap bersih.
 *
 * up() sekaligus MEMBAWA data zona yang sudah ada supaya deploy migration ini
 * tidak menghilangkan zona yang sudah berjalan di produksi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zones', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('name');
            // Array pasangan ruang; BOLEH kosong ([]) - zona lama (mis. '16')
            // sengaja hanya punya nama, ruangnya diambil otomatis (dipasangkan
            // 2-2) dari master saat itu tak dikonfigurasi manual. Lihat
            // DisplayController::menu()/zoneRooms().
            $table->json('rooms');
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        $seed = [
            ['code' => '11', 'name' => 'Dental, Women & Children clinic', 'sort' => 11,
                'rooms' => [['1101'], ['1102', '1103'], ['1105', '1106'], ['1107'], ['1108'], ['1110', '1109']]],
            ['code' => '12', 'name' => 'Beauty & Nutrition, Surgery clinic', 'sort' => 12,
                'rooms' => [['1219', '1220'], ['1217', '1218'], ['1223', '1225'], ['1221', '1222']]],
            ['code' => '15', 'name' => 'Neuroscience, Orthopedic, Oncology Clinic', 'sort' => 15,
                'rooms' => [['1528', '1529'], ['1526', '1527'], ['1533'], ['1531', '1532']]],
            // Tak pernah punya pasangan ruang manual (dulu cuma ada di ZONE_NAMES,
            // absen dari ZONE_PAIRS) - rooms kosong, dipasangkan otomatis dari master.
            ['code' => '16', 'name' => 'Medical & Sport Rehabilitation Center', 'sort' => 16, 'rooms' => []],
            ['code' => '17', 'name' => 'Cardiology & Internal Medicine Clinic, Pharmacy', 'sort' => 17,
                'rooms' => [['1736'], ['1737', '1738'], ['1739', '1751'], ['1752', '1753']]],
            ['code' => '18', 'name' => 'Medical Check Up', 'sort' => 18,
                'rooms' => [['1855'], ['1857', '1858'], ['1859', '1860']]],
        ];

        $now = now();
        foreach ($seed as $row) {
            $row['rooms'] = json_encode($row['rooms']);
            $row['created_at'] = $now;
            $row['updated_at'] = $now;
            DB::table('zones')->insert($row);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('zones');
    }
};
