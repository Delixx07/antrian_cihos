# Panduan Testing — Aplikasi Antrian CIHOS

Panduan langkah-demi-langkah untuk menguji aplikasi antrian, dari login sampai
tampil di layar tunggu. Mencerminkan **kondisi aplikasi saat ini**.

---

## 0. Persiapan

1. **Jalankan MySQL** lewat XAMPP Control Panel (jangan `mysqld` manual).
2. Pastikan database ada: `antrian_cihos`, `cihos_master`, `dbuser`,
   `appointment_pasien_cihos`.
3. **Aplikasi appointment harus jalan** (antrian menarik data pasien darinya).
4. Buka aplikasi antrian di browser. Kalau muncul **419 Page Expired**:
   hapus cookie situs / pakai jendela **Incognito**.

> Tip: buka tiap peran di **jendela/incognito berbeda** supaya bisa login
> beberapa peran sekaligus tanpa saling menendang.

---

## 1. Akun untuk testing

Semua login lewat halaman login antrian. Format **username = password**
(kecuali admin).

| Peran            | Username        | Password    | Keterangan                         |
|------------------|-----------------|-------------|------------------------------------|
| Super Admin      | `admin`         | `123456.`   | Akun lokal, akses semua menu       |
| Dokter (Klinik)  | `klinik`        | `klinik`    | Terikat dokter #124                |
| Farmasi          | `farmasi`       | `farmasi`   | Counter Racik/Non-Racik            |
| Kasir Administrasi | `kasir`       | `kasir`     | Counter 1–4                        |
| Kasir Farmasi    | `kasirfarmasi`  | `kasirfarmasi` |                                 |
| Admisi Rajal/LAB | `admisirl`      | `admisirl`  | (UI belum dibuat)                  |
| Admisi IGD       | `admisiigd`     | `admisiigd` | (UI belum dibuat)                  |
| Admisi Radiologi | `admisirad`     | `admisirad` | (UI belum dibuat)                  |
| SPV              | `spv`           | `spv`       | Lihat semua (read-only)            |

---

## 2. Menyiapkan pasien untuk diuji

Ada 2 cara mengisi antrian dengan pasien:

### A. Data DUMMY (paling praktis untuk tes UI)

Jalankan di terminal:

```bash
cd C:\xampp\htdocs\antrian

# 3 pasien di tahap KLINIK untuk dokter akun "klinik" (paramedic 124):
php artisan antrian:seed-test --tahap=klinik --paramedic=124 --jumlah=3 --fresh

# Langsung di tahap FARMASI (cepat tes layar tunggu):
php artisan antrian:seed-test --tahap=farmasi --jumlah=3 --fresh

# Langsung di tahap KASIR:
php artisan antrian:seed-test --tahap=kasir --jumlah=3 --fresh
```

Opsi:
- `--tahap=` → `klinik` | `kasir` | `farmasi`
- `--jumlah=` → berapa pasien
- `--paramedic=124` → dokter tertentu (WAJIB cocok dgn akun dokter untuk tahap klinik)
- `--fresh` → hapus antrian hari ini dulu

### B. Data NYATA dari appointment

```bash
php artisan antrian:sync
```

Menarik pasien yang benar-benar check-in di RS hari ini → masuk tahap **klinik**.
Butuh ada pasien real + terhubung jaringan RS.

> Di produksi/testing, pasien baru dari appointment ditarik otomatis (throttle
> maks 1x/45 dtk) saat halaman antrian dibuka. Tak perlu cron/scheduler.

---

## 3. Alur antrian (cara kerja)

Kuncinya: **dokter menentukan status resep** saat selesai. Alur setelahnya
otomatis mengikuti status itu.

```
Pasien check-in (appointment) → antrian TAHAP KLINIK
   │
   ▼  Dokter panggil → SELESAI, pilih salah satu:
      • Tanpa Resep
      • Resep Racik
      • Resep Non-Racik
   │  (transfer ke KASIR dengan status tsb)
   ▼
TAHAP KASIR — melihat SEMUA pasien (Tanpa Resep / Resep / Resep CLEAR)
   │  Panggil → Selesai (tujuan OTOMATIS dari status):
   ├─ Tanpa Resep       → SELESAI (pulang)
   ├─ Ada Resep (blm clear) → TAHAP FARMASI
   └─ Resep CLEAR       → SELESAI (bayar obat terakhir)
   ▼ (yang ada resep)
TAHAP FARMASI — melihat HANYA pasien ber-resep sesuai counter (Racik/Non-Racik)
   │  Panggil → Selesai → status "Resep CLEAR"
   ▼
   kembali ke KASIR untuk pembayaran obat → SELESAI
```

