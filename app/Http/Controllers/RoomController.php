<?php

namespace App\Http\Controllers;

use App\Models\AntrianAccess;
use App\Services\RoomOccupancy;
use Illuminate\Http\Request;

/**
 * Pemilihan RUANG untuk dokter yang punya >1 ruang praktik hari ini.
 * Alur: login → (bila banyak ruang) redirect ke sini → pilih → tempati →
 * lanjut ke antrian. Menerapkan aturan 1 dokter/ruang (blokir bentrok).
 */
class RoomController extends Controller
{
    public function choose(Request $request, RoomOccupancy $occ)
    {
        $rooms = (array) $request->session()->get('room_choices', []);
        if (empty($rooms)) {
            return redirect()->route('home');
        }

        // Tandai ruang yang sudah ditempati dokter lain (untuk ditandai di UI).
        $pid = (int) $request->session()->get('paramedic_id', 0);
        $occupied = [];
        foreach ($rooms as $r) {
            $o = rescue(fn () => $occ->occupantOf($r, $pid), null, false);
            if ($o) {
                $occupied[$r] = $o->paramedic_name ?: $o->name ?: 'dokter lain';
            }
        }

        return view('auth.pilih-ruang', [
            'rooms'    => $rooms,
            'occupied' => $occupied,
        ]);
    }

    public function pick(Request $request, RoomOccupancy $occ)
    {
        $data = $request->validate(['room_code' => ['required', 'string', 'max:50']]);
        $room = $data['room_code'];

        $rooms = (array) $request->session()->get('room_choices', []);
        if (! in_array($room, $rooms, true)) {
            return redirect()->route('room.choose')->withErrors(['room_code' => 'Ruang tidak valid.']);
        }

        $username = $request->session()->get('user');
        $access = $username ? AntrianAccess::forUser($username) : null;
        if (! $access) {
            return redirect()->route('login');
        }

        $pid = (int) $access->paramedic_id;
        $occupant = rescue(fn () => $occ->occupantOf($room, $pid), null, false);
        if ($occupant) {
            return redirect()->route('room.choose')->with('room_conflict', [
                'room'   => $room,
                'doctor' => $occupant->paramedic_name ?: $occupant->name ?: 'dokter lain',
            ]);
        }

        rescue(fn () => $occ->occupy($access, $room), null, false);
        $request->session()->put('room_code', $room);
        $request->session()->forget('room_choices');

        return redirect()->route('home');
    }
}
