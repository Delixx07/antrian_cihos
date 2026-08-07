<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * READ-ONLY. Klinik / unit layanan dari database Appointment (mirror MEDINFRAS).
 */
class Clinic extends Model
{
    protected $connection = 'master';
    protected $table = 'clinics';
    protected $primaryKey = 'service_unit_code';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $guarded = [];
    protected $casts = ['synced_at' => 'datetime'];
}
