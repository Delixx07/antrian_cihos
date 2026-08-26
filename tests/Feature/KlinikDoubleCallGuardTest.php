<?php

namespace Tests\Feature;

use App\Models\Antrian;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sama seperti KasirDoubleCallGuardTest, tapi untuk konsol Klinik (dokter) -
 * guard-nya per DOKTER (paramedic_id), bukan per counter. Rute panggil-satu/
 * ulang/selesai tidak dibatasi middleware role (controller sendiri yang
 * menolak lewat pengecekan paramedic_id sesi vs pemilik baris).
 */
class KlinikDoubleCallGuardTest extends TestCase
{
    use RefreshDatabase;

    private function loginAsDokter(int $paramedicId = 1): void
    {
        $this->withSession([
            'auth'         => true,
            'user'         => 'dokter1',
            'role'         => 'klinik',
            'paramedic_id' => $paramedicId,
        ]);
    }

    private function makePasienKlinik(array $overrides = []): Antrian
    {
        return Antrian::create(array_merge([
            'tanggal'        => today(),
            'no_antrian'     => 'ED'.random_int(100, 999),
            'appointment_no' => 'APT'.random_int(100000, 999999),
            'tahap'          => Antrian::TAHAP_KLINIK,
            'paramedic_id'   => 1,
            'is_booking'     => false,
        ], $overrides));
    }

    public function test_dokter_bisa_memanggil_pasien_pertama(): void
    {
        $this->loginAsDokter();
        $pasien = $this->makePasienKlinik();

        $response = $this->put(route('queue.panggil-satu', $pasien));

        $response->assertSessionHas('ok');
        $this->assertNotNull($pasien->fresh()->klinik_panggil_at);
    }

    public function test_dokter_tidak_bisa_memanggil_pasien_kedua_sebelum_yang_pertama_selesai(): void
    {
        $this->loginAsDokter();
        $pasien1 = $this->makePasienKlinik();
        $pasien2 = $this->makePasienKlinik();

        $this->put(route('queue.panggil-satu', $pasien1));
        $response = $this->put(route('queue.panggil-satu', $pasien2));

        $response->assertSessionHas('error');
        $this->assertNull($pasien2->fresh()->klinik_panggil_at);
    }

    public function test_bisa_memanggil_pasien_baru_setelah_yang_lama_selesai(): void
    {
        $this->loginAsDokter();
        $pasien1 = $this->makePasienKlinik();
        $pasien2 = $this->makePasienKlinik();

        $this->put(route('queue.panggil-satu', $pasien1));
        $this->put(route('queue.selesai', $pasien1));

        $response = $this->put(route('queue.panggil-satu', $pasien2));

        $response->assertSessionHas('ok');
        $this->assertNotNull($pasien2->fresh()->klinik_panggil_at);
    }

    public function test_dokter_lain_tidak_ikut_terblokir(): void
    {
        $pasienDokter1 = $this->makePasienKlinik(['paramedic_id' => 1]);
        $pasienDokter2 = $this->makePasienKlinik(['paramedic_id' => 2]);

        $this->loginAsDokter(1);
        $this->put(route('queue.panggil-satu', $pasienDokter1));

        $this->loginAsDokter(2);
        $response = $this->put(route('queue.panggil-satu', $pasienDokter2));

        $response->assertSessionHas('ok');
        $this->assertNotNull($pasienDokter2->fresh()->klinik_panggil_at);
    }

    /**
     * QueueController::selesai() sebelumnya TIDAK mengecek kepemilikan sama
     * sekali (beda dari panggil-satu/ulang yang sudah mengecek paramedic_id)
     * - dokter mana pun bisa POST ke /queue/{id}/selesai untuk ID pasien
     * dokter lain dan mendorongnya ke Kasir.
     */
    public function test_dokter_lain_tidak_bisa_menyelesaikan_pasien_dokter_lain(): void
    {
        $pasienDokter1 = $this->makePasienKlinik(['paramedic_id' => 1]);

        $this->loginAsDokter(2);
        $response = $this->put(route('queue.selesai', $pasienDokter1));

        $response->assertSessionHas('error');
        $this->assertSame(Antrian::TAHAP_KLINIK, $pasienDokter1->fresh()->tahap);
        $this->assertNull($pasienDokter1->fresh()->klinik_selesai_at);
    }

    public function test_tamu_tanpa_login_ditolak(): void
    {
        $pasien = $this->makePasienKlinik();

        $response = $this->put(route('queue.panggil-satu', $pasien));

        $response->assertRedirect(route('login'));
    }
}
