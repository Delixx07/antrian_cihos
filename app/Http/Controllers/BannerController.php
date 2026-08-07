<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Clinic;
use Illuminate\Http\Request;

/** Kelola banner (gambar promo) layar tunggu. Hanya admin. */
class BannerController extends Controller
{
    public function index()
    {
        $banners = Banner::orderBy('sort')->orderBy('id')->get();
        $targets = [];
        foreach ($banners as $b) {
            $targets[$b->id] = $b->clinicCodes();
        }

        return view('banners.index', [
            'banners' => $banners,
            'clinics' => Clinic::orderBy('service_unit_name')->get(['service_unit_code', 'service_unit_name']),
            'targets' => $targets,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama'        => ['required', 'string', 'max:150'],
            'image'       => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'clinic_mode' => ['required', 'in:all,pick'],
            'clinics'     => ['array'],
            'clinics.*'   => ['string', 'max:40'],
        ]);

        $dir = public_path('banners');
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $ext = strtolower($request->file('image')->getClientOriginalExtension() ?: 'jpg');
        $filename = 'banner_'.now()->format('YmdHis').'_'.mt_rand(1000, 9999).'.'.$ext;
        $request->file('image')->move($dir, $filename);

        $banner = Banner::create([
            'nama'  => $request->input('nama'),
            'image' => $filename,
            'sort'  => (int) (Banner::max('sort') ?? 0) + 1,
        ]);

        $banner->syncClinics($request->input('clinic_mode') === 'pick' ? (array) $request->input('clinics', []) : []);

        return redirect()->route('banners.index')->with('ok', 'Banner ditambahkan.');
    }

    /** Perbarui target klinik sebuah banner. */
    public function updateClinics(Request $request, Banner $banner)
    {
        $request->validate([
            'clinic_mode' => ['required', 'in:all,pick'],
            'clinics'     => ['array'],
            'clinics.*'   => ['string', 'max:40'],
        ]);

        $banner->syncClinics($request->input('clinic_mode') === 'pick' ? (array) $request->input('clinics', []) : []);

        return redirect()->route('banners.index')->with('ok', 'Target klinik "'.$banner->nama.'" diperbarui.');
    }

    /** Aktifkan / nonaktifkan banner (hanya yang aktif tampil di layar). */
    public function toggle(Banner $banner)
    {
        $banner->update(['is_active' => ! $banner->is_active]);

        return redirect()->route('banners.index')
            ->with('ok', $banner->is_active ? 'Banner diaktifkan.' : 'Banner dinonaktifkan.');
    }

    public function destroy(Banner $banner)
    {
        $file = public_path('banners/'.$banner->image);
        if ($banner->image && is_file($file)) {
            @unlink($file);
        }
        $banner->delete();

        return redirect()->route('banners.index')->with('ok', 'Banner dihapus.');
    }
}
