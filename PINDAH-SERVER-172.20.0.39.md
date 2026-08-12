# Pindah Aplikasi Antrian ke Server 172.20.0.39

Panduan lengkap memindahkan aplikasi antrian dari `172.19.4.232` ke server baru
`172.20.0.39`. Kerjakan **berurutan dari atas**.

> Dokumen ini menggantikan `DEPLOY-PRODUKSI.md` untuk kasus pindah server.
> `DEPLOY-PRODUKSI.md` masih berguna sebagai rujukan konfigurasi Apache & TTS,
> tetapi bagian "tidak perlu Task Scheduler" di sana **KELIRU** — lihat §8.

## Ringkasan langkah

| # | Di server | Langkah |
|---|---|---|
| 2 | LAMA | `mysqldump antrian_cihos` + pastikan media sudah ter-push |
| 3 | BARU | `git clone` + `composer install` |
| 4 | BARU | restore `antrian_cihos` saja |
| 5 | BARU | `copy .env_forproduction .env` + isi password DB |
| 6 | BARU | `key:generate`, `migrate`, `npm run build` |
| 7 | BARU | pasang `edge-tts` |
| 8 | BARU | **Task Scheduler** (tanpa ini antrian tidak jalan otomatis) |
| 9–10 | BARU | hak tulis folder + Apache |
| 11 | BARU | verifikasi |

---

## 0. Kondisi awal & lingkup pekerjaan

**Di server `172.20.0.39` sudah tersedia:**

- Aplikasi **Appointment** sudah terpasang dan **berjalan**
- Database `cihos_master`, `appointment_pasien_cihos`, `dbuser` **sudah ada**

**Yang perlu dikerjakan hanya:** memasang aplikasi antrian dari nol +
memindahkan **satu** database saja, yaitu `antrian_cihos`.

Ketergantungan antrian (semuanya sudah tersedia di server tujuan kecuali
baris pertama):

| Kebutuhan | Status di 172.20.0.39 |
|---|---|
| DB `antrian_cihos` | **perlu dipindah** (§2) |
| DB `cihos_master` | sudah ada |
| DB `appointment_pasien_cihos` | sudah ada |
| DB `dbuser` | sudah ada |
| Aplikasi + API Appointment | sudah jalan |
| SQL Server MEDINFRAS `10.10.110.3` | perlu dijangkau via ODBC |
| Python + `edge-tts` | perlu dipasang (§7) |

Karena Appointment satu server, seluruh koneksi DB memakai `127.0.0.1` dan
`APPOINTMENT_API_URL=http://172.20.0.39/appointment`. Nilai-nilai ini sudah
disiapkan di berkas **`.env_forproduction`**.

---

## 1. Prasyarat di server 172.20.0.39

Pastikan sudah terpasang **sebelum** menyalin berkas:

