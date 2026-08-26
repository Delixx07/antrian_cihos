<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `room_code` & `counter` dipakai terus di WHERE (guard "sudah ada panggilan
 * aktif", filter layar per ruang/counter) tapi belum diindex - murah untuk
 * ditambah walau volume harian kecil.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('antrian', function (Blueprint $table) {
            $table->index('room_code');
            $table->index('counter');
        });
    }

    public function down(): void
    {
        Schema::table('antrian', function (Blueprint $table) {
            $table->dropIndex(['room_code']);
            $table->dropIndex(['counter']);
        });
    }
};
