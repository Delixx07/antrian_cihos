<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * AUTO-SYNC ANTRIAN — tarik pasien baru dari appointment tiap menit.
 *
 * Selain sync yang berjalan saat halaman antrian dibuka
 * (AntrianSync::pullThrottled()), jadwal ini membuat pasien tetap masuk
 * WALAUPUN belum ada petugas/dokter yang membuka halaman. Tanpa ini,
 * pasien yang mendaftar pagi baru muncul saat seseorang membuka aplikasi.
 *
 * Dijalankan oleh Windows Task Scheduler lewat scheduler.bat (tiap 1 menit).
 *   withoutOverlapping : cegah dua sync jalan bersamaan bila API lambat.
 *   runInBackground    : jangan menahan proses schedule:run.
 */
Schedule::command('antrian:sync')
    ->everyMinute()
    ->withoutOverlapping(5)
    ->runInBackground();
