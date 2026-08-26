# Panduan Use Case & Testing — Aplikasi Antrian CIHOS

Peta lengkap siapa memakai aplikasi ini untuk apa (use case), lalu panduan
langkah-demi-langkah untuk mengujinya secara manual, dan peta test otomatis
yang sudah ada. Mencerminkan **kondisi aplikasi saat ini**.

---

## 0. Persiapan

1. **Jalankan MySQL** lewat XAMPP Control Panel (jangan `mysqld` manual).
2. Pastikan database ada: `antrian_cihos`, `cihos_master`, `dbuser`,
   `appointment_pasien_cihos`.
3. **Aplikasi appointment harus jalan** (antrian menarik data pasien darinya).
4. Buka aplikasi antrian di browser. Kalau muncul **419 Page Expired**:
   hapus cookie situs / pakai jendela **Incognito**.
5. **Suara panggilan (TTS)** butuh internet aktif di server (Edge TTS
   render di cloud Microsoft) - lihat use case UC-13.

> Tip: buka tiap peran di **jendela/incognito berbeda** supaya bisa login
> beberapa peran sekaligus tanpa saling menendang.

---

## 1. Akun untuk testing

Semua login lewat halaman login antrian. Format **username = password**
(kecuali admin). Login dibatasi **5 percobaan/menit** per kombinasi
username+IP (lihat UC-01c).

| Peran            | Username        | Password       | Keterangan                         |
|------------------|-----------------|----------------|-------------------------------------|
| Super Admin      | `admin`         | `123456.`      | Akun lokal, akses semua menu       |
| Dokter (Klinik)  | `klinik`        | `klinik`       | Terikat dokter #124                |
| Farmasi          | `farmasi`       | `farmasi`      | Pilih jenis (Racik/Non-Racik) + Counter 1-4 |
| Kasir Administrasi | `kasir`       | `kasir`        | Counter 1-4                        |
| Kasir Farmasi    | `kasirfarmasi`  | `kasirfarmasi` | Sama alurnya dgn Kasir Administrasi |
| Registrasi       | *(buat via Manajemen User)* | - | Counter 1-4, panggil tiket RG dari kiosk |
| SPV              | `spv`           | `spv`          | Monitor lintas klinik (read-only)  |
| Admisi Rajal/LAB | `admisirl`      | `admisirl`     | (UI belum dibuat)                  |
| Admisi IGD       | `admisiigd`     | `admisiigd`    | (UI belum dibuat)                  |
| Admisi Radiologi | `admisirad`     | `admisirad`    | (UI belum dibuat)                  |

---

## 2. Peta Use Case per Aktor

Aktor: **Dokter (Klinik)**, **Kasir**, **Farmasi**, **Registrasi**, **SPV**,
**Administrator**, **Layar Publik (tanpa login)**, **Sistem (background sync)**.

### Dokter (Klinik) — `role: klinik`

| ID | Use Case | Route | Catatan |
|----|----------|-------|---------|
| UC-01 | Login, ditempatkan otomatis ke ruang praktik hari ini | `POST /login` | Lihat UC-01a/b di bawah |
| UC-02 | Panggil pasien berikutnya (nomor terkecil menunggu) | `POST /antrian/panggil` | Ditolak bila masih ada pasien aktif (belum Selesai) |
| UC-03 | Panggil pasien TERTENTU dari daftar | `PUT /antrian/{id}/panggil` | Ditolak bila bukan pasiennya, atau pasien masih booking (belum check-in) |
| UC-04 | Panggil ulang (recall) pasien aktif/riwayat | `PUT /antrian/{id}/ulang` | Menaikkan `panggil_count` (memicu bunyi ulang di layar) |
| UC-05 | Selesai konsultasi → pasien lanjut ke Kasir | `PUT /antrian/{id}/selesai` | **Wajib** cek kepemilikan (`paramedic_id`) - lihat temuan keamanan di §8 |

