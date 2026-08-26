<?php

namespace Tests\Feature;

use App\Models\KioskRegistration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Sama seperti KasirDoubleCallGuardTest, tapi untuk konsol Registrasi (tiket
 * RG dari kiosk). KioskRegistration hidup di koneksi terpisah ('appointment')
 * - RefreshDatabase tak menjangkaunya, jadi skema dibuat manual per test
 * (lihat juga KioskRegistrationHasActiveCallTest untuk versi unit-nya).
 */
class RegistrasiDoubleCallGuardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::connection('appointment')->dropIfExists('kiosk_registrations');
        Schema::connection('appointment')->create('kiosk_registrations', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->string('rg_no', 20);
            $table->string('poli_kode', 50)->nullable();
            $table->string('poli_nama')->nullable();
            $table->unsignedBigInteger('paramedic_id')->nullable();
            $table->string('paramedic_name')->nullable();
            $table->string('status', 20)->default('menunggu');
            $table->string('appointment_no', 60)->nullable();
            $table->string('counter')->nullable();
            $table->timestamp('panggil_at')->nullable();
            $table->timestamp('selesai_at')->nullable();
            $table->unsignedInteger('panggil_count')->default(0);
            $table->timestamps();
            $table->unique(['tanggal', 'rg_no']);
        });
    }

    protected function tearDown(): void
    {
        Schema::connection('appointment')->dropIfExists('kiosk_registrations');
        parent::tearDown();
    }

    private function loginAsRegistrasi(string $counter = 'Counter 1'): void
    {
        $this->withSession([
            'auth'               => true,
            'user'               => 'registrasi1',
            'role'               => 'registrasi',
            'registrasi_counter' => $counter,
        ]);
    }

    private function makeRg(array $overrides = []): KioskRegistration
    {
        return KioskRegistration::create(array_merge([
            'tanggal' => today(),
            'rg_no'   => 'RG'.random_int(100, 999),
            'status'  => 'menunggu',
        ], $overrides));
    }

    public function test_petugas_bisa_memanggil_rg_pertama(): void
    {
        $this->loginAsRegistrasi();
        $rg = $this->makeRg();

        $response = $this->put(route('registrasi.panggil', $rg));

        $response->assertSessionHas('ok');
        $this->assertNotNull($rg->fresh()->panggil_at);
    }

    public function test_petugas_tidak_bisa_memanggil_rg_kedua_sebelum_yang_pertama_selesai(): void
    {
        $this->loginAsRegistrasi();
        $rg1 = $this->makeRg();
        $rg2 = $this->makeRg();

        $this->put(route('registrasi.panggil', $rg1));
        $response = $this->put(route('registrasi.panggil', $rg2));

        $response->assertSessionHas('error');
        $this->assertNull($rg2->fresh()->panggil_at);
        $this->assertSame('menunggu', $rg2->fresh()->status);
    }

    public function test_bisa_memanggil_rg_baru_setelah_yang_lama_selesai(): void
    {
        $this->loginAsRegistrasi();
        $rg1 = $this->makeRg();
        $rg2 = $this->makeRg();

        $this->put(route('registrasi.panggil', $rg1));
        $this->put(route('registrasi.selesai', $rg1));

        $response = $this->put(route('registrasi.panggil', $rg2));

        $response->assertSessionHas('ok');
        $this->assertNotNull($rg2->fresh()->panggil_at);
    }

    public function test_counter_lain_tidak_ikut_terblokir(): void
    {
        $rgA = $this->makeRg();
        $rgB = $this->makeRg();

        $this->loginAsRegistrasi('Counter 1');
        $this->put(route('registrasi.panggil', $rgA));

        $this->loginAsRegistrasi('Counter 2');
        $response = $this->put(route('registrasi.panggil', $rgB));

        $response->assertSessionHas('ok');
        $this->assertNotNull($rgB->fresh()->panggil_at);
    }

    /**
     * Panel "Sedang Dipanggil" menampilkan RG LINTAS counter, tapi Recall
     * harus tetap hanya boleh dari counter yang memanggilnya - sebelumnya
     * RegistrasiController::ulang() tidak mengecek ini sama sekali.
     */
    public function test_counter_lain_tidak_bisa_recall_rg_counter_lain(): void
    {
        $rg = $this->makeRg();

        $this->loginAsRegistrasi('Counter 1');
        $this->put(route('registrasi.panggil', $rg));
        $countSemula = $rg->fresh()->panggil_count;

        $this->loginAsRegistrasi('Counter 2');
        $response = $this->put(route('registrasi.ulang', $rg));

        $response->assertSessionHas('error');
        $this->assertSame($countSemula, $rg->fresh()->panggil_count);
    }

    public function test_tamu_tanpa_login_ditolak(): void
    {
        $rg = $this->makeRg();

        $response = $this->put(route('registrasi.panggil', $rg));

        $response->assertRedirect(route('login'));
    }

    public function test_role_lain_tak_boleh_akses_route_registrasi(): void
    {
        $this->withSession(['auth' => true, 'role' => 'kasir_administrasi']);
        $rg = $this->makeRg();

        $response = $this->put(route('registrasi.panggil', $rg));

        $response->assertForbidden();
    }
}
