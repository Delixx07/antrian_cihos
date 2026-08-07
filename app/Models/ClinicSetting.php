<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Data pelengkap klinik (zona + room code) yang dikelola lokal di aplikasi
 * antrian. Di-key oleh kode klinik (service_unit_code). DB utama antrian.
 */
class ClinicSetting extends Model
{
    /**
     * Koneksi ditetapkan EKSPLISIT: tanpa ini model memakai koneksi default
     * saat itu, dan bisa nyasar ke database lain (appointment/master) bila
     * ada model lain yang mengubah default dalam request yang sama.
     */
    protected $connection = 'mysql';

    protected $table = 'clinic_settings';
    protected $primaryKey = 'service_unit_code';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'service_unit_code', 'zone_code', 'zone_name',
        'room_code_1', 'room_code_2', 'room_code_3', 'room_code_4', 'room_code_5',
    ];
}
