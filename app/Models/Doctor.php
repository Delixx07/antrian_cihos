<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * READ-ONLY. Master dokter dari database aplikasi Appointment (mirror MEDINFRAS).
 * Jangan menulis - data ditimpa oleh sync di aplikasi Appointment.
 */
class Doctor extends Model
{
    protected $connection = 'master';
    protected $table = 'doctors';
    protected $primaryKey = 'paramedic_id';
    public $incrementing = false;
    protected $keyType = 'int';
    public $timestamps = false; // baca saja

    protected $guarded = [];
}