**Status pasien** (kolom Status, berwarna):
- **Tanpa Resep** (abu) — tak ada resep obat
- **Resep Racik / Resep Non-Racik** (kuning) — punya resep, belum diproses farmasi
- **Resep CLEAR** (hijau) — resep sudah selesai diproses farmasi

**Waktu Tunggu** dihitung sejak pasien di-transfer dari klinik.

---

## 4. Skenario testing UTAMA (rantai penuh dgn resep)

**Siapkan pasien di tahap klinik:**
```bash
php artisan antrian:seed-test --tahap=klinik --paramedic=124 --jumlah=3 --fresh
```

### Langkah 1 — DOKTER (Klinik)
1. Login `klinik` / `klinik` → otomatis ke halaman antriannya.
2. Klik **▶ Panggil Antrian** → nomor pasien muncul di kotak besar.
3. (Opsional) klik **⟲ Ulang** untuk memanggil ulang.
4. Klik salah satu status resep:
   - **✔ Tanpa Resep** — pasien tak dapat obat
   - **💊 Resep Racik** — resep racikan
   - **💊 Resep Non-Racik** — resep non-racik

   → pasien diteruskan ke **Kasir** dengan status itu.
   (Untuk mencoba alur farmasi, pilih **Resep Racik** atau **Non-Racik**.)
5. Logout.

### Langkah 2 — KASIR
1. Login `kasir` / `kasir`.
2. Muncul **"Silahkan Pilih Counter Pemanggil"** → pilih **Counter 1** → **PILIH COUNTER**.
3. Lihat **List Antrian** — semua pasien dengan kolom **Status** (berwarna) & **Waktu Tunggu**.
4. Klik **▶ Panggil Antrian** → pasien muncul (status resepnya tampil).
5. Tombol:
   - **⟲ Ulang** — panggil ulang
   - **✔ Selesai** — lanjut OTOMATIS:
     - Tanpa Resep → pasien selesai/pulang
     - Ada Resep → pasien lanjut ke **Farmasi**
6. Klik **✔ Selesai** (untuk pasien ber-resep → masuk Farmasi).
7. Logout.

### Langkah 3 — Buka LAYAR TUNGGU (tanpa login)
Di jendela/monitor lain, buka:
```
http://<host-antrian>/display/farmasi
```
Masih idle ("Menunggu panggilan…"). Tekan **F11** untuk fullscreen.

### Langkah 4 — FARMASI
1. Login `farmasi` / `farmasi`.
2. Pilih jenis **sesuai status resep pasien** (mis. **Farmasi Racik** kalau tadi
   dokter pilih Resep Racik) + **Counter 1** → **PILIH COUNTER**.
   > Farmasi HANYA melihat pasien ber-resep sesuai jenis counter. Kalau counter
   > Racik tapi pasien Non-Racik, tak akan muncul (dan sebaliknya).
3. Klik **▶ Panggil Antrian** → pasien dipanggil.
4. **Lihat LAYAR TUNGGU** → nomor muncul besar + berkedip + "Counter 1". ✅
5. Klik **✔ Selesai** (atau **» Lanjut**) → resep jadi **"Resep CLEAR"**, pasien
   otomatis **kembali ke antrian KASIR** untuk pembayaran obat.
6. Logout.

### Langkah 5 — KASIR (pembayaran obat)
1. Login `kasir` / `kasir` (pilih counter bila diminta).
2. Pasien tadi muncul lagi di list dengan status **"Resep CLEAR"** (hijau).
3. Klik **▶ Panggil Antrian** → **✔ Selesai** → pasien **SELESAI** (bayar obat, pulang). ✅

**Selesai — satu pasien ber-resep sudah menempuh rantai penuh:**
`Klinik → Kasir → Farmasi → Kasir → Selesai`

---

## 5. Testing per-modul (terpisah)

