<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/** Video promo untuk layar tunggu. File di public/videos. */
class Video extends Model
{
    /**
     * Koneksi ditetapkan EKSPLISIT: tanpa ini model memakai koneksi default
     * saat itu, dan bisa nyasar ke database lain (appointment/master) bila
     * ada model lain yang mengubah default dalam request yang sama.
     */
    protected $connection = 'mysql';

    protected $fillable = ['judul', 'filename', 'is_active', 'sort'];

    protected $casts = ['is_active' => 'boolean'];

    /** URL publik video. */
    public function url(): string
    {
        return asset('videos/'.$this->filename);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /** Kode klinik yang ditarget (kosong = SEMUA klinik). */
    public function clinicCodes(): array
    {
        return DB::table('video_clinics')->where('video_id', $this->id)
            ->pluck('service_unit_code')->all();
    }

    /** Simpan target klinik: array kode, atau [] = semua klinik. */
    public function syncClinics(array $codes): void
    {
        DB::table('video_clinics')->where('video_id', $this->id)->delete();
        $rows = collect($codes)->filter()->unique()->map(fn ($c) => [
            'video_id'          => $this->id,
            'service_unit_code' => $c,
            'created_at'        => now(),
            'updated_at'        => now(),
        ])->all();
        if ($rows) {
            DB::table('video_clinics')->insert($rows);
        }
    }

    /**
     * Batasi query ke media yang boleh tampil di sebuah klinik.
     *
     * Tanpa ?clinic= (layar umum) HANYA video "Semua Klinik" yang tampil —
     * video bertarget tidak boleh bocor ke layar klinik lain.
     * Dengan ?clinic=X: video "Semua Klinik" + video bertarget X.
     */
    public function scopeForClinic($query, ?string $code)
    {
        $noTarget = fn ($sub) => $sub->from('video_clinics')
            ->whereColumn('video_clinics.video_id', 'videos.id');

        if (! $code) {
            return $query->whereNotExists($noTarget);
        }

        return $query->where(function ($q) use ($code, $noTarget) {
            $q->whereNotExists($noTarget)
              ->orWhereExists(fn ($sub) => $sub->from('video_clinics')
                    ->whereColumn('video_clinics.video_id', 'videos.id')
                    ->where('video_clinics.service_unit_code', $code));
        });
    }
}
