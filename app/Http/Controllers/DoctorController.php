<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\DoctorPhoto;
use App\Models\UserDetail;
use Illuminate\Http\Request;

class DoctorController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $doctors = Doctor::query()
            ->when($q !== '', fn ($query) => $query->where(function ($w) use ($q) {
                $w->where('paramedic_name', 'like', "%{$q}%")
                    ->orWhere('paramedic_code', 'like', "%{$q}%")
                    ->orWhere('specialty_name', 'like', "%{$q}%");
            }))
            ->orderBy('paramedic_name')
            ->get();

        $photos = DoctorPhoto::whereIn('paramedic_id', $doctors->pluck('paramedic_id'))
            ->get()->keyBy('paramedic_id');

        return view('doctors.index', ['doctors' => $doctors, 'photos' => $photos, 'q' => $q]);
    }

    public function updatePhoto(Request $request, int $id)
    {
        // Foto dikirim sebagai data URL hasil crop (Cropper.js → canvas 400x400 → JPEG base64).
        $request->validate([
            'cropped' => ['required', 'string', 'starts_with:data:image/'],
        ]);

        $doctor = Doctor::find($id);
        if (! $doctor) {
            return back()->with('error', 'Dokter tidak ditemukan.');
        }

        // Decode data URL: "data:image/jpeg;base64,....."
        if (! preg_match('/^data:image\/(jpeg|jpg|png|webp);base64,(.+)$/', $request->input('cropped'), $m)) {
            return back()->withErrors(['cropped' => 'Format gambar tidak valid.']);
        }
        $binary = base64_decode($m[2], true);
        if ($binary === false || strlen($binary) > 4 * 1024 * 1024) {
            return back()->withErrors(['cropped' => 'Gambar tidak valid atau terlalu besar.']);
        }

        // NIK dari direktori RS (dbuser) via kode dokter; fallback ke kode dokter.
        $nik = null;
        if ($doctor->paramedic_code) {
            $ud = rescue(fn () => UserDetail::where('user', $doctor->paramedic_code)->first(), null, false);
            $nik = $ud?->NIK;
        }
        $basename = $nik ?: ($doctor->paramedic_code ?: ('DR'.$doctor->paramedic_id));

        $dir = public_path('doctor-photos');
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        // Hapus file lama (bila ada) supaya tak menumpuk.
        $old = DoctorPhoto::find($id);
        if ($old && $old->filename && is_file($dir.'/'.$old->filename)) {
            @unlink($dir.'/'.$old->filename);
        }

        $ext = $m[1] === 'jpg' ? 'jpeg' : $m[1];
        $filename = $basename.'.'.$ext;
        file_put_contents($dir.'/'.$filename, $binary);

        DoctorPhoto::updateOrCreate(
            ['paramedic_id' => $id],
            ['nik' => $nik, 'filename' => $filename],
        );

        return redirect()->route('doctors.index')->with('ok', 'Foto dokter berhasil disimpan.');
    }
}
