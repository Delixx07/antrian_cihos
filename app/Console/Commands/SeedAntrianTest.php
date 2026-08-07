<?php

namespace App\Console\Commands;

use App\Models\Antrian;
use App\Models\Doctor;
use Illuminate\Console\Command;

/**
 * Isi pasien DUMMY ke antrian untuk testing UI (tanpa perlu data appointment).
 *
 *   php artisan antrian:seed-test                 # 3 pasien di tahap klinik (dokter pertama)
 *   php artisan antrian:seed-test --tahap=farmasi # langsung di tahap farmasi
 *   php artisan antrian:seed-test --tahap=kasir --jumlah=5
 *   php artisan antrian:seed-test --paramedic=140 # untuk dokter tertentu
 *   php artisan antrian:seed-test --fresh         # hapus antrian hari ini dulu
 */
class SeedAntrianTest extends Command
{
    protected $signature = 'antrian:seed-test
        {--tahap=klinik : klinik|kasir|farmasi}
        {--jumlah=3 : jumlah pasien}
        {--paramedic= : paramedic_id dokter (default: dokter pertama di master)}
        {--resep= : status resep utk tahap kasir/farmasi: non_resep|racik|non_racik (default: racik)}
        {--fresh : hapus antrian hari ini dulu}';

    protected $description = 'Isi pasien dummy ke antrian untuk testing';

    public function handle(): int
    {
        $tahap  = $this->option('tahap');
        $jumlah = (int) $this->option('jumlah');

        if (! in_array($tahap, ['klinik', 'kasir', 'farmasi'], true)) {
            $this->error("Tahap tak valid: {$tahap}. Pakai klinik|kasir|farmasi.");

            return self::FAILURE;
        }

        // Dokter (untuk tahap klinik butuh paramedic_id yang cocok dgn akun dokter).
        $paramedicId = (int) $this->option('paramedic');
        $doctor = $paramedicId ? Doctor::find($paramedicId) : Doctor::first();
        if (! $doctor) {
            $this->error('Tak ada dokter di master (cihos_master). Jalankan sync appointment dulu.');

            return self::FAILURE;
        }
        $paramedicId = (int) $doctor->paramedic_id;

        if ($this->option('fresh')) {
            Antrian::whereDate('tanggal', today())->delete();
            $this->warn('Antrian hari ini dihapus.');
        }

        $nama = ['Budi Santoso', 'Siti Aminah', 'Ahmad Rizki', 'Dewi Lestari', 'Eko Prasetyo',
                 'Fitri Handayani', 'Gunawan Wijaya', 'Hana Permata'];

        // Nomor urut lanjut dari yang sudah ada hari ini.
        $mulai = (int) Antrian::whereDate('tanggal', today())->max('queue_no') + 1;
        $prefix = strtoupper(substr(str_replace(' ', '', $doctor->paramedic_name ?: 'TS'), 0, 2));

        $now = now();
        // Status resep utk tahap kasir/farmasi (klinik belum punya status).
        $resep = $this->option('resep') ?: Antrian::RESEP_RACIK;
        if (! in_array($resep, [Antrian::RESEP_NON, Antrian::RESEP_RACIK, Antrian::RESEP_NON_RACIK], true)) {
            $resep = Antrian::RESEP_RACIK;
        }
        $adaResep = in_array($resep, [Antrian::RESEP_RACIK, Antrian::RESEP_NON_RACIK], true);

        for ($i = 0; $i < $jumlah; $i++) {
            $q = $mulai + $i;
            $data = [
                'tanggal'          => today(),
                'no_antrian'       => $prefix.str_pad((string) $q, 3, '0', STR_PAD_LEFT),
                'queue_no'         => $q,
                'pasien_nama'      => $nama[$i % count($nama)].' (TES)',
                'pasien_nomrn'     => '00-TES-'.str_pad((string) $q, 3, '0', STR_PAD_LEFT),
                'paramedic_id'     => $paramedicId,
                'poli_dokter_nama' => $doctor->paramedic_name,
                'poli_nama'        => $doctor->specialty_name ?: 'Umum',
                'tahap'            => $tahap,
                'appointment_no'   => 'TES/'.today()->format('Ymd').'/'.str_pad((string) $q, 5, '0', STR_PAD_LEFT),
            ];
            // Set *_tunggu_at sesuai tahap agar muncul di daftar "menunggu".
            $data["{$tahap}_tunggu_at"] = $now->copy()->subMinutes($jumlah - $i);

            // Kasir/Farmasi butuh status resep + transfer_at (waktu tunggu).
            if ($tahap === 'kasir' || $tahap === 'farmasi') {
                $data['status_resep'] = $resep;
                $data['transfer_at']  = $now->copy()->subMinutes($jumlah - $i);
                if ($adaResep) {
                    $data['farmasi_jenis'] = $resep; // racik/non_racik → agar tampil di counter farmasi
                }
            }
            Antrian::create($data);
        }

        $this->info("✔ {$jumlah} pasien dummy dibuat di tahap {$tahap}.");
        $this->line("  Dokter: {$doctor->paramedic_name} (paramedic_id={$paramedicId})");
        $this->line('  Total antrian hari ini: '.Antrian::whereDate('tanggal', today())->count());

        if ($tahap === 'klinik') {
            $this->line("  → Login dokter yg terikat paramedic_id={$paramedicId} untuk memanggil.");
        }

        return self::SUCCESS;
    }
}
