<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * READ-ONLY. Jadwal praktik dokter (per unit per hari) dari database Appointment.
 * day_number: 1=Senin … 7=Minggu. Window/sesi ada di start_time1..5 / end_time1..5.
 */
class DoctorSchedule extends Model
{
    protected $connection = 'master';
    protected $table = 'doctor_schedules';
    public $timestamps = false;

    protected $guarded = [];

    /** Relasi ke dokter (opsional, memudahkan query). */
    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'paramedic_id', 'paramedic_id');
    }
}
