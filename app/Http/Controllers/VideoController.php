<?php

namespace App\Http\Controllers;

use App\Models\Clinic;
use App\Models\Video;
use Illuminate\Http\Request;

/** Kelola video promo layar tunggu. Hanya yang aktif diputar. Hanya admin. */
class VideoController extends Controller
{
    public function index()
    {
        $videos = Video::orderBy('sort')->orderBy('id')->get();
        // Peta target klinik per video (untuk tampilkan di UI).
        $targets = [];
        foreach ($videos as $v) {
            $targets[$v->id] = $v->clinicCodes();
        }

        return view('videos.index', [
            'videos'  => $videos,
            'clinics' => $this->clinics(),
            'targets' => $targets,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'judul'       => ['required', 'string', 'max:150'],
            'video'       => ['required', 'file', 'mimetypes:video/mp4,video/webm', 'max:262144'], // maks 256MB
            'clinic_mode' => ['required', 'in:all,pick'],
            'clinics'     => ['array'],
            'clinics.*'   => ['string', 'max:40'],
        ]);

        $dir = public_path('videos');
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $ext = strtolower($request->file('video')->getClientOriginalExtension() ?: 'mp4');
        $filename = 'video_'.now()->format('YmdHis').'_'.mt_rand(1000, 9999).'.'.$ext;
        $request->file('video')->move($dir, $filename);

        $video = Video::create([
            'judul'     => $request->input('judul'),
            'filename'  => $filename,
            'is_active' => false,
            'sort'      => (int) (Video::max('sort') ?? 0) + 1,
        ]);

        // "all" → tanpa target (tampil di semua klinik); "pick" → simpan pilihan.
        $video->syncClinics($request->input('clinic_mode') === 'pick' ? (array) $request->input('clinics', []) : []);

        return redirect()->route('videos.index')->with('ok', 'Video ditambahkan.');
    }

    /** Perbarui target klinik sebuah video. */
    public function updateClinics(Request $request, Video $video)
    {
        $request->validate([
            'clinic_mode' => ['required', 'in:all,pick'],
            'clinics'     => ['array'],
            'clinics.*'   => ['string', 'max:40'],
        ]);

        $video->syncClinics($request->input('clinic_mode') === 'pick' ? (array) $request->input('clinics', []) : []);

        return redirect()->route('videos.index')->with('ok', 'Target klinik "'.$video->judul.'" diperbarui.');
    }

    /** Daftar klinik aktif dari DB master untuk pilihan target. */
    private function clinics()
    {
        return Clinic::query()
            ->orderBy('service_unit_name')
            ->get(['service_unit_code', 'service_unit_name']);
    }

    /** Aktif/nonaktifkan video (hanya yang aktif diputar di layar tunggu). */
    public function toggle(Video $video)
    {
        $video->update(['is_active' => ! $video->is_active]);

        return redirect()->route('videos.index')
            ->with('ok', $video->is_active ? 'Video diaktifkan.' : 'Video dinonaktifkan.');
    }

    public function destroy(Video $video)
    {
        $file = public_path('videos/'.$video->filename);
        if ($video->filename && is_file($file)) {
            @unlink($file);
        }
        $video->delete();

        return redirect()->route('videos.index')->with('ok', 'Video dihapus.');
    }
}
