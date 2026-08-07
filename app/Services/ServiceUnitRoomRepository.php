<?php

namespace App\Services;

use App\Database\OdbcSqlServer;

/**
 * Baca zona & room per klinik dari SQL Server MEDINFRAS (vServiceUnitRoom).
 * Filter: ClassName='OPD' AND IsDeleted='0', kecuali Oncology & Delivery Room.
 * Zona diekstrak dari RoomName (format "1101 (Zona 11)").
 */
class ServiceUnitRoomRepository
{
    /**
     * @return array<string, array{zone:?string, rooms:array<int,string>}>
     *         key = ServiceUnitCode → { zone, rooms[] (kode ruangan) }
     */
    public function byClinic(): array
    {
        $rows = rescue(fn () => OdbcSqlServer::cached(
            "SELECT ServiceUnitCode, ServiceUnitName, RoomCode, RoomName
             FROM vServiceUnitRoom
             WHERE ClassName = ? AND IsDeleted = ?
               AND ServiceUnitName NOT LIKE ?
               AND ServiceUnitName NOT LIKE ?
             ORDER BY ServiceUnitCode, RoomCode",
            ['OPD', '0', '%Oncology%', '%Delivery Room%'],
            300
        ), [], false);

        $out = [];
        foreach ($rows as $r) {
            $code = (string) ($r['ServiceUnitCode'] ?? '');
            if ($code === '') {
                continue;
            }
            $out[$code] ??= ['zone' => null, 'rooms' => []];

            // Kode ruangan (unik, urut).
            $roomCode = trim((string) ($r['RoomCode'] ?? ''));
            if ($roomCode !== '' && ! in_array($roomCode, $out[$code]['rooms'], true)) {
                $out[$code]['rooms'][] = $roomCode;
            }

            // Zona dari RoomName: "1101 (Zona 11)" → "Zona 11".
            if ($out[$code]['zone'] === null && preg_match('/\(([^)]*Zona[^)]*)\)/i', (string) ($r['RoomName'] ?? ''), $m)) {
                $out[$code]['zone'] = trim($m[1]);
            }
        }

        return $out;
    }
}
