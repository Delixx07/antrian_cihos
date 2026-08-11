# Catatan Lanjutan

## ⚠ 7 Agustus — WAJIB DIKERJAKAN

### 1. Aktifkan Task Scheduler (auto-sync belum jalan penuh)
Jadwal `antrian:sync` tiap menit SUDAH ditulis di `routes/console.php` dan
sudah diuji jalan, TAPI **belum didaftarkan di Windows Task Scheduler**,
jadi belum berjalan sendiri.

Cara mendaftarkan (sekali saja):
1. Buka **Task Scheduler** → Create Task
2. Name: `Antrian Sync`
3. Tab **Triggers** → New → Daily, Repeat task every **1 minute**,
   duration **Indefinitely**
4. Tab **Actions** → New → Program: `C:\xampp\htdocs\antrian\scheduler.bat`
5. Centang "Run whether user is logged on or not"

Tanpa ini, sync hanya jalan saat ada yang MEMBUKA halaman antrian.

### 2. Nomor tiket beda (FD001 vs FD023) — BUKAN BUG
Penyebab: data registrasi dibuat di **produksi**, sedangkan nomor slot
dicari di tabel `appointments` **lokal** (yang tidak punya data itu).
API lalu jatuh ke `QueueNo` MEDINFRAS (1, 2) alih-alih slot (23, 24).

Sudah dibuktikan logikanya benar:
```
FD001 + slot lokal 23 -> FD023   (yang diharapkan)
FD001 + QueueNo 1     -> FD001   (kondisi saat data lokal kosong)
```
**Tidak ada kode yang perlu diubah.** Untuk uji coba, buat appointment +
registrasi lewat aplikasi appointment LOKAL, jangan dari produksi.
Di produksi otomatis benar karena datanya satu database.

### 3. Logout sendiri — BELUM SELESAI
Ditemukan error di log:
`Table 'appointment_pasien_cihos.antrian_access' doesn't exist`

Artinya ada kode yang menanyakan tabel `antrian_access` ke database
**appointment**, padahal tabel itu ada di `antrian_cihos`. Saat gagal,
sesi tidak terverifikasi → terlempar ke login.

Config sudah benar (`DB_DATABASE=antrian_cihos`), jadi ada sesuatu yang
mengganti koneksi saat runtime. **Belum ketemu di mana.**
Langkah lanjut: cari `setDefaultConnection`/`config(['database...'])` atau
model tanpa `$connection` yang dipakai lintas aplikasi.

## ▶ LANJUTAN BERIKUTNYA (6 Agt, belum selesai)

### Cara menyebut NOMOR RUANG — belum diputuskan
Sekarang: hanya 2 digit terakhir → `1838` = "ruangan, tiga puluh delapan".
Permintaan: sebut LENGKAP tapi dipecah 2-2 → `1838` = "ruangan **18 | 38**".

Tinggal pilih cara bacanya, lalu saya ubah di `SpeechController::roomSpoken()`
dan `layar.blade.php` (fungsi `numberWordsId`) — dua tempat, harus sama:

| Opsi | `1838` dibaca |
|---|---|
| A | "delapan belas, tiga puluh delapan"  (tiap pasangan = bilangan) |
| B | "satu delapan, tiga delapan"         (tiap pasangan dieja) |
| C | "satu, delapan, tiga, delapan"       (semua digit dieja) |

> Catatan: aturan "puluhan nol disebut dua digit" (`1105` → "nol lima")
> mungkin tidak lagi relevan bila memakai format lengkap ini.

### Suara kadang tidak keluar ("kadang ngebug aja")
Belum ketemu penyebab pastinya. Yang SUDAH dipastikan normal:
controller mengirim flash `say`, endpoint `/display/speech` merender audio,
URL cocok dengan host, skrip ada di layout terkompilasi, autoplay tidak
diblokir, voice V5 aktif.

Bila terjadi lagi, buka **F12 → Console** saat menekan Recall lalu catat:
1. adakah teks error merah?
2. hasil dari `localStorage.getItem('antrian_say_queue')`
   - berisi data → payload sampai, masalah di pemutaran
   - `null`/`[]` → flash tidak sampai ke halaman

Sempat dibersihkan cache view (`php artisan optimize:clear`) — bisa jadi ini
penyebabnya (halaman memakai versi lama sebelum skrip suara ada).

