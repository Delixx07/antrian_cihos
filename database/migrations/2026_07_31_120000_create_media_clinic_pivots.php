<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Penargetan media (banner/video) ke klinik/poli tertentu untuk layar tunggu.
 * Menyimpan service_unit_code (klinik ada di DB master, jadi tak pakai FK
 * lintas-database — cukup kode string). Bila sebuah media TIDAK punya baris di
 * sini, artinya tampil di SEMUA klinik (default).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banner_clinics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('banner_id')->constrained('banners')->cascadeOnDelete();
            $table->string('service_unit_code', 40);
            $table->timestamps();
            $table->unique(['banner_id', 'service_unit_code']);
            $table->index('service_unit_code');
        });

        Schema::create('video_clinics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('video_id')->constrained('videos')->cascadeOnDelete();
            $table->string('service_unit_code', 40);
            $table->timestamps();
            $table->unique(['video_id', 'service_unit_code']);
            $table->index('service_unit_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banner_clinics');
        Schema::dropIfExists('video_clinics');
    }
};
