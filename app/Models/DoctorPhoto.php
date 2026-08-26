<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Foto dokter, di-key paramedic_id. SATU folder untuk semua foto:
 * public/img-dokter - baik foto lama yang dimuat manual (nama file bervariasi,
 * "dr. Nama, Sp.X.JPG" atau "Nama, dr., Sp.X.JPG" dst) maupun foto yang
 * di-upload lewat app ini (nama file = nama dokter, lihat
 * DoctorController::updatePhoto()).
 *
 * Baris di tabel ini HANYA ada untuk dokter yang sudah pernah di-upload lewat
 * app (jadi filename-nya pasti akurat, tercatat di DB). Dokter yang belum
 * pernah upload TIDAK punya baris di sini - fotonya (kalau ada di
 * img-dokter) dicari via pencocokan nama yang toleran (lihat legacyUrl()),
 * bukan exact match, karena nama filenya tak selalu identik ke paramedic_name.
 */
class DoctorPhoto extends Model
{
    /**
     * Koneksi ditetapkan EKSPLISIT: tanpa ini model memakai koneksi default
     * saat itu, dan bisa nyasar ke database lain (appointment/master) bila
     * ada model lain yang mengubah default dalam request yang sama.
     */
    protected $connection = 'mysql';

    protected $table = 'doctor_photos';
    protected $primaryKey = 'paramedic_id';
    public $incrementing = false;
    protected $keyType = 'int';

    protected $fillable = ['paramedic_id', 'nik', 'filename'];

    /** URL publik foto yang di-upload lewat app (folder img-dokter, sama dengan foto lama). */
    public function url(): string
    {
        return asset('img-dokter/'.rawurlencode($this->filename)).'?v='.$this->updated_at?->timestamp;
    }

    /**
     * URL foto EFEKTIF utk seorang dokter: upload lewat app (kalau ada) →
     * fallback foto lama di img-dokter (kalau cocok namanya) → null (biar
     * pemanggil tampilkan placeholder inisial).
     */
    public static function urlFor(int $paramedicId, ?string $paramedicName): ?string
    {
        $own = static::find($paramedicId);
        if ($own) {
            return $own->url();
        }

        return $paramedicName ? static::legacyUrl($paramedicName) : null;
    }

    /**
     * Cari foto yang cocok di public/img-dokter berdasarkan nama dokter.
     * Nama filenya TIDAK selalu identik ke paramedic_name (urutan "dr."
     * berbeda, gelar tambahan kadang absen di salah satu sisi, dst) - jadi
     * dibandingkan versi "dinormalkan" (huruf besar/kecil, tanda baca,
     * spasi, kata "dr/drg/prof" semua dibuang), exact match dulu, baru
     * dicoba prefix match (utk kasus file tanpa gelar tambahan di paling
     * belakang, mis. file "...Sp.BTKV" vs DB "...Sp.BTKV, FIATCVS").
     */
    private static function legacyUrl(string $paramedicName): ?string
    {
        $index = static::legacyIndex();
        $needle = static::normalizeName($paramedicName);
        if ($needle === '') {
            return null;
        }

        if (isset($index[$needle])) {
            return asset('img-dokter/'.rawurlencode($index[$needle]));
        }

        // Prefix match dua arah, cuma dipakai kalau string-nya cukup panjang
        // (hindari kecocokan ngawur antar dokter yang namanya kebetulan mirip
        // di awal saja).
        foreach ($index as $key => $filename) {
            if (strlen($needle) < 10 || strlen($key) < 10) {
                continue;
            }
            if (str_starts_with($key, $needle) || str_starts_with($needle, $key)) {
                return asset('img-dokter/'.rawurlencode($filename));
            }
        }

        return null;
    }

    /** [nama_dinormalkan => nama_file_asli] utk semua file di public/img-dokter, di-cache. */
    private static function legacyIndex(): array
    {
        return Cache::remember('doctor_photos:img_dokter_index', 3600, function () {
            $dir = public_path('img-dokter');
            if (! is_dir($dir)) {
                return [];
            }

            $index = [];
            foreach (scandir($dir) ?: [] as $file) {
                if ($file === '.' || $file === '..' || ! is_file($dir.'/'.$file)) {
                    continue;
                }
                $name = pathinfo($file, PATHINFO_FILENAME);
                $key = static::normalizeName($name);
                if ($key !== '') {
                    $index[$key] = $file;
                }
            }

            return $index;
        });
    }

    /** Lowercase, buang kata sapaan (dr/drg/prof) & semua karakter non-alfanumerik. */
    private static function normalizeName(string $name): string
    {
        $name = preg_replace('/\b(dr|drg|prof)\b\.?/i', '', $name) ?? $name;

        return strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $name) ?? '');
    }
}
