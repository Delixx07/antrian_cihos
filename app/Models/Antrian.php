<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Antrian pasien lokal. Mengalir: KLINIK → KASIR → FARMASI → SELESAI.
 * Tiap tahap: tunggu → panggil → selesai. "Selesai" mendorong ke tahap
 * berikutnya (isi *_tunggu_at tahap selanjutnya).
 */
class Antrian extends Model
{
    /**
     * Koneksi ditetapkan EKSPLISIT: tanpa ini model memakai koneksi default
     * saat itu, dan bisa nyasar ke database lain (appointment/master) bila
     * ada model lain yang mengubah default dalam request yang sama.
     */
    protected $connection = 'mysql';

    protected $table = 'antrian';

    protected $guarded = ['id'];

    protected $casts = [
        'tanggal'            => 'date',
        'resep_clear'        => 'boolean',
        'transfer_at'        => 'datetime',
        'klinik_tunggu_at'   => 'datetime',
        'klinik_panggil_at'  => 'datetime',
        'klinik_selesai_at'  => 'datetime',
        'kasir_tunggu_at'    => 'datetime',
        'kasir_panggil_at'   => 'datetime',
        'kasir_selesai_at'   => 'datetime',
        'farmasi_tunggu_at'  => 'datetime',
        'farmasi_panggil_at' => 'datetime',
        'farmasi_selesai_at' => 'datetime',
    ];

    // Tahap
    public const TAHAP_KLINIK  = 'klinik';
    public const TAHAP_KASIR   = 'kasir';
    public const TAHAP_FARMASI = 'farmasi';
    public const TAHAP_SELESAI = 'selesai';

    // Status resep (ditentukan dokter saat transfer dari klinik)
    public const RESEP_NON      = 'non_resep';
    public const RESEP_RACIK    = 'racik';
    public const RESEP_NON_RACIK = 'non_racik';

    /** Label + warna status (untuk tabel). */
    public const RESEP_LABEL = [
        self::RESEP_NON       => 'Tanpa Resep',
        self::RESEP_RACIK     => 'Resep Racik',
        self::RESEP_NON_RACIK => 'Resep Non-Racik',
    ];

    // Farmasi jenis (alias status resep — racik/non_racik)
    public const FARMASI_RACIK     = 'racik';
    public const FARMASI_NON_RACIK = 'non_racik';

    /** Apakah pasien ini ber-resep (racik/non-racik)? */
    public function adaResep(): bool
    {
        return in_array($this->status_resep, [self::RESEP_RACIK, self::RESEP_NON_RACIK], true);
    }

    /** Label status untuk tabel: Tanpa Resep / Resep (Racik/Non) / Resep CLEAR. */
    public function statusLabel(): string
    {
        if ($this->resep_clear) {
            return 'Resep CLEAR';
        }

        return self::RESEP_LABEL[$this->status_resep] ?? '—';
    }

    /** Warna status: clear=hijau, ada resep=kuning, tanpa=abu. */
    public function statusWarna(): string
    {
        if ($this->resep_clear) {
            return 'clear';
        }

        return $this->adaResep() ? 'resep' : 'none';
    }

    /** Menit menunggu sejak transfer dari klinik. */
    public function menitTunggu(): int
    {
        $ref = $this->transfer_at ?? $this->klinik_selesai_at ?? $this->created_at;

        return $ref ? (int) $ref->diffInMinutes(now()) : 0;
    }

    // ---- Scope ----

    public function scopeToday($query)
    {
        return $query->whereDate('tanggal', today());
    }

    /** Antrian pada sebuah tahap (default hari ini). */
    public function scopeTahap($query, string $tahap)
    {
        return $query->where('tahap', $tahap);
    }

    /** Sedang menunggu di tahap ini (belum panggil / belum selesai). */
    public function scopeMenunggu($query, string $tahap)
    {
        return $query->where('tahap', $tahap)->whereNull("{$tahap}_selesai_at");
    }

