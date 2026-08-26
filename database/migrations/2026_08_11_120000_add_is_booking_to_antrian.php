<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tandai baris antrian yang masih BOOKING (belum check-in/registrasi).
 *
 * Tujuannya agar urutan antrian terlihat utuh sejak awal. Contoh kasus:
 * nomor 31 & 33 sudah registrasi dan tampil, lalu 32 menyusul registrasi -
 * tanpa penanda ini, 32 seolah "menyelip" dan pasien 33 merasa disalip.
 * Dengan menampilkan 32 SAMAR sejak awal, urutannya sudah terlihat, dan
 * saat 32 check-in ia hanya berubah dari samar menjadi jelas.
 *
 * Baris booking TIDAK boleh dipanggil - hanya untuk dilihat.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('antrian', function (Blueprint $table) {
            $table->boolean('is_booking')->default(false)->after('tahap');
            $table->index(['tanggal', 'is_booking']);
        });
    }

    public function down(): void
    {
        Schema::table('antrian', function (Blueprint $table) {
            $table->dropIndex(['tanggal', 'is_booking']);
            $table->dropColumn('is_booking');
        });
    }
};
