@echo off
REM ============================================================
REM  SPEAKER PUSAT ANTRIAN
REM
REM  Jalankan di SATU PC saja — PC yang tersambung ke sound system
REM  rumah sakit. PC ini akan mengumumkan SEMUA panggilan (klinik,
REM  kasir, farmasi) untuk seluruh rumah sakit.
REM
REM  Tidak perlu menekan tombol apa pun: begitu halaman terbuka,
REM  speaker langsung aktif dan berbunyi saat ada panggilan/recall.
REM  Layar TV lain TIDAK perlu dibuka — speaker membaca panggilan
REM  langsung dari database.
REM
REM  Agar jalan otomatis saat PC menyala:
REM    tekan Win+R -> ketik: shell:startup -> Enter
REM    lalu salin SHORTCUT berkas ini ke folder yang terbuka.
REM
REM  Keluar: tekan ALT+F4.
REM ============================================================

REM --- IP PC yang menjalankan XAMPP (server antrian) ---
set "SERVER=172.20.0.39"

REM Bila .bat ini dijalankan DI PC server sendiri, boleh pakai:
REM set "SERVER=localhost"

set "URL=http://%SERVER%/display/speaker"
    
REM --- Profil terpisah agar tidak mengganggu Chrome sehari-hari ---
set "PROFIL=%LOCALAPPDATA%\AntrianSpeaker"

REM --- Cari lokasi Chrome ---
set "CHROME=%ProgramFiles%\Google\Chrome\Application\chrome.exe"
if not exist "%CHROME%" set "CHROME=%ProgramFiles(x86)%\Google\Chrome\Application\chrome.exe"
if not exist "%CHROME%" set "CHROME=%LOCALAPPDATA%\Google\Chrome\Application\chrome.exe"

REM --- Bila Chrome tidak ada, pakai Microsoft Edge (flag-nya sama) ---
if not exist "%CHROME%" set "CHROME=%ProgramFiles(x86)%\Microsoft\Edge\Application\msedge.exe"
if not exist "%CHROME%" set "CHROME=%ProgramFiles%\Microsoft\Edge\Application\msedge.exe"

if not exist "%CHROME%" (
    echo.
    echo  [!] Chrome / Edge tidak ditemukan.
    echo      Pasang Google Chrome lalu jalankan lagi berkas ini.
    echo.
    pause
    exit /b 1
)

echo Mengaktifkan speaker pusat: %URL%
start "" "%CHROME%" ^
 --autoplay-policy=no-user-gesture-required ^
 --disable-session-crashed-bubble ^
 --disable-infobars ^
 --noerrdialogs ^
 --no-first-run ^
 --disable-features=TranslateUI ^
 --user-data-dir="%PROFIL%" ^
 "%URL%"
