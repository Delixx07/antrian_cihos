# Deploy Aplikasi Antrian ke Server Produksi

Urutkan dari atas. Bagian **WAJIB** harus dikerjakan; melewatinya membuat
aplikasi error atau tidak aman.

---

## 0. Yang perlu dipahami dulu — database mana yang dibuat, mana yang tidak

Aplikasi ini memakai **4 database**. Hanya **satu** yang kita buat sendiri:

| Database | Isi | Di server produksi |
|---|---|---|
| **`antrian_cihos`** | antrian, hak akses, banner, video, foto dokter, sesi, cache | **BELUM ADA — kita yang membuat (§2)** |
| `cihos_master` | dokter, jadwal praktik, klinik, prefix ruang | Sudah ada, milik sistem lain — **jangan disentuh** |
| `appointment_pasien_cihos` | sumber data antrian (registrasi pasien) | Sudah ada, milik sistem lain — **jangan disentuh** |
| `dbuser` | direktori user RS, verifikasi password login | Sudah ada, milik sistem lain — **jangan disentuh** |

> **Tidak perlu backup/dump dari server lama.** Tiga database terakhir sudah
> tersedia di server produksi dan aplikasi ini hanya **membaca** darinya.
> `antrian_cihos` dibuat dari nol memakai berkas `setup-database.sql`
> yang sudah disiapkan di repo ini (§2) — bukan hasil salinan.

---

## 1. WAJIB — Ambil kode aplikasi

### Cara A — `git clone` (dianjurkan)

```powershell
cd C:\xampp\htdocs
git clone <url-repo> antrian
cd antrian
composer install --no-dev --optimize-autoloader
```

Semua berkas data ikut ter-clone, termasuk `public/banners/`,
`public/videos/`, `public/doctor-photos/`, `public/bel.mp3`, dan
`storage/deploy/setup-database.sql` — **tidak perlu menyalin apa pun manual**.

`.env` sengaja tidak ada di repo; dibuat di §3.

### Cara B — salin folder manual

Salin seluruh folder proyek, **KECUALI** ini (jangan ikut):

| Jangan disalin | Alasan |
|---|---|
| `.env` | konfigurasi lokal — dibuat baru di produksi (lihat §3) |
| `.env.bak-*` | cadangan lama, berisi kredensial |
| `storage/logs/*.log` | log lokal, bisa besar |
| `storage/app/*_backup_*.json` | cadangan uji coba |
| `public/tts/*.mp3` | cache audio, dibuat ulang otomatis |
| `node_modules/` | tidak diperlukan saat runtime |

**HARUS ikut** (mudah terlewat, isinya data asli):
- `public/banners/` — gambar promosi
- `public/videos/` — video promosi
- `public/doctor-photos/` — foto dokter
- `public/bel.mp3` — bel pengumuman
- `public/cihoslogo_biruijo.png`, `login_bg.png`
- `storage/deploy/setup-database.sql` — dipakai di §2

```
composer install --no-dev --optimize-autoloader
```

---

## 2. WAJIB — Buat database `antrian_cihos`

Berkas `storage/deploy/setup-database.sql` membuat database ini dari **NOL**:
17 tabel lengkap + data awal (hak akses user, banner, video, foto dokter),
dan tabel `migrations` sudah terisi sehingga Laravel tahu semua migrasi
telah dijalankan.

```powershell
cd C:\xampp\mysql\bin
.\mysql.exe -u root -p < storage\deploy\setup-database.sql
```

