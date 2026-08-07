<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Data tambahan per klinik yang DIKELOLA di aplikasi antrian (zona + room code).
 * Daftar klinik-nya sendiri dibaca read-only dari DB appointment; tabel ini
 * menyimpan pelengkap yang tak ada di sana, di-key oleh kode klinik.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinic_settings', function (Blueprint $table) {
            $table->string('service_unit_code')->primary(); // kode klinik (mis. SU-029)
            $table->string('zone_code')->nullable();
            $table->string('zone_name')->nullable();
            $table->string('room_code_1')->nullable();
            $table->string('room_code_2')->nullable();
            $table->string('room_code_3')->nullable();
            $table->string('room_code_4')->nullable();
            $table->string('room_code_5')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinic_settings');
    }
};