    /**
     * Antrian FARMASI = pasien ber-resep yang BELUM clear, dengan jenis tertentu
     * (racik/non_racik). Farmasi bekerja berdasar STATUS RESEP, bukan tahap —
     * pasien tetap di tahap kasir sepanjang alur. Farmasi hanya melihat ini.
     */
    public function scopeFarmasiQueue($query, string $jenis)
    {
        return $query->where('farmasi_jenis', $jenis)
            ->whereIn('status_resep', [self::RESEP_RACIK, self::RESEP_NON_RACIK])
            ->where('resep_clear', false);
    }

    // ---- Status panggilan (untuk sebuah tahap) ----

    /** Status "dipanggil" di tahap: sudah panggil, belum selesai. */
    public function isDipanggil(string $tahap): bool
    {
        return $this->{"{$tahap}_panggil_at"} !== null && $this->{"{$tahap}_selesai_at"} === null;
    }

    // ---- Aksi alur ----

    /** Panggil pasien di tahap (set panggil_at + naikkan counter). */
    public function panggil(string $tahap, ?string $counter = null): void
    {
        $this->forceFill([
            "{$tahap}_panggil_at" => now(),
            'counter'             => $counter ?? $this->counter,
            'panggil_count'       => $this->panggil_count + 1,
        ])->save();
    }

    /** Ulangi panggilan (hanya naikkan counter, tak ubah tahap). */
    public function ulang(string $tahap): void
    {
        $this->forceFill([
            "{$tahap}_panggil_at" => now(),
            'panggil_count'       => $this->panggil_count + 1,
        ])->save();
    }

    /**
     * Selesaikan tahap. Alur BERCABANG (sesuai aplikasi lama): tiap tahap bisa
     * jadi ujung — mis. klinik selesai tanpa kasir, atau kasir selesai tanpa
     * farmasi. Penelusuran ke tahap berikutnya DITENTUKAN pemanggil lewat $next:
     *
     *   $next = null (default) → SELESAI TOTAL (tahap akhir untuk pasien ini).
     *   $next = 'kasir'|'farmasi' → dorong ke tahap itu (isi *_tunggu_at-nya).
     *
     * Contoh:
     *   Klinik selesai, tak perlu bayar   → selesaikan('klinik')            (selesai)
     *   Klinik selesai, perlu ke kasir    → selesaikan('klinik','kasir')
     *   Kasir selesai, tak ada resep      → selesaikan('kasir')             (selesai)
     *   Kasir selesai, ada resep          → selesaikan('kasir','farmasi')
     *   Farmasi selesai                   → selesaikan('farmasi')           (selesai/ujung)
     */
    public function selesaikan(string $tahap, ?string $next = null): void
    {
        $now = now();
        $data = ["{$tahap}_selesai_at" => $now];

        $validTargets = [self::TAHAP_KASIR, self::TAHAP_FARMASI];
        if ($next === null || ! in_array($next, $validTargets, true)) {
            // Ujung alur untuk pasien ini.
            $data['tahap'] = self::TAHAP_SELESAI;
        } else {
            $data['tahap'] = $next;
            $data["{$next}_tunggu_at"] = $now;   // masuk antrian tahap berikutnya
            $data['panggil_count'] = 0;          // reset hitung panggil untuk tahap baru
            $data['counter'] = null;             // counter di-set ulang oleh tahap berikutnya
        }

        $this->forceFill($data)->save();
    }

    // ---- Alur bisnis (resep) ----

