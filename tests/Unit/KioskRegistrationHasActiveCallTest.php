<?php

namespace Tests\Unit;

use App\Models\KioskRegistration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * KioskRegistration hidup di koneksi terpisah ('appointment', fisik di app
 * appointment) - RefreshDatabase TIDAK menjangkaunya (cuma koneksi default),
 * jadi skema dibuat manual di sini per test, meniru migration aslinya
 * (2026_08_13_150000_create_kiosk_registrations_table.php +
 * 2026_08_13_160000_add_call_fields_to_kiosk_registrations.php di app
 * appointment). Guard-nya sama persis semangatnya dgn Antrian::hasActiveCall().
 */
class KioskRegistrationHasActiveCallTest extends TestCase
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

    private function makeReg(array $overrides = []): KioskRegistration
    {
        return KioskRegistration::create(array_merge([
            'tanggal' => today(),
            'rg_no'   => 'RG'.random_int(100, 999),
            'status'  => 'menunggu',
            'counter' => 'Counter 1',
        ], $overrides));
    }

    public function test_tidak_ada_panggilan_aktif_saat_belum_pernah_dipanggil(): void
    {
        $this->makeReg();

        $this->assertFalse(KioskRegistration::hasActiveCall('Counter 1'));
    }

    public function test_dipanggil_tapi_belum_selesai_dianggap_aktif(): void
    {
        $this->makeReg(['status' => 'dipanggil', 'panggil_at' => now()]);

        $this->assertTrue(KioskRegistration::hasActiveCall('Counter 1'));
    }

    public function test_yang_sudah_selesai_tidak_lagi_dianggap_aktif(): void
    {
        $this->makeReg([
            'status'     => 'selesai',
            'panggil_at' => now()->subMinutes(5),
            'selesai_at' => now(),
        ]);

        $this->assertFalse(KioskRegistration::hasActiveCall('Counter 1'));
    }

    public function test_counter_berbeda_tidak_ikut_terblokir(): void
    {
        $this->makeReg(['counter' => 'Counter 1', 'panggil_at' => now()]);

        $this->assertFalse(KioskRegistration::hasActiveCall('Counter 2'));
    }

    public function test_hanya_menghitung_hari_ini(): void
    {
        $this->makeReg([
            'tanggal'    => today()->subDay(),
            'panggil_at' => now(),
        ]);

        $this->assertFalse(KioskRegistration::hasActiveCall('Counter 1'));
    }
}
