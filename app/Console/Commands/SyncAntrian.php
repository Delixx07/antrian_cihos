<?php

namespace App\Console\Commands;

use App\Services\AntrianSync;
use Illuminate\Console\Command;

/**
 * Tarik registrasi hari ini (dari appointment) ke tabel antrian lokal. Pasien
 * baru masuk tahap KLINIK. Idempoten. Untuk testing / dijadwalkan berkala.
 *
 *   php artisan antrian:sync
 *   php artisan antrian:sync --date=2026-07-28
 */
class SyncAntrian extends Command
{
    protected $signature = 'antrian:sync {--date= : Tanggal (Y-m-d), default hari ini}';
    protected $description = 'Tarik registrasi dari appointment ke antrian lokal';

    public function handle(AntrianSync $sync): int
    {
        $date = $this->option('date');
        $this->info('Menarik antrian dari appointment ('.($date ?: 'hari ini').')…');

        $r = $sync->pull($date);

        if (! $r['ok']) {
            $this->error('Gagal: '.$r['message']);

            return self::FAILURE;
        }

        $this->line("  ✔ Baru ditambahkan: {$r['added']}");
        $this->line("  Total antrian hari ini: {$r['total']}");

        return self::SUCCESS;
    }
}