    /**
     * KLINIK: dokter selesai → transfer pasien ke KASIR dengan status resep.
     * SEMUA pasien masuk tahap KASIR (kasir melihat SEMUA pasien). Namun:
     *   - Tanpa resep         → kasir langsung boleh memanggil.
     *   - Ada resep (blm clear) → kasir TAK boleh memanggil dulu. FARMASI yang
     *     melihat & memproses (berdasar status_resep, bukan tahap). Setelah
     *     farmasi selesai (resep_clear), barulah kasir boleh memanggil.
     * Waktu tunggu dihitung dari transfer klinik ini (transfer_at).
     * $statusResep: non_resep | racik | non_racik.
     */
    public function transferKeKasir(string $statusResep): void
    {
        $now = now();
        $adaResep = in_array($statusResep, [self::RESEP_RACIK, self::RESEP_NON_RACIK], true);

        $this->forceFill([
            'klinik_selesai_at' => $now,
            'transfer_at'       => $now,           // basis waktu tunggu (kasir & farmasi)
            'status_resep'      => $statusResep,
            'farmasi_jenis'     => $this->adaResepJenis($statusResep), // racik/non_racik utk farmasi
            'tahap'             => self::TAHAP_KASIR,
            'kasir_tunggu_at'   => $now,
            // Pasien ber-resep juga langsung "menunggu" di farmasi (waktu = transfer).
            'farmasi_tunggu_at' => $adaResep ? $now : null,
            'panggil_count'     => 0,
            'counter'           => null,
        ])->save();
    }

    private function adaResepJenis(string $statusResep): ?string
    {
        return in_array($statusResep, [self::RESEP_RACIK, self::RESEP_NON_RACIK], true)
            ? $statusResep : null;
    }

    /**
     * KASIR selesai memanggil → SELESAI TOTAL. Kasir hanya bisa memanggil pasien
     * tanpa resep, atau pasien ber-resep yang SUDAH clear dari farmasi — jadi
     * saat kasir menyelesaikan, alur untuk pasien ini memang berakhir.
     */
    public function selesaiKasir(): void
    {
        $this->forceFill([
            'kasir_selesai_at' => now(),
            'tahap'            => self::TAHAP_SELESAI,
        ])->save();
    }

    /**
     * FARMASI selesai memproses resep → status "Resep CLEAR". Pasien TETAP di
     * tahap KASIR (tak pernah pindah tahap) — kini kasir boleh memanggilnya
     * untuk pembayaran. Farmasi bekerja "di samping" alur kasir.
     */
    public function selesaiFarmasi(): void
    {
        // Pasien tetap di tahap KASIR — hanya tandai resep clear + waktu selesai
        // farmasi. Tak menyentuh field kasir (panggil/counter) supaya antrian
        // kasir tak teracak; kasir kini boleh memanggilnya (guard resep lolos).
        $this->forceFill([
            'farmasi_selesai_at' => now(),
            'resep_clear'        => true,
        ])->save();
    }

    /**
     * TARIK pasien kembali ke KLINIK (dokter memanggil ulang pasien yang sudah
     * lanjut ke kasir/farmasi). Membatalkan SELURUH jejak alur hilir (kasir,
     * farmasi, resep) supaya pasien TIDAK tampil dobel di display farmasi/kasir.
     * Kebalikan dari transferKeKasir(). Setelah ini dokter menyelesaikan lagi &
     * memilih status resep → pasien masuk kembali ke kasir/farmasi seperti biasa.
     */
    public function tarikKeKlinik(): void
    {
        $this->forceFill([
            'tahap'              => self::TAHAP_KLINIK,
            'klinik_selesai_at'  => null,
            // Batalkan transfer & seluruh jejak kasir.
            'transfer_at'        => null,
            'kasir_tunggu_at'    => null,
            'kasir_panggil_at'   => null,
            'kasir_selesai_at'   => null,
            // Batalkan resep & seluruh jejak farmasi (inilah yg bikin pasien
            // "nyangkut" di display farmasi bila tak direset).
            'status_resep'       => null,
            'farmasi_jenis'      => null,
            'resep_clear'        => false,
            'farmasi_tunggu_at'  => null,
            'farmasi_panggil_at' => null,
            'farmasi_selesai_at' => null,
            'counter'            => null,
        ])->save();
    }
}
