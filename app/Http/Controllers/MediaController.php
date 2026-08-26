<?php

namespace App\Http\Controllers;

use App\Http\Controllers\DisplayController;
use App\Models\Banner;
use App\Models\Clinic;
use App\Models\Setting;
use App\Models\Video;
use Illuminate\Http\Request;

/**
 * Halaman gabungan "Media Layar Tunggu" - mengelola Video & Banner dalam satu
 * halaman (tab). Aksi simpan/hapus/toggle/target-klinik tetap memakai route
 * videos.* dan banners.* yang sudah ada (VideoController & BannerController).
 * Controller ini hanya menyusun data untuk halaman gabungan.
 */
class MediaController extends Controller
{
    public function index()
    {
        $videos  = Video::orderBy('sort')->orderBy('id')->get();
        $banners = Banner::orderBy('sort')->orderBy('id')->get();

        $videoTargets = [];
        foreach ($videos as $v) {
            $videoTargets[$v->id] = $v->clinicCodes();
        }
        $bannerTargets = [];
        foreach ($banners as $b) {
            $bannerTargets[$b->id] = $b->clinicCodes();
        }

        return view('media.index', [
            'videos'        => $videos,
            'banners'       => $banners,
            'clinics'       => Clinic::orderBy('service_unit_name')->get(['service_unit_code', 'service_unit_name']),
            'videoTargets'  => $videoTargets,
            'bannerTargets' => $bannerTargets,
            'runningText'   => Setting::get('running_text', DisplayController::RUNNING_TEXT_DEFAULT),
        ]);
    }

    public function updateRunningText(Request $request)
    {
        $data = $request->validate([
            'running_text' => ['required', 'string', 'max:500'],
        ]);

        Setting::set('running_text', trim($data['running_text']));

        return back()->with('ok', 'Teks berjalan berhasil disimpan.');
    }
}
