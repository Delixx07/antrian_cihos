<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Banner (gambar promo) untuk layar tunggu/kiosk. Meniru tabel `banner` lama:
 * nama + file gambar. Semua banner tampil sebagai slideshow (tak ada aktif/tidak).
 * File fisik disimpan di public/banners.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->string('nama');            // label banner
            $table->string('image');           // nama file gambar (public/banners)
            $table->unsignedInteger('sort')->default(0); // urutan tampil
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banners');
    }
};
