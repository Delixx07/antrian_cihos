<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/** Banner gambar untuk layar tunggu. File di public/banners. */
class Banner extends Model
{
    /**
     * Koneksi ditetapkan EKSPLISIT: tanpa ini model memakai koneksi default
     * saat itu, dan bisa nyasar ke database lain (appointment/master) bila
     * ada model lain yang mengubah default dalam request yang sama.
     */
    protected $connection = 'mysql';

    protected $fillable = ['nama', 'image', 'is_active', 'sort'];

    protected $casts = ['is_active' => 'boolean'];

    /** URL publik gambar. */
    public function url(): string
    {
        return asset('banners/'.$this->image);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /** Kode klinik yang ditarget (kosong = SEMUA klinik). */
    public function clinicCodes(): array
    {
        return DB::table('banner_clinics')->where('banner_id', $this->id)
            ->pluck('service_unit_code')->all();
    }

    /** Simpan target klinik: array kode, atau [] = semua klinik. */
    public function syncClinics(array $codes): void
    {
        DB::table('banner_clinics')->where('banner_id', $this->id)->delete();
        $rows = collect($codes)->filter()->unique()->map(fn ($c) => [
            'banner_id'         => $this->id,
            'service_unit_code' => $c,
            'created_at'        => now(),
            'updated_at'        => now(),
        ])->all();
        if ($rows) {
            DB::table('banner_clinics')->insert($rows);
        }
    }

    /**
     * Batasi ke banner yang boleh tampil di sebuah klinik.
     *
     * Tanpa ?clinic= (layar umum) HANYA banner "Semua Klinik" yang tampil —
     * banner bertarget (mis. ANDROLOGY) tidak boleh bocor ke layar lain.
     * Dengan ?clinic=X: banner "Semua Klinik" + banner bertarget X.
     */
    public function scopeForClinic($query, ?string $code)
    {
        $noTarget = fn ($sub) => $sub->from('banner_clinics')
            ->whereColumn('banner_clinics.banner_id', 'banners.id');

        if (! $code) {
            return $query->whereNotExists($noTarget);
        }

        return $query->where(function ($q) use ($code, $noTarget) {
            $q->whereNotExists($noTarget)
              ->orWhereExists(fn ($sub) => $sub->from('banner_clinics')
                    ->whereColumn('banner_clinics.banner_id', 'banners.id')
                    ->where('banner_clinics.service_unit_code', $code));
        });
    }
}
