<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Penanda "dokter sedang menempati ruang" (mirip islogin di sistem lama).
 * Di-set saat dokter login & masuk ruang (room_code), dikosongkan saat logout.
 * Dipakai untuk: (1) aturan 1 dokter/ruang (blokir bentrok), (2) Client Display
 * menentukan dokter mana yang tampil di sebuah ruang.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('antrian_access', function (Blueprint $table) {
            $table->timestamp('room_occupied_at')->nullable()->after('room_code');
        });
    }

    public function down(): void
    {
        Schema::table('antrian_access', function (Blueprint $table) {
            $table->dropColumn('room_occupied_at');
        });
    }
};
