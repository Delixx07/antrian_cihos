<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Antrian pasien (LOKAL, bisa ditulis). Satu baris = satu pasien per hari yang
 * mengalir melewati tahap: KLINIK → KASIR → FARMASI (admisi opsional). Sumber
 * pasien = registrasi MEDINFRAS (dibaca dari aplikasi appointment), lalu tiap
 * counter memanggil & mengisi timestamp tahapnya. Meniru tabel `antrian` lama
 * (disederhanakan). Tiap tahap: tunggu → panggil → selesai; "selesai" mendorong
 * pasien ke tahap berikutnya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('antrian', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal')->index();

            // Identitas antrian
            $table->string('no_antrian', 30)->nullable();   // mis. ED001 (prefix + urut)
            $table->string('prefix', 20)->nullable();       // prefix ruangan/zona
            $table->unsignedInteger('queue_no')->default(0);

            // Pasien
            $table->string('pasien_nama')->nullable();
            $table->string('pasien_nomrn', 50)->nullable();
            $table->string('pasien_jk', 5)->nullable();

            // Klinik/dokter
            $table->string('poli_kode', 50)->nullable();
            $table->string('poli_nama')->nullable();
            $table->unsignedBigInteger('paramedic_id')->nullable()->index();
            $table->string('poli_dokter_nama')->nullable();
            $table->string('room_code', 50)->nullable();
            $table->string('room_name')->nullable();

            // Referensi MEDINFRAS
            $table->string('appointment_no', 60)->nullable();
            $table->string('registration_no', 60)->nullable();

            // Tahap saat ini: klinik | kasir | farmasi | selesai
            $table->string('tahap', 20)->default('klinik')->index();

            // Farmasi: jenis (racik/non_racik) di-set saat masuk tahap farmasi.
            $table->string('farmasi_jenis', 20)->nullable();  // 'racik' | 'non_racik'
            $table->string('counter')->nullable();            // counter yang memanggil (mis. "Counter 1")

            // Timestamp per tahap (tunggu → panggil → selesai).
            foreach (['klinik', 'kasir', 'farmasi'] as $st) {
                $table->timestamp("{$st}_tunggu_at")->nullable();
                $table->timestamp("{$st}_panggil_at")->nullable();
                $table->timestamp("{$st}_selesai_at")->nullable();
            }

            // Berapa kali dipanggil (untuk tombol "Ulang").
            $table->unsignedSmallInteger('panggil_count')->default(0);

            $table->timestamps();

            $table->index(['tanggal', 'tahap']);
            $table->unique(['tanggal', 'appointment_no'], 'uq_antrian_appt'); // cegah dobel per hari
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('antrian');
    }
};
