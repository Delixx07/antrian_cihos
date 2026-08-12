# Deploy Aplikasi Antrian ke Server Produksi

Urutkan dari atas. Bagian **WAJIB** harus dikerjakan; melewatinya membuat
aplikasi error atau tidak aman.

---

## 1. WAJIB — Berkas & folder yang disalin

Salin seluruh folder proyek, **KECUALI** ini (jangan ikut):

| Jangan disalin | Alasan |
|---|---|
| `.env` | konfigurasi lokal — dibuat baru di produksi (lihat §2) |
| `.env.bak-*` | cadangan lama, berisi kredensial |
| `storage/logs/*.log` | log lokal, bisa besar |
| `storage/app/*_backup_*.json` | cadangan uji coba |
| `public/tts/*.mp3` | cache audio, dibuat ulang otomatis |
| `node_modules/`, `.git/` | tidak diperlukan saat runtime |

**HARUS ikut** (mudah terlewat, isinya data asli):
- `public/banners/` — gambar promosi
- `public/videos/` — video promosi
- `public/doctor-photos/` — foto dokter
- `public/bel.mp3` — bel pengumuman
- `public/cihoslogo_biruijo.png`, `login_bg.png`

```
composer install --no-dev --optimize-autoloader
```

---

## 2. WAJIB — Buat `.env` produksi

Salin `.env.example`, lalu isi. Yang **HARUS** berbeda dari lokal:

```env
APP_ENV=production
APP_DEBUG=false           # WAJIB false — true membocorkan isi kode & kredensial
                          # ke layar saat error
APP_KEY=                  # kosongkan, lalu jalankan: php artisan key:generate
APP_URL=http://172.20.0.39/antrian

# Aset memakai path RELATIF supaya tidak rusak saat IP berubah.
ASSET_URL=/antrian

# --- Database (sesuaikan kredensial produksi) ---
DB_DATABASE=antrian_cihos
DB_USERNAME=<jangan root>
DB_PASSWORD=<password kuat>

MASTER_DB_DATABASE=cihos_master
APPT_DB_DATABASE=appointment_pasien_cihos
DBUSER_DATABASE=dbuser

# --- Sesi ---
SESSION_DRIVER=database
SESSION_LIFETIME=10080     # 7 hari
SESSION_PATH=/antrian      # WAJIB — memisahkan sesi dari aplikasi lain
SESSION_COOKIE=antrian_session

# --- Suara pengumuman ---
TTS_ENGINE=edge
TTS_EDGE_VOICE="id-ID-GadisNeural"
TTS_EDGE_RATE="+8%"
TTS_EDGE_PITCH="-12Hz"
TTS_SOUND_MODE=local
```

> **Jangan pakai user `root`** untuk database di produksi. Buat user khusus
> yang hanya boleh mengakses 4 database di atas.

Lalu:
```
php artisan key:generate
php artisan migrate --force
```

---

## 3. WAJIB — Konfigurasi Apache

Tambahkan di `httpd.conf` (arahkan ke folder `public`, BUKAN root proyek —
kalau salah, `.env` bisa diunduh siapa saja):

```apache
Alias /antrian "C:/xampp/htdocs/antrian/public"

<Directory "C:/xampp/htdocs/antrian/public">
    Options All
    AllowOverride All
    Require all granted
</Directory>
```

**Uji keamanannya** setelah Apache restart — semuanya harus **404/403**:
```
http://172.20.0.39/antrian/.env
http://172.20.0.39/antrian/composer.json
http://172.20.0.39/antrian/app/Http/Controllers/AuthController.php
```

### URL pendek untuk layar TV (opsional)
Kalau ingin `172.20.0.39/display/klinik`, salin `htdocs/.htaccess` dari server ini.

---

## 4. WAJIB — Suara pengumuman (TTS)

Aplikasi memakai **Edge TTS** (voice `id-ID-GadisNeural`). Server produksi
perlu:

```
python --version          # pastikan Python 3 terpasang
pip install edge-tts
```

Uji:
```
php artisan tinker
>>> app(App\Services\SpeechSynthesizer::class)->available()
```
Harus `true`. Kalau `false`, suara tidak akan keluar.

> Edge TTS butuh **koneksi internet** saat merender kalimat baru. Hasilnya
> di-cache di `public/tts`, jadi tiap kalimat hanya sekali online.
> Bila server produksi tidak boleh keluar internet, pakai Piper (offline) —
> lihat `SUARA-INDONESIA.md`.

---

## 5. WAJIB — Hak tulis folder

Pastikan Apache bisa menulis ke:
```
storage/            (log, sesi, cache)
bootstrap/cache/
public/tts/         (cache audio)
public/banners/     (unggah banner)
public/videos/
public/doctor-photos/
```

---

## 6. Impor user

```
php artisan antrian:import-users --dry-run   # lihat dulu
php artisan antrian:import-users             # jalankan
```
Memetakan user dari `dbuser.user_detail` ke hak akses antrian berdasarkan
departemen. Password TIDAK disalin — login tetap diverifikasi ke `dbuser`.

---

## 7. Layar TV

Salin `buka-layar.bat` & `buka-speaker.bat` ke tiap PC layar, lalu ubah:
```bat
set "SERVER=172.20.0.39"
```

Agar jalan otomatis saat PC menyala: `Win+R` → `shell:startup` → taruh
shortcut-nya di situ.

> Kedua `.bat` memakai flag `--autoplay-policy=no-user-gesture-required`.
> **Tanpa membuka lewat .bat, suara diblokir browser** sampai layar diklik.

---

## 8. JANGAN dilakukan

- **Jangan** jalankan `php artisan config:cache` selama masih sering mengubah
  `.env`. Config basi pernah membuat koneksi `mysql` menunjuk ke database
  appointment → semua query `antrian_access` gagal → petugas logout sendiri.
  Bila terlanjur: `php artisan config:clear`.
- **Jangan** biarkan `APP_DEBUG=true`.
- **Jangan** arahkan Alias Apache ke root proyek.

---

## 9. Uji setelah deploy

```
# Aplikasi hidup
curl -I http://172.20.0.39/antrian/login                    # 200

# Berkas sensitif terlindungi
curl -I http://172.20.0.39/antrian/.env                     # 404/403

# Sync appointment jalan
php artisan antrian:sync                             # "Baru ditambahkan: N"

# Suara berfungsi
curl "http://172.20.0.39/antrian/display/speech?no=A001&dest=1859&area=klinik"
                                                     # {"url":"/antrian/tts/....mp3"}

# Layar tampil
curl -I http://172.20.0.39/antrian/display/klinik           # 200
```

Terakhir, buka layar lewat `buka-layar.bat`, tekan **Panggil** di konsol
dokter — harus terdengar bel → pengumuman → bel.

---

## Catatan arsitektur

Antrian membaca appointment **langsung dari MySQL** (`appointment_pasien_cihos`),
bukan lewat HTTP API. Konsekuensinya:

- Kedua aplikasi **harus satu server** (atau MySQL-nya bisa diakses).
- `APPOINTMENT_API_*` di `.env` sudah **tidak dipakai** untuk antrian —
  boleh dibiarkan atau dihapus.
- Tidak perlu cron/Task Scheduler: sync berjalan saat halaman dibuka,
  dan sangat cepat (~0,1 detik) karena hanya membaca tabel lokal.