**UC-01a — Login, 1 ruang jadwal hari ini**: langsung menempati ruang itu bila kosong.
**UC-01b — Login, >1 ruang jadwal hari ini**: diarahkan ke `/pilih-ruang` (UC-06/07).
**UC-01c — Login gagal / diblokir / rate-limited**: lihat §3.

| ID | Use Case | Route |
|----|----------|-------|
| UC-06 | Lihat pilihan ruang (yang sudah ditempati dokter lain ditandai) | `GET /pilih-ruang` |
| UC-07 | Pilih satu ruang → ditolak bila keburu ditempati dokter lain (bentrok) | `POST /pilih-ruang` |

### Kasir — `role: kasir_administrasi \| kasir_farmasi`

| ID | Use Case | Route |
|----|----------|-------|
| UC-08 | Pilih counter (1-4) sebelum mulai kerja | `GET/POST /kasir/pilih-counter` |
| UC-09 | Lihat SEMUA pasien tahap Kasir (status resep berwarna) | `GET /kasir` |
| UC-10 | Panggil pasien tertentu | `PUT /kasir/{id}/panggil` | Ditolak bila counter ini masih punya pasien aktif |
| UC-11 | Recall pasien aktif **milik counter sendiri** | `PUT /kasir/{id}/ulang` | Counter LAIN ditolak (lihat §8) |
| UC-12 | Selesai → pilih tujuan: Tanpa Resep (pulang) / Racik / Non-Racik (→ Farmasi) | `PUT /kasir/{id}/selesai` | Modal pop-up pilihan |
| UC-12a | Panggil ulang dari riwayat (sudah selesai, perlu balik) | `PUT /kasir/{id}/panggil-ulang` |

### Farmasi — `role: farmasi`

| ID | Use Case | Route |
|----|----------|-------|
| UC-13 | Pilih jenis (Racik/Non-Racik) + counter | `GET/POST /farmasi/pilih-counter` |
| UC-14 | Lihat HANYA resep sesuai jenis counter, belum clear | `GET /farmasi` |
| UC-15 | Panggil resep berikutnya / tertentu | `POST /farmasi/panggil`, `PUT /farmasi/{id}/panggil` |
| UC-16 | Recall **milik counter sendiri** (counter lain ditolak) | `PUT /farmasi/{id}/ulang` |
| UC-17 | Selesai/Lanjut → resep "CLEAR", pasien balik ke Kasir bayar | `PUT /farmasi/{id}/selesai`, `/lanjut` | Counter lain ditolak menyelesaikan (§8) |
| UC-17a | Panggil ulang dari riwayat | `PUT /farmasi/{id}/panggil-ulang` |

### Registrasi — `role: registrasi`

Menangani tiket **RG** dari kiosk self-service (app `appointment`, tabel
`kiosk_registrations`) - **BUKAN** membuat appointment resmi, cuma menandai
sudah ditangani.

| ID | Use Case | Route |
|----|----------|-------|
| UC-18 | Pilih counter (1-4) | `GET/POST /registrasi/pilih-counter` |
| UC-19 | Lihat antrian RG menunggu/dipanggil lintas counter | `GET /registrasi` |
| UC-20 | Panggil tiket RG (juga dipakai untuk panggil ulang dari riwayat) | `PUT /registrasi/{id}/panggil` |
| UC-21 | Recall **milik counter sendiri** (counter lain ditolak) | `PUT /registrasi/{id}/ulang` |
| UC-22 | Tandai selesai ditangani | `PUT /registrasi/{id}/selesai` |

### SPV — `role: spv` (read-only, lintas klinik)

| ID | Use Case | Route |
|----|----------|-------|
| UC-23 | Buka beranda tanpa pernah pilih klinik → ajakan buka modal "Ganti Klinik" | `GET /spv` |
| UC-24 | Pilih 1+ klinik (modal) → tabel per klinik muncul, scrollable (5 baris awal) | `POST /spv/klinik` |
| UC-25 | Pilih 1+ dokter per klinik (modal, checklist "Pilih Semua"/"Kosongkan") | `POST /spv/dokter` | Kosong = tampilkan semua dokter klinik itu |
| UC-26 | Filter tersimpan di SESI, bertahan lewat auto-refresh 20 detik | - | Termasuk kompatibilitas sesi format lama (single ID) |
| UC-27 | Urutkan tabel asc/desc per klinik | - | UI saja (client-side) |

