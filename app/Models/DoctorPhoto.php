<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Foto dokter (lokal antrian), di-key paramedic_id. Nama file = NIK. */
class DoctorPhoto extends Model
{
    /**
     * Koneksi ditetapkan EKSPLISIT: tanpa ini model memakai koneksi default
     * saat itu, dan bisa nyasar ke database lain (appointment/master) bila
     * ada model lain yang mengubah default dalam request yang sama.
     */
    protected $connection = 'mysql';

    protected $table = 'doctor_photos';
    protected $primaryKey = 'paramedic_id';
    public $incrementing = false;
    protected $keyType = 'int';

    protected $fillable = ['paramedic_id', 'nik', 'filename'];

    /** URL publik foto. */
    public function url(): string
    {
        return asset('doctor-photos/'.$this->filename);
    }
}
