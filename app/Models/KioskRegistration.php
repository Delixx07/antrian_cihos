<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Tiket RG (pra-registrasi) dari kiosk - tabel `kiosk_registrations` FISIK ada
 * di database app `appointment` (dibuat & diisi oleh KioskController::rg() di
 * sana), model ini cuma "jendela" dari app `antrian` lewat koneksi `appointment`
 * yang sudah ada (lihat config/database.php). Konsol Registrasi (RegistrasiController)
 * MENULIS ke sini (status/counter/panggil_at) - pengecualian yang disengaja dari
 * komentar "read-only" di koneksi itu, karena tabel ini memang dibuat utk dipakai
 * dari sisi antrian.
 */
class KioskRegistration extends Model
{
    protected $connection = 'appointment';

    protected $table = 'kiosk_registrations';

    protected $guarded = ['id'];

    protected $casts = [
        'tanggal'    => 'date',
        'panggil_at' => 'datetime',
        'selesai_at' => 'datetime',
    ];

    /** Status "dipanggil": sudah dipanggil, belum ditandai selesai. */
    public function isDipanggil(): bool
    {
        return $this->panggil_at !== null && $this->selesai_at === null;
    }

    /** Guard "tak bisa panggil baru sebelum yg lama selesai" - lihat Antrian::hasActiveCall(). */
    public static function hasActiveCall(string $counter): bool
    {
        return static::whereDate('tanggal', today())->where('counter', $counter)
            ->whereNotNull('panggil_at')->whereNull('selesai_at')->exists();
    }
}
