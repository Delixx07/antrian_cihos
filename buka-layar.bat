@echo off
REM ============================================================
REM  Buka LAYAR ANTRIAN di TV (fullscreen + suara langsung aktif)
REM
REM  Flag --autoplay-policy=no-user-gesture-required membuat suara
REM  pengumuman berbunyi TANPA perlu diklik dulu — wajib untuk TV
REM  yang tidak punya mouse/keyboard.
REM
REM  Cara pakai:
REM    - Klik 2x berkas ini, ATAU
REM    - Klik 2x sambil memberi argumen area/URL, contoh:
REM        buka-layar.bat kasir
REM        buka-layar.bat farmasi
REM        buka-layar.bat client/1859,1860
REM
REM  Agar jalan otomatis saat PC menyala:
REM    tekan Win+R -> ketik: shell:startup -> Enter
REM    lalu salin SHORTCUT berkas ini ke folder yang terbuka.
REM
REM  Keluar dari fullscreen: tekan ALT+F4.
REM ============================================================

REM ============================================================
REM  ALAMAT SERVER — SESUAIKAN BILA IP SERVER BERBEDA
REM
REM  Isi dengan IP PC yang menjalankan XAMPP (server antrian),
REM  dilihat dari sisi TV. Cek dengan membuka alamat ini di
REM  browser TV: bila layar antrian muncul, berarti sudah benar.
REM ============================================================
set "SERVER=172.19.4.232"

REM Bila .bat ini dijalankan DI PC server sendiri, boleh pakai:
REM set "SERVER=localhost"

set "BASE=http://%SERVER%/display"

REM --- Area yang dibuka (default: klinik) ---
set "AREA=%~1"
if "%AREA%"=="" set "AREA=klinik"

REM --- Profil terpisah supaya flag & fullscreen tidak mengganggu
REM     Chrome yang dipakai sehari-hari. ---
set "PROFIL=%LOCALAPPDATA%\AntrianLayar"

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

echo Membuka layar antrian: %BASE%/%AREA%
start "" "%CHROME%" ^
 --autoplay-policy=no-user-gesture-required ^
 --kiosk ^
 --disable-session-crashed-bubble ^
 --disable-infobars ^
 --noerrdialogs ^
 --no-first-run ^
 --disable-features=TranslateUI ^
 --user-data-dir="%PROFIL%" ^
 "%BASE%/%AREA%"