---

# Catatan 5 Agustus 2026

## Yang SUDAH selesai & teruji

### Suara pengumuman
- Mesin: **Edge TTS** (`id-ID-GadisNeural`, suara perempuan), dirender di
  server → semua perangkat berbunyi sama tanpa install voice.
- Kalimat: `"Nomor antrian, F., D., nol, nol, lima, silakan menuju ruangan,
  lima puluh sembilan."`
  - Huruf dipisah `.,` (titik+koma) → jelas & tidak melebur jadi kata.
  - Angka nomor antrian dieja per digit.
  - **Nomor ruang: 2 digit terakhir, dibaca utuh** (`1859` → "lima puluh
    sembilan").
  - klinik → "ruangan", kasir/farmasi → "counter".
- Tempo: `TTS_EDGE_RATE=+8%`, `TTS_EDGE_PITCH=-2Hz` (di `config/tts.php`).
- **Tanpa klik**: layar langsung berbunyi saat recall — asal dibuka lewat
  `buka-layar.bat` (memakai flag `--autoplay-policy=no-user-gesture-required`).

### URL pendek
- `http://<ip>/display/klinik` dst. Alamat di browser TETAP pendek
  (rewrite internal di `htdocs/.htaccess`, bukan redirect).
- Program display lama dipindah ke `htdocs/display_old` (tidak dihapus).

### Perbaikan bug
- Banner bertarget klinik bocor ke semua layar → `scopeForClinic()` pada
  `Banner`/`Video` diperbaiki.
- Banner kini bisa diaktif/nonaktifkan (kolom `is_active` + toggle).
- Animasi toggle ikut jalan di semua kartu saat halaman dimuat → dibatasi
  hanya saat diklik.
- `"COUNTER Counter 2"` → kata depan ganda dibuang di `DisplayController`.

---

## PERLU DIPERIKSA BESOK

### 1. ~~IP server berubah → aset 404~~ SUDAH DIPERBAIKI (6 Agt)
`ASSET_URL` diubah jadi **relatif**: `/antrian` (bukan lagi `http://<ip>/antrian`).
Aset kini ikut host apa pun — IP wifi, IP LAN, `localhost`, atau hostname —
tanpa perlu diubah lagi saat jaringan berganti.

Sudah diuji dari `172.19.4.232` dan `localhost`: logo & audio termuat, nol
request gagal.

**Sisa yang masih menyebut IP (perlu disesuaikan bila server pindah):**
- `.env` → `APP_URL` (dipakai untuk link di email/CLI, bukan tampilan layar)
- `buka-layar.bat` → variabel `BASE`

> Catatan: IP mesin ini saat dicek adalah **172.19.4.232** (Wi-Fi).
> `172.20.0.39` pada layar kemarin kemungkinan jaringan lain (LAN/lokasi lain).
> Bila TV memakai jaringan berbeda, sesuaikan `BASE` di `buka-layar.bat` saja.

### 2. Belum dites di TV sungguhan
Semua pengujian memakai browser headless di PC ini. Yang perlu dicek langsung:
- suara keluar dari speaker TV saat recall ditekan,
- tampilan pas di layar TV (bukan 1920x1080 simulasi),
- `buka-layar.bat` jalan otomatis saat PC menyala.

### 3. Speaker pusat (opsional, belum dipakai)
Bila nanti ingin SATU PC saja yang bersuara untuk seluruh RS:
- buka `http://<ip>/display/speaker`
- set `TTS_SOUND_MODE=central` di `.env`
Speaker ini sudah bisa membaca panggilan langsung dari database, jadi tetap
berbunyi walau layar area itu tidak dibuka. **Belum diuji di lapangan.**

### 4. Hal kecil
- ~~`1105` terdengar "ruangan lima"~~ **SUDAH DIPERBAIKI (6 Agt):** nomor ruang
  yang puluhannya nol kini disebut dua digit — `1105` → "nol, lima",
  `1600` → "nol, nol". Selain itu tetap dibaca utuh (`1859` → "lima puluh
  sembilan"). Berlaku untuk audio server maupun cadangan suara browser.
- Folder `htdocs/display_old` bisa dihapus bila sudah yakin tidak dipakai.
- Cadangan config: `httpd.conf.bak-20260805-1558`, `.env.bak-*`.
