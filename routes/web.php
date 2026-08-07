<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BannerController;
use App\Http\Controllers\ClinicController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DisplayController;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\FarmasiController;
use App\Http\Controllers\KasirController;
use App\Http\Controllers\QueueController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\SpeechController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VideoController;
use Illuminate\Support\Facades\Route;

// --- Layar tunggu / kiosk (PUBLIK, tanpa login — untuk TV ruang tunggu) ---
// MENU pemilihan display (halaman awal). mis. /display
Route::get('/display', [DisplayController::class, 'menu'])->name('display.menu');

// CLIENT DISPLAY per ruang (foto dokter login + sesi + antrean). mis. /display/client/1859
// {room} boleh berisi koma utk 2 ruang: /display/client/1859,1860
Route::get('/display/client/{room}', [DisplayController::class, 'client'])
    ->where('room', '[0-9,\-]+')->name('display.client');
Route::get('/display/client/{room}/json', [DisplayController::class, 'clientJson'])
    ->where('room', '[0-9,\-]+')->name('display.client.json');

// AUDIO pengumuman (Bahasa Indonesia) — dirender di server sehingga semua
// perangkat berbunyi sama tanpa perlu voice terpasang.
// HARUS didaftarkan sebelum /display/{area} agar tidak tertangkap wildcard.
Route::get('/display/speech', [SpeechController::class, 'show'])->name('display.speech');

// ANTREAN SUARA TERPUSAT: semua layar mendaftarkan panggilan ke sini, lalu
// SATU perangkat speaker mengambil & memutarnya satu per satu (FIFO) agar
// dua panggilan bersamaan tidak berbunyi bertumpuk.
Route::get('/display/speech/enqueue', [SpeechController::class, 'enqueue'])->name('display.speech.enqueue');
Route::get('/display/speech/next', [SpeechController::class, 'nextAnnouncement'])->name('display.speech.next');
Route::get('/display/speech/done', [SpeechController::class, 'doneAnnouncement'])->name('display.speech.done');

// Halaman SPEAKER PUSAT — buka di satu PC yang tersambung sound system RS.
Route::view('/display/speaker', 'display.speaker')->name('display.speaker');

// MAIN DISPLAY per area: klinik | kasir | farmasi. mis. /display/farmasi
Route::get('/display/{area}', [DisplayController::class, 'show'])
    ->whereIn('area', ['klinik', 'kasir', 'farmasi'])->name('display.show');
Route::get('/display/{area}/json', [DisplayController::class, 'json'])
    ->whereIn('area', ['klinik', 'kasir', 'farmasi'])->name('display.json');

