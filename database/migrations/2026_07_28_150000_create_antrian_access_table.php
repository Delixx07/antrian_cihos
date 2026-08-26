<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kontrol akses aplikasi Antrian - LOKAL (DB antrian_cihos, bukan dbuser).
 *
 * Identitas & password diverifikasi ke direktori RS (dbuser.user_detail, SHA1).
 * Tabel ini menentukan SIAPA dari direktori itu yang boleh masuk aplikasi
 * antrian, PERANNYA (hak akses), dan keterikatan (dokter untuk role Klinik,
 * counter/room untuk Farmasi/Kasir/Loket). Password TIDAK disimpan di sini.
 *
 * Satu baris = satu user yang boleh masuk, di-key `username` (= dbuser.user).
 * Pola meniru `user_access` di aplikasi appointment.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('antrian_access', function (Blueprint $table) {
            $table->id();
            $table->string('username', 100)->unique();     // = dbuser.user_detail.user
            $table->string('name')->nullable();            // nama tampilan (dari dbuser saat dibuat)
            $table->string('role', 40);                    // hak akses: administrator/klinik/farmasi/...

            // Keterikatan role Klinik → dokter tertentu (paramedic_id di cihos_master).
            $table->unsignedBigInteger('paramedic_id')->nullable();
            $table->string('paramedic_name')->nullable();  // cache nama dokter utk tampilan

            // Keterikatan operasional untuk Farmasi/Kasir/Loket/Radiologi.
            $table->string('counter')->nullable();         // mis. "Counter 1", "Farmasi Racik"
            $table->string('room_code')->nullable();
            $table->string('zona')->nullable();

            $table->boolean('is_blocked')->default(false); // "Blokir User"
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();

            $table->index('role');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('antrian_access');
    }
};
