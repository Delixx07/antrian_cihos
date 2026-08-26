<?php

namespace App\Http\Controllers;

use App\Models\Zone;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Kelola Zona Klinik (dulu di-hardcode di DisplayController::ZONE_NAMES/
 * ZONE_PAIRS) - nama zona + pasangan ruang per Client Display. Dipakai kartu
 * "Zona Klinik" di menu display & header "ZONE {code}" saat Main Display
 * dibuka per-zona. Hanya admin.
 */
class ZoneController extends Controller
{
    public function index()
    {
        return view('zones.index', [
            'zones' => Zone::orderBy('sort')->orderBy('code')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        Zone::create($data);

        return redirect()->route('zones.index')->with('ok', 'Zona berhasil ditambahkan.');
    }

    public function update(Request $request, Zone $zone)
    {
        $data = $this->validated($request, $zone->id);

        $zone->update($data);

        return redirect()->route('zones.index')->with('ok', 'Zona berhasil diperbarui.');
    }

    public function destroy(Zone $zone)
    {
        $zone->delete();

        return redirect()->route('zones.index')->with('ok', 'Zona dihapus.');
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'code'       => [
                'required', 'string', 'max:10',
                Rule::unique('zones', 'code')->ignore($ignoreId),
            ],
            'name'       => ['required', 'string', 'max:150'],
            'rooms_text' => ['nullable', 'string', 'max:2000'],
            'sort'       => ['nullable', 'integer'],
        ]);

        return [
            'code'  => $data['code'],
            'name'  => $data['name'],
            'rooms' => Zone::parseRoomsText($data['rooms_text'] ?? ''),
            'sort'  => $data['sort'] ?? 0,
        ];
    }
}