### Administrator — `role: administrator`

| ID | Use Case | Route |
|----|----------|-------|
| UC-28 | Tambah/edit/hapus user, cari pegawai dari direktori RS, blokir akun | `/user*` |
| UC-29 | Kelola foto dokter (upload → crop → simpan ke `img-dokter`, nama file = nama dokter) | `PUT /dokter/{id}/foto` |
| UC-30 | Kelola Zona Klinik (nama + pasangan ruang per Client Display) | `/zones*` |
| UC-31 | Kelola Media layar tunggu: Banner (gambar) + Video, running text | `/media*`, `/banner*`, `/video*` |
| UC-32 | Lihat SEMUA antrian per dokter (read-only, dropdown) | `GET /antrian?paramedic_id=` |
| UC-33 | Lihat master Klinik/Dokter/Jadwal (read-only dari `master`) | `/klinik`, `/dokter`, `/jadwal-dokter` |
| UC-34 | Lihat Dashboard (kartu total pasien/klinik/kasir/farmasi + grafik 10 hari + list dokter aktif) | `GET /` |

### Layar Publik — TANPA login (TV ruang tunggu)

| ID | Use Case | Route |
|----|----------|-------|
| UC-35 | Menu pilih display (kartu per area + Zona Klinik) | `GET /display` |
| UC-36 | Main Display per area: klinik/kasir/farmasi/registrasi (now serving + antrian berikutnya) | `GET /display/{area}` |
| UC-37 | Client Display per ruang (jadwal dokter + foto + status sesi) | `GET /display/client/{room}` |
| UC-38 | Suara panggilan diputar lokal per layar (mode `local`, default) ATAU dari satu PC pusat (mode `central`) | `GET /display/speaker` |
| UC-39 | Indikator "koneksi terputus" muncul setelah 2x poll gagal berturut | - |

### Sistem (background)

| ID | Use Case | Mekanisme |
|----|----------|-----------|
| UC-40 | Tarik pasien baru dari `appointment` ke tabel `antrian` lokal | `AntrianSync::pullThrottled()` - dipanggil tiap buka halaman Klinik/Dashboard, throttle 3 detik; juga scheduled task tiap 1 menit (`scheduler.bat` → Windows Task Scheduler) |
| UC-41 | Booking (belum check-in) naik status jadi antrian nyata begitu check-in | `AntrianSync::pull()` - tahap yang sedang berjalan TIDAK ditimpa |
| UC-42 | Buang baris booking yang appointment sumbernya sudah dibatalkan (voided) | `AntrianSync::removeVoidedBookings()` |
| UC-43 | Render & cache suara panggilan (Edge TTS), fallback ke Web Speech API browser bila gagal/timeout | `SpeechSynthesizer` |

---

## 3. Login — kasus gagal (UC-01c)

| Kasus | Yang terjadi |
|---|---|
| Username/password salah | Pesan "Username atau password salah." |
| Password dbuser benar tapi tak ada akses lokal | "Akun ini belum diberi akses ke aplikasi antrian. Hubungi administrator." |
| Akun diblokir (`is_blocked`) | "Akun Anda diblokir. Hubungi administrator." |
| >5 percobaan/menit (username+IP sama) | HTTP 429, pesan throttle |
| Dokter login, ruang jadwalnya sudah ditempati dokter lain | Ditolak, sesi di-flush lagi, pesan bentrok ruang di halaman login |
| DB dbuser/master mati saat login | "Tidak dapat memverifikasi login - cek koneksi database." (tidak bocor detail error) |

---

## 4. Menyiapkan pasien untuk diuji

### A. Data DUMMY (paling praktis untuk tes UI)

