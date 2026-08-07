@echo off
REM ============================================================
REM  Laravel Scheduler untuk aplikasi ANTRIAN.
REM  Menjalankan `php artisan schedule:run` sekali.
REM  Dipanggil oleh Windows Task Scheduler tiap 1 menit,
REM  yang di dalamnya menjalankan `antrian:sync` (tarik pasien
REM  baru dari appointment) tiap menit.
REM ============================================================
cd /d C:\xampp\htdocs\antrian
C:\xampp\php\php.exe artisan schedule:run
