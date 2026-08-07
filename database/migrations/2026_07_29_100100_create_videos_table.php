<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Video promo untuk layar tunggu/kiosk. Meniru tabel `video` lama: judul + file
 * .mp4 + flag aktif (hanya yang aktif diputar). File fisik di public/videos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('videos', function (Blueprint $table) {
            $table->id();
            $table->string('judul');
            $table->string('filename');            // nama file .mp4 (public/videos)
            $table->boolean('is_active')->default(false); // hanya yg aktif diputar
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('videos');
    }
};