```bash
cd C:\xampp\htdocs\antrian

# 3 pasien di tahap KLINIK untuk dokter akun "klinik" (paramedic 124):
php artisan antrian:seed-test --tahap=klinik --paramedic=124 --jumlah=3 --fresh

# Langsung di tahap FARMASI / KASIR (cepat tes layar tunggu):
php artisan antrian:seed-test --tahap=farmasi --jumlah=3 --fresh
php artisan antrian:seed-test --tahap=kasir --jumlah=3 --fresh
```

Opsi: `--tahap=klinik|kasir|farmasi`, `--jumlah=`, `--paramedic=124`, `--fresh`,
`--resep=racik|non_racik|non_resep`.

### B. Data NYATA dari appointment

```bash
php artisan antrian:sync
```

> Di produksi, pasien baru dari appointment ditarik otomatis (throttle 3 detik)
> saat halaman Klinik/Dashboard dibuka - **tak wajib** menunggu scheduled task,
> tapi scheduled task tetap dipasang sebagai jaring pengaman kalau kebetulan
> tak ada yang buka halaman manapun untuk sementara waktu.

---

## 5. Alur bisnis utama (Klinik → Kasir → Farmasi)

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

Jalur **Registrasi** (tiket RG dari kiosk) TERPISAH dari alur ini - lihat UC-18..22.

---

## 6. Skenario testing UTAMA (rantai penuh dgn resep)

**Siapkan pasien di tahap klinik:**
```bash
php artisan antrian:seed-test --tahap=klinik --paramedic=124 --jumlah=3 --fresh
```

1. **Dokter**: login `klinik`/`klinik` → **▶ Panggil** → **✔ Tanpa Resep** /
   **💊 Racik** / **💊 Non-Racik** → logout.
2. **Kasir**: login `kasir`/`kasir` → pilih Counter 1 → **▶ Panggil** →
   **✔ Selesai** (pilih resep bila belum) → logout.
3. Buka `/display/farmasi` di jendela lain (tanpa login) - masih idle.
4. **Farmasi**: login `farmasi`/`farmasi` → pilih jenis sesuai resep + Counter 1
   → **▶ Panggil** → cek layar tunggu berbunyi & berkedip → **✔ Selesai**.
5. **Kasir** lagi: pasien muncul status "Resep CLEAR" → **▶ Panggil** →
   **✔ Selesai** → pasien SELESAI. ✅

**Rantai penuh**: `Klinik → Kasir → Farmasi → Kasir → Selesai`

---

## 7. Testing per-modul

### Registrasi (tiket RG dari kiosk)
1. Buat pasien RG lewat kiosk app `appointment` (atau isi manual tabel
   `kiosk_registrations` via tinker untuk tes cepat).
2. Login sebagai `registrasi` → pilih counter → **▶ Panggil** → cek
   `/display/registrasi` berbunyi & tampil nomor RG.
3. **✔ Selesai** → RG masuk riwayat "Sudah Ditangani", bisa dipanggil ulang.

### SPV (monitor lintas klinik)
1. Login `spv`/`spv` → beranda kosong dengan ajakan "Pilih Klinik".
2. Klik **Ganti Klinik** → modal checklist → pilih 2+ klinik → Simpan →
   tabel per klinik muncul (scrollable, 5 baris awal terlihat).
