<?php

namespace App\Http\Controllers;

use App\Models\Clinic;
use App\Models\ClinicSetting;
use App\Services\ServiceUnitRoomRepository;
use Illuminate\Http\Request;

class ClinicController extends Controller
{
    public function index(Request $request, ServiceUnitRoomRepository $rooms)
    {
        $q = trim((string) $request->query('q', ''));

        $clinics = Clinic::query()
            ->when($q !== '', fn ($query) => $query->where(function ($w) use ($q) {
                $w->where('service_unit_name', 'like', "%{$q}%")
                    ->orWhere('service_unit_code', 'like', "%{$q}%");
            }))
            ->orderBy('service_unit_name')
            ->get();

        // Sumber zona/room: MEDINFRAS (vServiceUnitRoom) DULU; bila klinik tak ada
        // di sana, pakai data lokal (clinic_settings) yang diisi admin.
        $medRooms = $rooms->byClinic();                       // [code => {zone, rooms[]}]
        $settings = ClinicSetting::whereIn('service_unit_code', $clinics->pluck('service_unit_code'))
            ->get()->keyBy('service_unit_code');

        // Bentuk data tampil per klinik: prioritas MEDINFRAS, fallback lokal.
        $info = [];
        foreach ($clinics as $c) {
            $code = $c->service_unit_code;
            $med  = $medRooms[$code] ?? null;
            $loc  = $settings[$code] ?? null;

            if ($med && ($med['zone'] || $med['rooms'])) {
                // Ambil maks 5 room, pad ke 5 slot (null bila kurang).
                $r = array_slice(array_values($med['rooms']), 0, 5);
                $r = array_pad($r, 5, null);
                $info[$code] = ['source' => 'MEDINFRAS', 'zone' => $med['zone'], 'rooms' => $r];
            } else {
                $info[$code] = [
                    'source' => 'Lokal',
                    'zone'   => $loc?->zone_name ?: $loc?->zone_code,
                    'rooms'  => [
                        $loc?->room_code_1, $loc?->room_code_2, $loc?->room_code_3,
                        $loc?->room_code_4, $loc?->room_code_5,
                    ],
                ];
            }
        }

        return view('clinics.index', [
            'clinics'  => $clinics,
            'settings' => $settings,
            'info'     => $info,
            'q'        => $q,
        ]);
    }

    public function update(Request $request, string $code)
    {
        $data = $request->validate([
            'zone_code'   => ['nullable', 'string', 'max:50'],
            'zone_name'   => ['nullable', 'string', 'max:150'],
            'room_code_1' => ['nullable', 'string', 'max:50'],
            'room_code_2' => ['nullable', 'string', 'max:50'],
            'room_code_3' => ['nullable', 'string', 'max:50'],
            'room_code_4' => ['nullable', 'string', 'max:50'],
            'room_code_5' => ['nullable', 'string', 'max:50'],
        ]);

        ClinicSetting::updateOrCreate(['service_unit_code' => $code], $data);

        return redirect()->route('clinics.index')->with('ok', 'Data klinik disimpan.');
    }
}