- **XAMPP** dengan **PHP >= 8.2** (`php -v`)
- **MySQL/MariaDB** aktif
- **Composer** (`composer -V`)
- **Node.js + npm** (`node -v`) — hanya bila akan build ulang aset
- **Python 3** + `edge-tts` — untuk suara pengumuman (§7)
- Ekstensi PHP aktif di `php.ini`:
  `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `curl`, `zip`, `gd`
- **`pdo_sqlsrv` + ODBC Driver 17/18** — untuk baca zona/room MEDINFRAS

Cek cepat ekstensi:

```powershell
php -m | Select-String "pdo_mysql|mbstring|openssl|fileinfo|curl|zip|gd|sqlsrv"
```

---

## 2. Backup dari server LAMA (172.19.4.232)

### 2a. Database — HANYA `antrian_cihos`

```powershell
cd C:\xampp\mysql\bin
.\mysqldump.exe -u root -p --routines --events --single-transaction antrian_cihos > C:\backup\antrian_cihos.sql
```

> `cihos_master`, `appointment_pasien_cihos`, dan `dbuser` **TIDAK perlu
> di-dump** — ketiganya sudah ada di server `172.20.0.39` dan sedang dipakai
> aplikasi Appointment di sana. Menimpanya justru berisiko merusak data
> produksi yang sedang berjalan.

### 2b. Berkas media — sudah ikut git, TIDAK perlu disalin manual

Sudah diperiksa: seluruh media ter-track git, jadi ikut terbawa saat
`git clone` (§3):

| Folder / berkas | Status |
|---|---|
| `public/banners/` | 10 berkas, semua di git |
| `public/videos/` | 1 berkas, di git |
| `public/doctor-photos/` | 2 berkas, di git |
| `public/bel.mp3` | di git |
| `public/cihoslogo_biruijo.png`, `public/login_bg.png` | di git |

**Syaratnya:** media yang ditambahkan lewat aplikasi (upload banner/video/foto
dokter baru) **setelah** commit terakhir belum ada di git. Sebelum clone,
di server lama jalankan:

```powershell
cd C:\xampp\htdocs\antrian
git status --short public/
```

Bila ada berkas `??` di situ, commit & push dulu — kalau tidak, media itu
hilang di server baru.

### 2c. Simpan `.env` lama sebagai rujukan

Tidak disalin ke server baru (pakai `.env_forproduction`), tapi simpan untuk
membandingkan kredensial bila ada yang perlu dicocokkan.

---

## 3. Clone kode di server baru

Di server **172.20.0.39**:

```powershell
cd C:\xampp\htdocs
git clone https://github.com/Delixx07/antrian_cihos.git antrian
cd antrian
composer install --no-dev --optimize-autoloader
```

Media (banner, video, foto dokter, `bel.mp3`, logo) **ikut terbawa clone** —
lihat §2b. `.env` dan `vendor/` tidak ada di git; keduanya dibuat di sini
(`composer install`) dan §5.

---

## 4. Restore database `antrian_cihos` di server baru

Di server **172.20.0.39**:

```powershell
cd C:\xampp\mysql\bin
.\mysql.exe -u root -p -e "CREATE DATABASE antrian_cihos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
.\mysql.exe -u root -p antrian_cihos < C:\backup\antrian_cihos.sql
```

> ⚠️ **HANYA** database ini. Jangan menyentuh `cihos_master`,
> `appointment_pasien_cihos`, atau `dbuser` — semuanya sedang dipakai
> aplikasi Appointment yang sudah berjalan di server itu.

Verifikasi tabel masuk:

```powershell
.\mysql.exe -u root -p -e "USE antrian_cihos; SHOW TABLES;"
```

---

## 5. Buat `.env` produksi

Berkas **`.env_forproduction`** sudah disiapkan khusus untuk server
`172.20.0.39` — seluruh host DB sudah `127.0.0.1`, `APP_URL` dan
`APPOINTMENT_API_URL` sudah menunjuk `172.20.0.39`, dan `APPOINTMENT_API_KEY`
serta `SQLSRV_*` sudah terisi.

> Berkas itu masuk `.gitignore`, jadi **tidak ikut `git clone`**. Salin manual
> dari server lama (mis. lewat USB / share folder) ke root aplikasi di server
> baru.

```powershell
cd C:\xampp\htdocs\antrian
copy .env_forproduction .env
```

Lalu **isi 8 placeholder** di dalamnya:

- `DB_USERNAME` / `DB_PASSWORD`
- `MASTER_DB_USERNAME` / `MASTER_DB_PASSWORD`
- `APPT_DB_USERNAME` / `APPT_DB_PASSWORD`
- `DBUSER_USERNAME` / `DBUSER_PASSWORD`

Gunakan kredensial MySQL yang **sudah dipakai aplikasi Appointment** di server
itu, atau buat user khusus (perintah `CREATE USER` + `GRANT` ada sebagai
komentar di dalam `.env_forproduction`).

`APP_KEY` sengaja kosong — diisi otomatis di §6.

> **Penting soal `SESSION_PATH`.** Bila aplikasi Appointment berada di server
> yang sama, `SESSION_PATH` keduanya harus berbeda (`/antrian` vs
> `/appointment`). Bila sama-sama `/`, cookie `XSRF-TOKEN` kedua aplikasi
> saling menimpa dan pengguna **logout sendiri** secara acak.

---

## 6. Inisialisasi aplikasi

```powershell
cd C:\xampp\htdocs\antrian
php artisan key:generate
php artisan migrate --force
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
```

**WAJIB — build aset.** `public/build/` tidak ada di repo, jadi tanpa langkah
ini halaman tampil **tanpa CSS sama sekali**:

```powershell
npm ci
npm run build
```

Pastikan `public/build/manifest.json` terbentuk setelahnya.

---

## 7. Suara pengumuman (Edge TTS)

```powershell
python --version
pip install edge-tts
python -c "import edge_tts; print('OK')"
```

Bila `python` tidak dikenali, set path lengkapnya di `.env`:

```env
TTS_PYTHON="C:\Python312\python.exe"
```

Pastikan `public/tts/` bisa ditulis (§9).

---

## 8. WAJIB — Task Scheduler (sync otomatis)

> `DEPLOY-PRODUKSI.md` menyebut Task Scheduler tidak perlu. **Itu keliru.**
> `routes/console.php` menjadwalkan `antrian:sync` setiap menit; tanpa
> scheduler, pasien yang mendaftar pagi **baru muncul saat ada orang membuka
> halaman**.

Buat task di Windows Task Scheduler:

- **Program**: `C:\xampp\htdocs\antrian\scheduler.bat`
- **Trigger**: harian, ulangi tiap **1 menit**, durasi **tanpa batas**
- **Run whether user is logged on or not**: ya
- **Start in**: `C:\xampp\htdocs\antrian`

Buka `scheduler.bat` dan pastikan path di dalamnya sudah benar untuk server
baru (isinya `cd /d C:\xampp\htdocs\antrian`).

Uji manual dulu:

```powershell
cd C:\xampp\htdocs\antrian
php artisan antrian:sync
```

---

## 9. Hak tulis folder

```powershell
icacls "C:\xampp\htdocs\antrian\storage" /grant "Users:(OI)(CI)M" /T
icacls "C:\xampp\htdocs\antrian\bootstrap\cache" /grant "Users:(OI)(CI)M" /T
icacls "C:\xampp\htdocs\antrian\public\tts" /grant "Users:(OI)(CI)M" /T
```

---

## 10. Konfigurasi Apache

Pastikan `mod_rewrite` aktif dan `AllowOverride All` untuk `htdocs`.
Rujuk `DEPLOY-PRODUKSI.md` §3 untuk detail vhost dan URL pendek layar TV.

Restart Apache setelah semua perubahan.

---

## 11. Verifikasi setelah pindah

Jalankan berurutan — semua harus lulus:

```powershell
# 1. Aplikasi hidup
curl.exe -s -o NUL -w "%{http_code}`n" http://172.20.0.39/antrian/login
#    harapan: 200

