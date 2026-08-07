<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * READ-ONLY. Peta ruangan → prefix nomor tiket antrian, dari database Appointment
 * (mirror MEDINFRAS). Mis. room_code tertentu → prefix "FD"/"CA".
 */
class RoomTicketPrefix extends Model
{
    protected $connection = 'master';
    protected $table = 'room_ticket_prefixes';
    public $timestamps = false;

    protected $guarded = [];
}