### Layar Tunggu Farmasi (cepat)
```bash
# Pasien langsung di tahap farmasi, resep racik:
php artisan antrian:seed-test --tahap=farmasi --resep=racik --jumlah=3 --fresh
```
→ login `farmasi` → pilih **Farmasi Racik** + counter → Panggil → lihat `/display/farmasi`.

> Opsi `--resep=` : `racik` | `non_racik` | `non_resep`. Untuk tahap **farmasi**,
> pakai `racik`/`non_racik` (harus cocok dgn jenis counter yg dipilih saat login).

### Kasir (cepat)
```bash
# Campur: sebagian ber-resep, sebagian tidak
php artisan antrian:seed-test --tahap=kasir --resep=racik --jumlah=2 --fresh
php artisan antrian:seed-test --tahap=kasir --resep=non_resep --jumlah=2
```
→ login `kasir` → pilih counter → lihat tabel Status (kuning=resep, abu=tanpa).

### Admin — Manajemen User
1. Login `admin` / `123456.`.
2. Menu **Manajemen User** → **Tambah User**:
   - Cari pegawai dari direktori RS (ketik nama/username).
   - Pilih **Hak Akses**. Kalau **Klinik** → muncul pilih **Dokter**.
   - (Opsional) isi Counter / centang Blokir.
3. Edit / Hapus user.

### Admin — Banner
1. Menu **Banner** → upload gambar (JPG/PNG/WEBP).
2. Gambar muncul di grid + tampil slideshow di layar tunggu.

### Admin — Video
1. Menu **Video** → upload .mp4/.webm.
2. Klik **Aktifkan** pada satu video → hanya yang aktif diputar di layar tunggu.

### Admin — Lihat semua antrian
1. Menu **Antrian** → pilih dokter dari dropdown → lihat antriannya (read-only).

### Master (read-only)
- **Daftar Klinik**, **Daftar Dokter** (bisa edit foto), **Jadwal Dokter**.

---

## 6. Reset data testing

```bash
# Hapus semua antrian hari ini:
php artisan tinker --execute="App\Models\Antrian::whereDate('tanggal',today())->delete(); echo 'cleared';"

# Atau isi ulang dari nol:
php artisan antrian:seed-test --tahap=klinik --paramedic=124 --fresh
```

---

## 7. Sync pasien dari appointment

Pasien baru dari appointment (registrasi RS) ditarik **otomatis** saat halaman
antrian dibuka — di-throttle maks 1x per 45 detik supaya ringan. **Tak perlu
cron / Task Scheduler.** Karena antrian & appointment 1 server, sync-nya cepat.

Kalau ingin tarik manual:
```bash
php artisan antrian:sync            # tarik registrasi hari ini
php artisan antrian:sync --date=2026-07-28
```

---

## 8. Yang BELUM ada (jangan diuji dulu)

- **Admisi** (tahap awal, jarang dipakai di data lama).
- **Layar tunggu untuk Kasir/Klinik** (baru Farmasi yang ada).
- **Suara panggil (TTS)** — layar baru visual (kedip).
- **Deteksi resep otomatis dari MEDINFRAS** — sekarang status resep dipilih
  DOKTER manual (racik/non-racik/tanpa). Detail obat dari MEDINFRAS belum dibaca.
- **Laporan Antrian**, **dashboard kartu data nyata**.

---

## 9. Masalah umum

| Masalah | Solusi |
|---|---|
| **419 Page Expired / logout sendiri** | Hapus cookie situs (`172.19.4.232`) / pakai Incognito. Pastikan `APP_URL` di `.env` = host yang kamu akses. |
| Antrian dokter kosong | Jalankan `php artisan antrian:seed-test` atau buka halaman (auto-sync). |
| Pasien ber-resep tak muncul di Farmasi | Pastikan jenis counter Farmasi (Racik/Non-Racik) **cocok** dgn status resep pasien. |
| Layar tunggu tak update | Normal, poll tiap 4 dtk. Pastikan farmasi sudah memanggil. |
| Login ditolak | Pastikan user terdaftar di Manajemen User & tak diblokir. |
| Tak bisa login 2 app | Cookie sudah dipisah (`antrian_session` / `appointment_session`). Hapus cookie lama. |
