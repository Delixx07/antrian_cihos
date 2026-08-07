<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Password LOKAL opsional untuk akun antrian yang tak ada di direktori RS
 * (mis. super admin / akun mesin: farmasi, kasir, loket). Bila null, login user
 * ini diverifikasi ke dbuser seperti biasa; bila terisi (bcrypt), diverifikasi
 * lokal. Ini membuat akun operasional non-pegawai tetap bisa dibuat.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('antrian_access', function (Blueprint $table) {
            $table->string('password')->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('antrian_access', function (Blueprint $table) {
            $table->dropColumn('password');
        });
    }
};
