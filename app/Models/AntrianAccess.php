<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Hak akses aplikasi antrian (LOKAL). Login diverifikasi ke dbuser; baris ini
 * menentukan boleh-masuk + peran + keterikatan. Meniru pola user_access
 * appointment. Lihat migration create_antrian_access_table.
 */
class AntrianAccess extends Model
{
    /**
     * WAJIB ditetapkan eksplisit.
     *
     * Tanpa ini model memakai koneksi DEFAULT saat itu - dan bila dalam satu
     * request ada model lain yang sempat mengubah koneksi default, query ini
     * ikut nyasar ke database appointment ("Table
     * appointment_pasien_cihos.antrian_access doesn't exist"). Akibatnya
     * pemeriksaan hak akses gagal dan petugas terlempar ke halaman login.
     */
    protected $connection = 'mysql';

    protected $table = 'antrian_access';

    protected $fillable = [
        'username', 'name', 'password', 'role', 'paramedic_id', 'paramedic_name',
        'counter', 'room_code', 'room_occupied_at', 'zona', 'is_blocked', 'last_login_at',
    ];

    protected $hidden = ['password'];

    protected $casts = [
        'is_blocked'       => 'boolean',
        'last_login_at'    => 'datetime',
        'room_occupied_at' => 'datetime',
    ];

    /** Akun ini punya password lokal sendiri (tak lewat dbuser). */
    public function hasLocalPassword(): bool
    {
        return ! empty($this->password);
    }

    /** Cek password lokal (bcrypt). */
    public function checkLocalPassword(string $plain): bool
    {
        return $this->hasLocalPassword()
            && \Illuminate\Support\Facades\Hash::check($plain, $this->password);
    }

    /**
     * Daftar hak akses (role) - sesuai form "Tambah User" antrian asli.
     * key = kode disimpan di DB, value = label tampilan.
     */
    public const ROLES = [
        'administrator'        => 'Administrator',
        'admisi_rajal_lab'     => 'Admisi Rawat Jalan & LAB',
        'admisi_igd'           => 'Admisi IGD',
        'admisi_radiologi'     => 'Admisi Radiologi',
        'klinik'               => 'Klinik',
        'farmasi'              => 'Farmasi',
        'kasir_administrasi'   => 'Kasir Administrasi',
        'kasir_farmasi'        => 'Kasir Farmasi',
        'spv'                  => 'SPV',
        'registrasi'           => 'Registrasi',
    ];

    /** Role yang terikat ke seorang dokter (butuh paramedic_id). */
    public const ROLE_KLINIK = 'klinik';

    /** Role dengan akses penuh (kelola user, semua menu). */
    public const ROLE_ADMIN = 'administrator';

    /** Label tampilan role ini. */
    public function roleLabel(): string
    {
        return self::ROLES[$this->role] ?? $this->role;
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /** SPV dianggap bisa lihat semua (read) tapi bukan kelola user. */
    public function isSupervisor(): bool
    {
        return $this->role === 'spv';
    }

    public function isDoctor(): bool
    {
        return $this->role === self::ROLE_KLINIK;
    }

    /** Hanya admin yang boleh mengelola user. */
    public function canManageUsers(): bool
    {
        return $this->isAdmin();
    }

    /** Cari record akses untuk sebuah username (case-insensitive aman). */
    public static function forUser(string $username): ?self
    {
        return static::where('username', $username)->first();
    }
}
