<?php

namespace Tests\Feature;

use App\Models\Antrian;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sama seperti KasirDoubleCallGuardTest, tapi untuk konsol Farmasi. Farmasi
 * TIDAK difilter kolom `tahap` (pasien tetap tahap=kasir sepanjang alur) -
 * lihat komentar Antrian::hasActiveCall() & areaQuery() - jadi fixture di
 * sini SENGAJA pakai tahap=kasir, bukan tahap=farmasi.
 */
class FarmasiDoubleCallGuardTest extends TestCase
{
    use RefreshDatabase;

    private function loginAsFarmasi(string $counter = 'Farmasi Racik', string $jenis = Antrian::FARMASI_RACIK): void
    {
        $this->withSession([
            'auth'            => true,
            'user'            => 'farmasi1',
            'role'            => 'farmasi',
            'farmasi_counter' => $counter,
            'farmasi_jenis'   => $jenis,
        ]);
    }

    private function makeResep(array $overrides = []): Antrian
    {
        return Antrian::create(array_merge([
            'tanggal'        => today(),
            'no_antrian'     => 'FD'.random_int(100, 999),
            'appointment_no' => 'APT'.random_int(100000, 999999),
            'tahap'          => Antrian::TAHAP_KASIR,
            'status_resep'   => Antrian::RESEP_RACIK,
            'farmasi_jenis'  => Antrian::FARMASI_RACIK,
            'resep_clear'    => false,
        ], $overrides));
    }

    public function test_petugas_bisa_memanggil_resep_pertama(): void
    {
        $this->loginAsFarmasi();
        $resep = $this->makeResep();

        $response = $this->put(route('farmasi.panggil-satu', $resep));

        $response->assertSessionHas('ok');
        $this->assertNotNull($resep->fresh()->farmasi_panggil_at);
    }

    public function test_petugas_tidak_bisa_memanggil_resep_kedua_sebelum_yang_pertama_selesai(): void
    {
        $this->loginAsFarmasi();
        $resep1 = $this->makeResep();
        $resep2 = $this->makeResep();

        $this->put(route('farmasi.panggil-satu', $resep1));
        $response = $this->put(route('farmasi.panggil-satu', $resep2));

        $response->assertSessionHas('error');
        $this->assertNull($resep2->fresh()->farmasi_panggil_at);
    }

    public function test_bisa_memanggil_resep_baru_setelah_yang_lama_selesai(): void
    {
        $this->loginAsFarmasi();
        $resep1 = $this->makeResep();
        $resep2 = $this->makeResep();

        $this->put(route('farmasi.panggil-satu', $resep1));
        $this->put(route('farmasi.selesai', $resep1));

        $response = $this->put(route('farmasi.panggil-satu', $resep2));

        $response->assertSessionHas('ok');
        $this->assertNotNull($resep2->fresh()->farmasi_panggil_at);
    }

    public function test_counter_lain_tidak_ikut_terblokir(): void
    {
        $resepA = $this->makeResep();
        $resepB = $this->makeResep();

        $this->loginAsFarmasi('Farmasi Racik 1');
        $this->put(route('farmasi.panggil-satu', $resepA));

        $this->loginAsFarmasi('Farmasi Racik 2');
        $response = $this->put(route('farmasi.panggil-satu', $resepB));

        $response->assertSessionHas('ok');
        $this->assertNotNull($resepB->fresh()->farmasi_panggil_at);
    }

    public function test_resep_jenis_berbeda_ditolak(): void
    {
        $this->loginAsFarmasi('Farmasi Racik', Antrian::FARMASI_RACIK);
        $resepNonRacik = $this->makeResep([
            'status_resep'  => Antrian::RESEP_NON_RACIK,
            'farmasi_jenis' => Antrian::FARMASI_NON_RACIK,
        ]);

        $response = $this->put(route('farmasi.panggil-satu', $resepNonRacik));

        $response->assertSessionHas('error');
        $this->assertNull($resepNonRacik->fresh()->farmasi_panggil_at);
    }

    /**
     * Panel "Sedang Dilayani" menampilkan resep LINTAS counter, tapi
     * Recall/Selesai harus tetap hanya boleh dari counter yang memanggilnya -
     * sebelumnya FarmasiController::ulang()/selesai() tidak mengecek ini,
     * jadi counter mana pun bisa menyelesaikan resep counter lain.
     */
    public function test_counter_lain_tidak_bisa_recall_resep_counter_lain(): void
    {
        $resep = $this->makeResep();

        $this->loginAsFarmasi('Farmasi Racik 1');
        $this->put(route('farmasi.panggil-satu', $resep));
        $countSemula = $resep->fresh()->panggil_count;

        $this->loginAsFarmasi('Farmasi Racik 2');
        $response = $this->put(route('farmasi.ulang', $resep));

        $response->assertSessionHas('error');
        $this->assertSame($countSemula, $resep->fresh()->panggil_count);
    }

    public function test_counter_lain_tidak_bisa_menyelesaikan_resep_counter_lain(): void
    {
        $resep = $this->makeResep();

        $this->loginAsFarmasi('Farmasi Racik 1');
        $this->put(route('farmasi.panggil-satu', $resep));

        $this->loginAsFarmasi('Farmasi Racik 2');
        $response = $this->put(route('farmasi.selesai', $resep));

        $response->assertSessionHas('error');
        $this->assertNull($resep->fresh()->farmasi_selesai_at);
        $this->assertFalse((bool) $resep->fresh()->resep_clear);
    }

    public function test_tamu_tanpa_login_ditolak(): void
    {
        $resep = $this->makeResep();

        $response = $this->put(route('farmasi.panggil-satu', $resep));

        $response->assertRedirect(route('login'));
    }

    public function test_role_lain_tak_boleh_akses_route_farmasi(): void
    {
        $this->withSession(['auth' => true, 'role' => 'kasir_administrasi']);
        $resep = $this->makeResep();

        $response = $this->put(route('farmasi.panggil-satu', $resep));

        $response->assertForbidden();
    }
}
