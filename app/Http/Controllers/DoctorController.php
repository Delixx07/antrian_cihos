<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\DoctorPhoto;
use App\Models\UserDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

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

        // URL foto EFEKTIF per dokter: upload lewat app kalau ada, else fallback
        // ke public/img-dokter (foto lama, dicocokkan by nama) - lihat
        // DoctorPhoto::urlFor().
        $photos = $doctors->mapWithKeys(fn ($d) => [
            $d->paramedic_id => DoctorPhoto::urlFor($d->paramedic_id, $d->paramedic_name),
        ]);

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

        // NIK dari direktori RS (dbuser) via kode dokter - tetap dicatat di
        // kolom `nik` sbg referensi, TAPI nama file sekarang pakai nama dokter
        // (sama gayanya dgn foto lama di public/img-dokter), bukan NIK lagi.
        $nik = null;
        if ($doctor->paramedic_code) {
            $ud = rescue(fn () => UserDetail::where('user', $doctor->paramedic_code)->first(), null, false);
            $nik = $ud?->NIK;
        }
        $basename = $this->sanitizeFilename($doctor->paramedic_name) ?: ($doctor->paramedic_code ?: ('DR'.$doctor->paramedic_id));

        // Foto upload disimpan LANGSUNG di public/img-dokter, satu folder yang
        // sama dengan foto lama - nama file = nama dokter (sama gayanya).
        $dir = public_path('img-dokter');
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

        // Index nama file img-dokter (dipakai fallback pencocokan) jadi basi begitu
        // ada file baru masuk folder ini - refresh biar dokter LAIN yang belum
        // pernah upload tetap dapat pencocokan yang akurat.
        Cache::forget('doctor_photos:img_dokter_index');

        return redirect()->route('doctors.index')->with('ok', 'Foto dokter berhasil disimpan.');
    }

    /** Buang karakter yang dilarang di nama file Windows dari nama dokter. */
    private function sanitizeFilename(string $name): string
    {
        $clean = preg_replace('/[<>:"\/\\\\|?*\x00-\x1F]/', '', $name) ?? $name;

        return trim($clean, " .");
    }
}