3. Klik **Ganti Dokter** → modal per-klinik, centang 2+ dokter satu klinik
   (coba **Pilih Semua**/**Kosongkan**) → Simpan → tabel klinik itu terfilter
   ke dokter terpilih saja; klinik lain tetap tampil semua dokter.
4. Refresh halaman manual (F5) dan tunggu 20 detik (auto-refresh) → filter
   klinik & dokter **tetap** tersimpan (baca dari sesi, bukan hilang).
5. Coba urutkan kolom (klik header) → ikon panah berubah arah.

### Kasir/Farmasi/Registrasi — isolasi antar-counter (regresi keamanan)
1. Buka 2 sesi: Counter 1 dan Counter 2 (role sama, mis. Kasir).
2. Counter 1 panggil pasien A. Panel "Sedang Dipanggil" di Counter 2 ikut
   menampilkan pasien A (read-only, LINTAS counter - disengaja).
3. Di Counter 2, cek baris pasien A: **tidak ada tombol aksi**, cuma badge
   "Counter lain". (Sebelumnya ini bisa diklik dan benar-benar mengubah
   punya Counter 1 - sudah diperbaiki, lihat §8.)

### Admin — Manajemen User
1. Login `admin`/`123456.` → **Manajemen User** → **Tambah User** → cari
   pegawai dari direktori RS → pilih **Hak Akses** (Klinik → pilih Dokter) →
   simpan.
2. Edit/Hapus/Blokir user.

### Admin — Zona Klinik
1. Menu **Zona Klinik** → tambah/edit zona (kode, nama, pasangan ruang).
2. Ubah **Kode Zona** pada zona existing → muncul peringatan sebelum submit.
3. Buka `/display` → kartu "Zona Klinik" mengikuti data terbaru.

### Admin — Media (Banner + Video)
1. Menu **Media** → tab **Banner** → upload gambar → muncul di grid + tampil
   slideshow di layar tunggu (bisa dibatasi per klinik).
2. Tab **Video** → upload → **Aktifkan** salah satu → hanya yang aktif diputar.
3. Ubah **Running Text** → tampil berjalan di bawah layar tunggu.

### Suara panggilan (TTS)
1. Pastikan `.env`: `TTS_ENABLED=true`, `TTS_ENGINE=edge`, internet aktif.
2. Panggil pasien apa saja → dengar urutan: **bel pembuka → nomor dieja per
   digit/huruf ("F, D, nol, nol, satu") → tujuan ruangan/counter → bel penutup**.
3. Dua counter memanggil BERSAMAAN → suara ANTRE (FIFO), tidak tumpang tindih
   (lihat `soundQueue` di display, atau `AnnouncementQueue` bila mode `central`).
4. Matikan internet server sesaat lalu panggil pasien → setelah maks ~8 detik
   otomatis jatuh ke suara browser (Web Speech API), TIDAK menggantung lama.

### Dashboard (kartu data nyata)
1. Registrasi pasien baru di appointment app.
2. **Tanpa membuka halaman lain**, langsung buka Dashboard antrian (`/`) →
   kartu **Total Pasien**/**Antrian Klinik** harus ikut bertambah (Dashboard
   menarik sync sendiri, tidak bergantung scheduled task/halaman Klinik).

### Master (read-only)
- **Daftar Klinik**, **Daftar Dokter** (edit foto - lihat UC-29), **Jadwal Dokter**.

---

## 8. Temuan & perbaikan keamanan/logic penting (riwayat)

Dicatat supaya tidak regresi. Semua sudah ada test otomatisnya (§9).

| Temuan | Perbaikan |
|---|---|
| 2 counter memanggil bersamaan → suara tumpang tindih | Antrean suara client-side (`soundQueue`) - FIFO |
| Jeda lama antara bel & suara (TTS Edge lambat) | Bell & fetch TTS jalan konkuren (`Promise.all`); render TTS server dibatasi timeout 8 detik |
| Nomor tiket dibaca berpasangan ("nol satu" nyambung) | Dieja per-digit dgn jeda jelas |
| Registrasi mengeja "C-O-U-N-T-E-R" tiap panggilan | `cleanDest()` membuang prefix "Counter " sebelum diucapkan (konsisten dgn Kasir/Farmasi) |
| Counter LAIN bisa Recall/Selesai-kan pasien counter lain (Kasir/Farmasi/Registrasi) | Cek kepemilikan counter di controller; UI ganti jadi badge read-only |
| Dokter LAIN bisa menyelesaikan (`selesai()`) pasien dokter lain | Cek `paramedic_id` sesi vs pemilik baris |
| Sesi `spv_dokter` format lama (single ID) → `TypeError` | Dinormalkan jadi array di `SpvController::beranda()` |
| Dashboard tampil 0 walau pasien sudah check-in | Dashboard ikut memanggil `pullThrottled()` sendiri, tak lagi 100% bergantung scheduled task |
| `APP_DEBUG=true` di `.env` (bocor stack trace ke publik) | **Belum diperbaiki - WAJIB `false` sebelum live**, lihat catatan produksi di bawah |
| `SQLSRV_DATABASE=MS_CHSBY_TEST` (MEDINFRAS masih ke DB test) | **Perlu dikonfirmasi & diganti ke DB production** saat migrasi ke live |

---

## 9. Test otomatis

```bash
php artisan test
```

| File | Cakupan |
|---|---|
| `Unit/AntrianHasActiveCallTest.php` | Guard "1 panggilan aktif" per counter/dokter (model) |
| `Unit/KioskRegistrationHasActiveCallTest.php` | Sama, untuk tiket RG |
| `Unit/ZoneTest.php` | Parsing teks pasangan ruang, cache zona |
| `Feature/KlinikDoubleCallGuardTest.php` | UC-02/03/05 + isolasi antar-dokter |
| `Feature/KasirDoubleCallGuardTest.php` | UC-10/11/12 + isolasi antar-counter |
| `Feature/FarmasiDoubleCallGuardTest.php` | UC-15/16/17 + isolasi antar-counter |
| `Feature/RegistrasiDoubleCallGuardTest.php` | UC-20/21/22 + isolasi antar-counter |
| `Feature/AuthTest.php` | UC-01c: login lokal berhasil/gagal, akun diblokir, rate-limit |
| `Feature/AntrianSyncTest.php` | UC-40/41/42: idempotensi, promosi booking→check-in, hapus voided |
| `Feature/DashboardSyncTest.php` | Regresi bug "Dashboard tampil 0" |
| `Feature/RoleAccessTest.php` | Matriks akses role vs route terlindungi |
| `Feature/SpvControllerTest.php` | UC-24/25/26: filter klinik/dokter, kompatibilitas sesi lama |
| `Feature/ZoneControllerTest.php` | UC-30: CRUD zona, hanya admin |
| `Feature/ExampleTest.php` | Smoke test: tamu diarahkan ke login |

Jalankan satu file: `php artisan test --filter=AntrianSyncTest`

---

## 10. Reset data testing

```bash
# Hapus semua antrian hari ini:
php artisan tinker --execute="App\Models\Antrian::whereDate('tanggal',today())->delete(); echo 'cleared';"

# Atau isi ulang dari nol:
php artisan antrian:seed-test --tahap=klinik --paramedic=124 --fresh
```

---

## 11. Yang BELUM ada (jangan diuji dulu)

- **Admisi Rajal/LAB, IGD, Radiologi** — role sudah ada, UI belum dibuat.
- **Deteksi resep otomatis dari MEDINFRAS** — status resep masih dipilih
  DOKTER manual (racik/non-racik/tanpa). Detail obat dari MEDINFRAS belum dibaca.
- **Laporan Antrian** (rekap/export).

---

## 12. Masalah umum

| Masalah | Solusi |
|---|---|
| **419 Page Expired / logout sendiri** | Hapus cookie situs / pakai Incognito. Pastikan `APP_URL` di `.env` = host yang kamu akses. |
| Antrian dokter kosong | Jalankan `php artisan antrian:seed-test` atau buka halaman (auto-sync). |
| Pasien ber-resep tak muncul di Farmasi | Pastikan jenis counter Farmasi (Racik/Non-Racik) **cocok** dgn status resep pasien. |
| Layar tunggu tak update | Normal, poll tiap beberapa detik. Pastikan sudah ada yang memanggil. |
| Login ditolak | Pastikan user terdaftar di Manajemen User & tak diblokir. |
| Login ditolak terus walau password benar | Cek belum kena rate-limit (5x/menit) - tunggu 1 menit. |
| Tak bisa login 2 app (antrian + appointment) | Cookie sudah dipisah (`antrian_session` / `appointment_session`). Hapus cookie lama kalau masih bentrok. |
| Suara tidak keluar / lama sekali | Cek internet server (Edge TTS butuh koneksi). Setelah 8 detik otomatis jatuh ke suara browser. |