> **PowerShell tidak mendukung `<`.** Kalau muncul error
> *"The '<' operator is reserved for future use"*, pakai salah satu ini:
>
> ```powershell
> # cara A — mysql membaca berkasnya sendiri (paling cepat)
> .\mysql.exe -u root -p -e "source C:/xampp/htdocs/antrian/storage/deploy/setup-database.sql"
>
> # cara B — pipa; -Raw WAJIB, tanpa itu perintah multi-baris rusak
> Get-Content C:\xampp\htdocs\antrian\storage\deploy\setup-database.sql -Raw | .\mysql.exe -u root -p
> ```
>
> Pada cara A gunakan **garis miring biasa** (`/`) — `source` adalah perintah
> internal klien mysql, dan `\` di situ dianggap karakter escape.

Verifikasi (harus muncul 17 tabel):
```powershell
.\mysql.exe -u root -p -e "USE antrian_cihos; SHOW TABLES;"
```

> ⚠️ **Hanya untuk database kosong.** Berkas ini memuat perintah `DROP`.
> Bila `antrian_cihos` sudah ada dan berisi data operasional, **jangan
> dijalankan** — isinya akan terhapus.

<details>
<summary>Alternatif: bikin tabel lewat migration saja (tanpa data awal)</summary>

Kalau memang ingin database benar-benar kosong tanpa data awal:

```powershell
.\mysql.exe -u root -p -e "CREATE DATABASE IF NOT EXISTS antrian_cihos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
cd C:\xampp\htdocs\antrian
php artisan migrate --force
```

Strukturnya sama persis, tapi **tanpa** hak akses user, banner, video, dan
foto dokter — semuanya harus diisi ulang manual lewat menu admin.
Untuk deploy normal, pakai `setup-database.sql` di atas.
</details>

---

## 3. WAJIB — Buat `.env` produksi

> Berkas `.env*` (selain `.env.example`) **sengaja tidak ikut git** — isinya
> kredensial. Jadi setelah `git clone`, `.env` dan `.env.produksi` TIDAK ada
> di server; keduanya dibuat manual di sini. Kalau punya `.env.produksi`
> hasil siapan, salin lewat flashdisk/RDP — **jangan** di-commit ke repo.

```powershell
cd C:\xampp\htdocs\antrian
copy .env.example .env
```

Lalu isi. Yang **HARUS** berbeda dari lokal:

```env
APP_ENV=production
APP_DEBUG=false           # WAJIB false — true membocorkan isi kode & kredensial
                          # ke layar saat error
APP_KEY=                  # kosongkan, lalu jalankan: php artisan key:generate
APP_URL=http://172.20.0.39/antrian

# Aset memakai path RELATIF supaya tidak rusak saat IP berubah.
ASSET_URL=/antrian

# --- Database (sesuaikan kredensial produksi) ---
# Hanya antrian_cihos yang dibuat sendiri (§2); tiga sisanya sudah ada
# di server dan hanya DIBACA aplikasi ini.
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

> **Jangan pakai user `root`** untuk database di produksi. Buat user khusus:
> hak **penuh** atas `antrian_cihos`, tetapi cukup **`SELECT` saja** untuk
> `cihos_master`, `appointment_pasien_cihos`, dan `dbuser` — aplikasi ini
> hanya membaca ketiganya, dan hak tulis di situ berisiko merusak data
> sistem lain.
>
> ```sql
> CREATE USER 'antrian'@'localhost' IDENTIFIED BY '<password kuat>';
> GRANT ALL PRIVILEGES ON `antrian_cihos`.*             TO 'antrian'@'localhost';
> GRANT SELECT         ON `cihos_master`.*              TO 'antrian'@'localhost';
> GRANT SELECT         ON `appointment_pasien_cihos`.*  TO 'antrian'@'localhost';
> GRANT SELECT         ON `dbuser`.*                    TO 'antrian'@'localhost';
> FLUSH PRIVILEGES;
> ```

Lalu:
```
php artisan key:generate
php artisan migrate --force
php artisan config:clear
```

> `migrate --force` di sini hanya **verifikasi** — `setup-database.sql`
> sudah mengisi tabel `migrations`, jadi keluarannya semestinya
> **"Nothing to migrate"**. Kalau justru ada migrasi yang jalan, berarti
> repo di server lebih baru daripada berkas SQL-nya; itu normal dan
> memang perlu dijalankan.

---

## 4. WAJIB — Konfigurasi Apache

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

## 5. WAJIB — Suara pengumuman (TTS)

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

## 6. WAJIB — Hak tulis folder

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

## 7. Impor user

```
php artisan antrian:import-users --dry-run   # lihat dulu
php artisan antrian:import-users             # jalankan
```
Memetakan user dari `dbuser.user_detail` ke hak akses antrian berdasarkan
departemen. Password TIDAK disalin — login tetap diverifikasi ke `dbuser`.

---

## 8. Layar TV

Salin `buka-layar.bat` & `buka-speaker.bat` ke tiap PC layar, lalu ubah:
```bat
set "SERVER=172.20.0.39"
```

Agar jalan otomatis saat PC menyala: `Win+R` → `shell:startup` → taruh
shortcut-nya di situ.

> Kedua `.bat` memakai flag `--autoplay-policy=no-user-gesture-required`.
> **Tanpa membuka lewat .bat, suara diblokir browser** sampai layar diklik.

---

## 9. JANGAN dilakukan

- **Jangan** jalankan `php artisan config:cache` selama masih sering mengubah
  `.env`. Config basi pernah membuat koneksi `mysql` menunjuk ke database
  appointment → semua query `antrian_access` gagal → petugas logout sendiri.
  Bila terlanjur: `php artisan config:clear`.
- **Jangan** biarkan `APP_DEBUG=true`.
- **Jangan** arahkan Alias Apache ke root proyek.

---

## 10. Uji setelah deploy

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
