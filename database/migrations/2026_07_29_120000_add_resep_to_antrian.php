<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Status resep pada antrian. Dokter (klinik) menentukan saat transfer ke kasir:
 *   non_resep  → tanpa resep obat
 *   racik      → resep racik
 *   non_racik  → resep non-racik
 * Farmasi hanya melihat yang ber-resep (racik/non_racik). Setelah farmasi selesai
 * memproses, `resep_clear`=true → pasien balik ke kasir untuk pembayaran obat.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('antrian', function (Blueprint $table) {
            // Status resep ditentukan dokter: non_resep|racik|non_racik.
            $table->string('status_resep', 20)->nullable()->after('tahap')->index();
            // Farmasi sudah selesai memproses resep (siap balik kasir utk bayar).
            $table->boolean('resep_clear')->default(false)->after('status_resep');
            // Waktu tunggu dihitung sejak transfer dari klinik.
            $table->timestamp('transfer_at')->nullable()->after('klinik_selesai_at');
        });
    }

    public function down(): void
    {
        Schema::table('antrian', function (Blueprint $table) {
            $table->dropColumn(['status_resep', 'resep_clear', 'transfer_at']);
        });
    }
};
