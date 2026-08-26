<?php

namespace App\Services;

use App\Models\AntrianAccess;
use App\Models\DoctorSchedule;
use Illuminate\Support\Carbon;

/**
 * Menempatkan dokter ke RUANG saat login (mirip islogin di sistem lama):
 *  - Ruang diambil dari JADWAL hari ini (doctor_schedules) untuk dokter itu.
 *  - Aturan 1 dokter/ruang: bila ruang sudah ditempati dokter LAIN yang masih
 *    aktif hari ini (room_occupied_at = hari ini), penempatan DITOLAK (bentrok).
 *
 * Semua query dibungkus rescue di pemanggil supaya tak memblokir login bila
 * DB master lambat.
 */
class RoomOccupancy
{
    /**
     * Kode ruang tempat dokter praktik HARI INI (unik, urut).
     * @return array<int,string>
     */
    public function roomsToday(int $paramedicId): array
    {
        if ($paramedicId <= 0) {
            return [];
        }

        $dow = (int) Carbon::now()->isoWeekday(); // 1=Senin..7=Minggu

        return DoctorSchedule::where('paramedic_id', $paramedicId)
            ->where('day_number', $dow)
            ->whereNotNull('room_code')
            ->where('room_code', '<>', '')
            ->orderBy('room_code')
            ->pluck('room_code')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Dokter LAIN yang sedang menempati ruang ini hari ini (bentrok), atau null.
     */
    public function occupantOf(string $roomCode, int $exceptParamedicId = 0): ?AntrianAccess
    {
        if ($roomCode === '') {
            return null;
        }

        return AntrianAccess::where('room_code', $roomCode)
            ->whereNotNull('room_occupied_at')
            ->whereDate('room_occupied_at', Carbon::today())
            ->when($exceptParamedicId > 0, fn ($q) => $q->where('paramedic_id', '<>', $exceptParamedicId))
            ->orderByDesc('room_occupied_at')
            ->first();
    }

    /**
     * Tempatkan dokter ke ruang (set room_code + room_occupied_at = sekarang).
     */
    public function occupy(AntrianAccess $access, string $roomCode): void
    {
        $access->forceFill([
            'room_code'        => $roomCode,
            'room_occupied_at' => Carbon::now(),
        ])->save();
    }

    /**
     * Lepas ruang (dipanggil saat logout). Hanya mengosongkan penanda,
     * room_code dibiarkan (histori) - occupancy ditentukan room_occupied_at.
     */
    public function release(AntrianAccess $access): void
    {
        if ($access->room_occupied_at !== null) {
            $access->forceFill(['room_occupied_at' => null])->save();
        }
    }
}
