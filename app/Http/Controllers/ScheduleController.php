<?php

namespace App\Http\Controllers;

use App\Models\DoctorSchedule;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    private const DAYS = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $rows = DoctorSchedule::query()
            ->when($q !== '', fn ($query) => $query->where(function ($w) use ($q) {
                $w->where('service_unit_name', 'like', "%{$q}%")
                    ->orWhere('service_unit_code', 'like', "%{$q}%")
                    ->orWhere('paramedic_code', 'like', "%{$q}%");
            }))
            ->orderBy('day_number')
            ->orderBy('service_unit_name')
            ->get();

        // Nama dokter (join manual ke tabel doctors via paramedic_id).
        $names = \App\Models\Doctor::whereIn('paramedic_id', $rows->pluck('paramedic_id')->unique())
            ->pluck('paramedic_name', 'paramedic_id');

        return view('schedules.index', [
            'rows'  => $rows,
            'names' => $names,
            'days'  => self::DAYS,
            'q'     => $q,
        ]);
    }
}
