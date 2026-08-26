<?php

namespace Tests\Unit;

use App\Models\Antrian;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Antrian::hasActiveCall() adalah guard "tak bisa panggil pasien baru sebelum
 * yang sekarang selesai" - dipakai Klinik/Kasir/Farmasi. Bug ini pernah lolos
 * ke produksi sebelum guard-nya ada (screenshot: beberapa nomor "Sedang
 * Dipanggil" sekaligus), jadi logic ini butuh jaring pengaman otomatis.
 */
class AntrianHasActiveCallTest extends TestCase
{
    use RefreshDatabase;

    private function makeAntrian(array $overrides = []): Antrian
    {
        return Antrian::create(array_merge([
            'tanggal'         => today(),
            'no_antrian'      => 'TST'.random_int(1000, 9999),
            'appointment_no'  => 'APT'.random_int(100000, 999999),
            'tahap'           => Antrian::TAHAP_KLINIK,
            'paramedic_id'    => 1,
            'counter'         => 'Counter 1',
            'panggil_count'   => 0,
        ], $overrides));
    }

    public function test_tidak_ada_panggilan_aktif_saat_belum_pernah_dipanggil(): void
    {
        $this->makeAntrian(); // klinik_panggil_at masih null

        $this->assertFalse(
            Antrian::hasActiveCall('klinik', 'paramedic_id', 1, Antrian::TAHAP_KLINIK)
        );
    }

    public function test_dipanggil_tapi_belum_selesai_dianggap_aktif(): void
    {
        $this->makeAntrian([
            'klinik_panggil_at' => now(),
            'klinik_selesai_at' => null,
        ]);

        $this->assertTrue(
            Antrian::hasActiveCall('klinik', 'paramedic_id', 1, Antrian::TAHAP_KLINIK)
        );
    }

    public function test_yang_sudah_selesai_tidak_lagi_dianggap_aktif(): void
    {
        $this->makeAntrian([
            'klinik_panggil_at' => now()->subMinutes(5),
            'klinik_selesai_at' => now(),
        ]);

        $this->assertFalse(
            Antrian::hasActiveCall('klinik', 'paramedic_id', 1, Antrian::TAHAP_KLINIK)
        );
    }

    public function test_dokter_lain_tidak_ikut_terblokir(): void
    {
        // Dokter #1 sedang aktif memanggil...
        $this->makeAntrian([
            'paramedic_id'      => 1,
            'klinik_panggil_at' => now(),
        ]);

        // ...tapi dokter #2 harus tetap bebas memanggil pasiennya sendiri.
        $this->assertFalse(
            Antrian::hasActiveCall('klinik', 'paramedic_id', 2, Antrian::TAHAP_KLINIK)
        );
    }

    public function test_tahap_filter_mencegah_kebocoran_lintas_tahap(): void
    {
        // Pasien yang sudah pindah ke tahap kasir (klinik_panggil_at masih
        // terisi dari riwayat) TIDAK boleh dianggap "aktif di klinik" lagi.
        $this->makeAntrian([
            'tahap'             => Antrian::TAHAP_KASIR,
            'klinik_panggil_at' => now()->subHour(),
            'klinik_selesai_at' => now()->subMinutes(50),
        ]);

        $this->assertFalse(
            Antrian::hasActiveCall('klinik', 'paramedic_id', 1, Antrian::TAHAP_KLINIK)
        );
    }

    public function test_farmasi_tidak_difilter_tahap_karena_pasien_tetap_di_tahap_kasir(): void
    {
        // Farmasi sengaja TIDAK memfilter kolom `tahap` (lihat komentar di
        // Antrian::hasActiveCall() & DisplayController::areaQuery()) - pasien
        // farmasi tetap tahap=kasir sepanjang alur.
        $this->makeAntrian([
            'tahap'              => Antrian::TAHAP_KASIR,
            'counter'            => 'Farmasi Racik',
            'farmasi_panggil_at' => now(),
        ]);

        $this->assertTrue(
            Antrian::hasActiveCall('farmasi', 'counter', 'Farmasi Racik')
        );
    }

    public function test_counter_berbeda_tidak_ikut_terblokir(): void
    {
        $this->makeAntrian([
            'tahap'             => Antrian::TAHAP_KASIR,
            'counter'           => 'Counter 1',
            'kasir_panggil_at'  => now(),
        ]);

        $this->assertFalse(
            Antrian::hasActiveCall('kasir', 'counter', 'Counter 2', Antrian::TAHAP_KASIR)
        );
    }

    public function test_hanya_menghitung_hari_ini(): void
    {
        // Antrian::today() harus mengecualikan baris kemarin walau kolom lain cocok.
        $this->makeAntrian([
            'tanggal'           => today()->subDay(),
            'klinik_panggil_at' => now(),
        ]);

        $this->assertFalse(
            Antrian::hasActiveCall('klinik', 'paramedic_id', 1, Antrian::TAHAP_KLINIK)
        );
    }
}