# 2. Berkas sensitif TIDAK bisa diakses
curl.exe -s -o NUL -w "%{http_code}`n" http://172.20.0.39/antrian/.env
#    harapan: 403 atau 404 (BUKAN 200)

# 3. Koneksi DB & migrasi
php artisan migrate:status

# 4. Sync dari Appointment
php artisan antrian:sync

# 5. Konfigurasi terbaca benar
php artisan tinker --execute="echo config('database.connections.mysql.database');"
#    harapan: antrian_cihos
```

Lalu uji manual di browser:

- Login petugas berhasil
- Daftar antrian tampil dan terisi
- Layar TV `/display/...` tampil, banner & video muncul
- Panggil satu pasien → suara terdengar di PC speaker

---

## 12. JANGAN dilakukan

- ❌ `APP_DEBUG=true` di produksi — membocorkan kredensial saat error
- ❌ `php artisan config:cache` bila belum yakin — nilai `.env` bisa
  ter-cache salah. Bila dipakai, **selalu** `config:clear` setelah mengubah `.env`
- ❌ Memakai user `root` MySQL untuk aplikasi
- ❌ Menyalin `.env` lama mentah-mentah — `APP_URL` dan host DB akan salah
- ❌ Membiarkan `SESSION_PATH=/` bila Appointment ada di server yang sama

---

## 13. Rollback

Server lama **jangan dimatikan** sampai server baru terbukti stabil
minimal 1 hari kerja penuh. Bila gagal, arahkan kembali pengguna ke
`172.19.4.232` — data lama masih utuh selama tidak ada penulisan baru ke
server lama.

---

## Lampiran — Masalah yang sering muncul

| Gejala | Penyebab | Solusi |
|---|---|---|
| Halaman tanpa CSS/gambar | `ASSET_URL` salah atau `public/build/` belum ada | set `ASSET_URL=/antrian`, `npm run build` |
| Logout sendiri berulang | `SESSION_PATH` bentrok dgn Appointment | set `/antrian` dan `/appointment`, hapus cookie browser |
| Antrian tidak bertambah | Task Scheduler belum dibuat | §8 |
| Suara tidak keluar | `edge-tts` belum terpasang / `public/tts` tak bisa ditulis | §7, §9 |
| Zona/room kosong | ODBC SQL Server gagal | cek `pdo_sqlsrv`, jangkauan ke `10.10.110.3` |
| Error `Table ... doesn't exist` | config ter-cache dari aplikasi lain | `php artisan config:clear` |
| Loading sangat lama | SQL Server MEDINFRAS lambat dari jaringan ini | cek latensi ke `10.10.110.3` |