// --- Autentikasi ---
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// --- Halaman terlindungi (butuh login) ---
Route::middleware('auth.antrian')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/home', [DashboardController::class, 'index'])->name('home'); // alias

    // Pilih ruang (dokter dgn >1 ruang praktik hari ini).
    Route::get('/pilih-ruang', [RoomController::class, 'choose'])->name('room.choose');
    Route::post('/pilih-ruang', [RoomController::class, 'pick'])->name('room.pick');

    // Master (read-only dari DB appointment)
    Route::get('/klinik', [ClinicController::class, 'index'])->name('clinics.index');
    Route::put('/klinik/{code}', [ClinicController::class, 'update'])->name('clinics.update');
    Route::get('/dokter', [DoctorController::class, 'index'])->name('doctors.index');
    Route::put('/dokter/{id}/foto', [DoctorController::class, 'updatePhoto'])->name('doctors.photo');
    Route::get('/jadwal-dokter', [ScheduleController::class, 'index'])->name('schedules.index');

    // --- Antrian (registrasi hari ini per dokter, via API appointment) ---
    Route::get('/antrian', [QueueController::class, 'index'])->name('queue.index');
    // Tarik ulang pasien dari appointment SEKARANG (tombol manual di halaman
    // antrian) — dipakai bila sync otomatis terlewat/belum menarik pasien baru.
    Route::post('/antrian/sync', [QueueController::class, 'syncNow'])->name('queue.sync');
    // Konsol panggil dokter (role klinik).
    Route::post('/antrian/panggil', [QueueController::class, 'panggil'])->name('queue.panggil');
    // Panggil pasien tertentu dari barisnya (mirip kasir/farmasi).
    Route::put('/antrian/{antrian}/panggil', [QueueController::class, 'panggilSatu'])->name('queue.panggil-satu');
    Route::put('/antrian/{antrian}/ulang', [QueueController::class, 'ulang'])->name('queue.ulang');
    Route::put('/antrian/{antrian}/selesai', [QueueController::class, 'selesai'])->name('queue.selesai');

    // --- Counter Farmasi (role farmasi) ---
    Route::middleware('role:farmasi')->group(function () {
        Route::get('/farmasi/pilih-counter', [FarmasiController::class, 'pilihCounter'])->name('farmasi.pilih-counter');
        Route::post('/farmasi/pilih-counter', [FarmasiController::class, 'setCounter'])->name('farmasi.set-counter');
        Route::get('/farmasi', [FarmasiController::class, 'beranda'])->name('farmasi.beranda');
        Route::post('/farmasi/panggil', [FarmasiController::class, 'panggilBerikutnya'])->name('farmasi.panggil');
        // Panggil pasien TERTENTU dari barisnya (mirip kasir).
        Route::put('/farmasi/{antrian}/panggil', [FarmasiController::class, 'panggil'])->name('farmasi.panggil-satu');
        Route::put('/farmasi/{antrian}/ulang', [FarmasiController::class, 'ulang'])->name('farmasi.ulang');
        Route::put('/farmasi/{antrian}/panggil-ulang', [FarmasiController::class, 'panggilUlangRiwayat'])->name('farmasi.panggil-ulang');
        Route::put('/farmasi/{antrian}/lanjut', [FarmasiController::class, 'lanjut'])->name('farmasi.lanjut');
        Route::put('/farmasi/{antrian}/selesai', [FarmasiController::class, 'selesai'])->name('farmasi.selesai');
    });

    // --- Counter Kasir (role kasir administrasi / farmasi) ---
    Route::middleware('role:kasir_administrasi,kasir_farmasi')->group(function () {
        Route::get('/kasir/pilih-counter', [KasirController::class, 'pilihCounter'])->name('kasir.pilih-counter');
        Route::post('/kasir/pilih-counter', [KasirController::class, 'setCounter'])->name('kasir.set-counter');
        Route::get('/kasir', [KasirController::class, 'beranda'])->name('kasir.beranda');
        Route::put('/kasir/{antrian}/panggil', [KasirController::class, 'panggil'])->name('kasir.panggil');
        Route::put('/kasir/{antrian}/ulang', [KasirController::class, 'ulang'])->name('kasir.ulang');
        Route::put('/kasir/{antrian}/panggil-ulang', [KasirController::class, 'panggilUlangRiwayat'])->name('kasir.panggil-ulang');
        Route::put('/kasir/{antrian}/selesai', [KasirController::class, 'selesai'])->name('kasir.selesai');
    });

    // --- Manajemen User (hanya administrator) ---
    Route::middleware('role:administrator')->group(function () {
        Route::get('/user', [UserController::class, 'index'])->name('users.index');
        Route::get('/user/cari-direktori', [UserController::class, 'searchDirectory'])->name('users.search-directory');
        Route::get('/user/cari-dokter', [UserController::class, 'searchDoctors'])->name('users.search-doctors');
        Route::post('/user', [UserController::class, 'store'])->name('users.store');
        Route::put('/user/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/user/{user}', [UserController::class, 'destroy'])->name('users.destroy');

        // Media layar tunggu — halaman GABUNGAN (tab Video & Banner).
        Route::get('/media', [App\Http\Controllers\MediaController::class, 'index'])->name('media.index');

        // Aksi Banner (dipakai dari halaman media). index lama → redirect ke media.
        Route::get('/banner', fn () => redirect()->route('media.index'))->name('banners.index');
        Route::post('/banner', [BannerController::class, 'store'])->name('banners.store');
        Route::put('/banner/{banner}/klinik', [BannerController::class, 'updateClinics'])->name('banners.clinics');
        Route::put('/banner/{banner}/toggle', [BannerController::class, 'toggle'])->name('banners.toggle');
        Route::delete('/banner/{banner}', [BannerController::class, 'destroy'])->name('banners.destroy');

        // Aksi Video (dipakai dari halaman media). index lama → redirect ke media.
        Route::get('/video', fn () => redirect()->route('media.index'))->name('videos.index');
        Route::post('/video', [VideoController::class, 'store'])->name('videos.store');
        Route::put('/video/{video}/toggle', [VideoController::class, 'toggle'])->name('videos.toggle');
        Route::put('/video/{video}/klinik', [VideoController::class, 'updateClinics'])->name('videos.clinics');
        Route::delete('/video/{video}', [VideoController::class, 'destroy'])->name('videos.destroy');
    });
});
