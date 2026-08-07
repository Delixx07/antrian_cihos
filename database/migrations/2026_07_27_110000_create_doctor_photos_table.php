<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Foto dokter yang di-upload & disimpan lokal di aplikasi antrian. Di-key oleh
 * paramedic_id. Nama file memakai NIK dokter (dari dbuser). File fisik ada di
 * public/doctor-photos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctor_photos', function (Blueprint $table) {
            $table->unsignedBigInteger('paramedic_id')->primary();
            $table->string('nik')->nullable();
            $table->string('filename');        // mis. 3578....png
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_photos');
    }
};
