<?php

namespace Tests\Feature;

use App\Models\Antrian;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Uji END-TO-END (routing + middleware + controller, bukan cuma model) untuk
 * bug yang pernah lolos ke produksi: petugas bisa memanggil pasien baru
 * sebelum pasien sebelumnya selesai, sehingga beberapa nomor "Sedang
 * Dipanggil" muncul sekaligus. Diperbaiki via Antrian::hasActiveCall() yang
 * dipanggil dari KasirController - test ini memastikan guard itu benar2
 * nyambung sampai ke HTTP layer, bukan cuma benar di level model.
 */
class KasirDoubleCallGuardTest extends TestCase
{
    use RefreshDatabase;

    private function loginAsKasir(string $counter = 'Counter 1'): void
    {
        $this->withSession([
            'auth'          => true,
            'user'          => 'kasir1',
            'name'          => 'Kasir Satu',
            'role'          => 'kasir_administrasi',
            'kasir_counter' => $counter,
        ]);
    }

    private function makePasienKasir(array $overrides = []): Antrian
    {
        return Antrian::create(array_merge([
            'tanggal'        => today(),
            'no_antrian'     => 'FD'.random_int(100, 999),
            'appointment_no' => 'APT'.random_int(100000, 999999),
            'tahap'          => Antrian::TAHAP_KASIR,
        ], $overrides));
    }

    public function test_petugas_bisa_memanggil_pasien_pertama(): void
    {
        $this->loginAsKasir();
        $pasien = $this->makePasienKasir();

        $response = $this->put(route('kasir.panggil', $pasien));

        $response->assertRedirect();
        $response->assertSessionHas('ok');
        $this->assertNotNull($pasien->fresh()->kasir_panggil_at);
    }

    public function test_petugas_tidak_bisa_memanggil_pasien_kedua_sebelum_yang_pertama_selesai(): void
    {
        $this->loginAsKasir();
        $pasien1 = $this->makePasienKasir();
        $pasien2 = $this->makePasienKasir();

        $this->put(route('kasir.panggil', $pasien1));
        $response = $this->put(route('kasir.panggil', $pasien2));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertNull(
            $pasien2->fresh()->kasir_panggil_at,
            'pasien kedua TIDAK BOLEH ikut terpanggil selama yang pertama masih aktif'
        );
    }

    public function test_bisa_memanggil_pasien_baru_setelah_yang_lama_selesai(): void
    {
        $this->loginAsKasir();
        $pasien1 = $this->makePasienKasir();
        $pasien2 = $this->makePasienKasir();

        $this->put(route('kasir.panggil', $pasien1));
        $this->put(route('kasir.selesai', $pasien1), ['status_resep' => Antrian::RESEP_NON]);

        $response = $this->put(route('kasir.panggil', $pasien2));

        $response->assertSessionHas('ok');
        $this->assertNotNull($pasien2->fresh()->kasir_panggil_at);
    }

    public function test_counter_lain_tidak_ikut_terblokir_oleh_counter_yang_sedang_aktif(): void
    {
        $pasien1 = $this->makePasienKasir();
        $pasien2 = $this->makePasienKasir();

        $this->loginAsKasir('Counter 1');
        $this->put(route('kasir.panggil', $pasien1));

        $this->loginAsKasir('Counter 2');
        $response = $this->put(route('kasir.panggil', $pasien2));

        $response->assertSessionHas('ok');
        $this->assertNotNull($pasien2->fresh()->kasir_panggil_at);
    }

    /**
     * Panel "Sedang Dipanggil" menampilkan pasien LINTAS counter (supaya semua
     * kasir bisa lihat siapa sedang dilayani di mana), tapi Recall harus
     * TETAP hanya boleh dari counter yang memanggilnya - sebelumnya
     * KasirController::ulang() tidak mengecek ini sama sekali, jadi counter
     * mana pun bisa mengulang panggilan pasien counter lain.
     */
    public function test_counter_lain_tidak_bisa_recall_pasien_counter_lain(): void
    {
        $pasien = $this->makePasienKasir();

        $this->loginAsKasir('Counter 1');
        $this->put(route('kasir.panggil', $pasien));
        $countSemula = $pasien->fresh()->panggil_count;

        $this->loginAsKasir('Counter 2');
        $response = $this->put(route('kasir.ulang', $pasien));

        $response->assertSessionHas('error');
        $this->assertSame(
            $countSemula, $pasien->fresh()->panggil_count,
            'Counter 2 TIDAK BOLEH mengubah panggilan pasien milik Counter 1'
        );
    }

    public function test_counter_pemilik_tetap_bisa_recall_pasiennya_sendiri(): void
    {
        $pasien = $this->makePasienKasir();

        $this->loginAsKasir('Counter 1');
        $this->put(route('kasir.panggil', $pasien));
        $countSemula = $pasien->fresh()->panggil_count;

        $response = $this->put(route('kasir.ulang', $pasien));

        $response->assertSessionHas('ok');
        $this->assertSame($countSemula + 1, $pasien->fresh()->panggil_count);
    }

    public function test_tamu_tanpa_login_ditolak(): void
    {
        $pasien = $this->makePasienKasir();

        $response = $this->put(route('kasir.panggil', $pasien));

        $response->assertRedirect(route('login'));
    }

    public function test_role_lain_tak_boleh_akses_route_kasir(): void
    {
        $this->withSession(['auth' => true, 'role' => 'farmasi']);
        $pasien = $this->makePasienKasir();

        $response = $this->put(route('kasir.panggil', $pasien));

        $response->assertForbidden();
    }
}
