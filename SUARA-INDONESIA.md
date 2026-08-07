# Suara Pemanggilan Antrian (Bahasa Indonesia)

Layar memanggil pasien dengan gaya **pengumuman bandara** — diputar dua kali:

```
1) "Perhatian. Nomor antrian, ef de nol nol lima,
    dipersilakan menuju ruang 1102. Terima kasih."     (~9,5 detik)

    ...jeda 1,2 detik...

2) "Nomor antrian, ef de nol nol lima,
    dipersilakan menuju ruang 1102."                   (~6,8 detik)
```

Putaran kedua sengaja **lebih ringkas** (tanpa "Perhatian" & "Terima kasih")
agar total durasi tetap wajar (~17 detik) tanpa kehilangan kesan formal.

Nomor antrian **dieja** (FD005 → "ef de nol nol lima") agar tiap karakter jelas
terdengar; nomor ruang/loket **dibaca utuh** (1102 → "seribu seratus dua")
karena itu yang alami diucapkan.

### Mengatur tempo bicara

`TTS_LENGTH_SCALE` di `.env` — nilai **lebih besar = lebih pelan**:

| Nilai | Kesan |
|---|---|
| `1.00` | normal, agak cepat |
| `1.15` | **default** — tenang & berwibawa |
| `1.30` | sangat pelan (durasi jadi ~21 detik) |

Setelah mengubah, jalankan `php artisan config:clear`. Berkas cache lama
tidak perlu dihapus — nama berkas ikut menyertakan konfigurasi.

> **Catatan:** Piper hanya menyediakan **satu** voice Indonesia
> (`id_ID-news_tts-medium`), jadi karakter suara tidak bisa diganti.
> Yang bisa diatur hanya susunan kalimat & tempo bicara.

## Cara kerja

Ada dua jalur, otomatis:

| | Sumber suara | Kualitas | Setup |
|---|---|---|---|
| **Utama** | **Piper TTS di server** | Natural, **sama persis di semua layar** | Sekali di server |
| Cadangan | Web Speech API browser | Bergantung voice perangkat | Per perangkat |

Selama Piper terpasang di server, **semua layar** (Windows, Android, TV box)
memutar berkas audio yang sama — tidak perlu memasang voice di tiap perangkat.
Bila Piper belum terpasang, layar otomatis memakai suara browser sebagai
cadangan sehingga sistem tetap berfungsi.

Hasil render **di-cache** di `public/tts`, jadi tiap kalimat hanya dirender
sekali. Nomor yang sama dipanggil ulang → langsung pakai berkas yang ada.

---

## Pasang Piper di server (sekali saja)

> **Status server ini: SUDAH TERPASANG & AKTIF.**
> Piper ada di `C:\piper\piper.exe` dengan voice `id_ID-news_tts-medium`.
> Bagian di bawah untuk memasang di server lain.

### 1. Unduh Piper

<https://github.com/rhasspy/piper/releases> → ambil `piper_windows_amd64.zip`
→ ekstrak ke **`C:\piper`** sehingga ada `C:\piper\piper.exe`.

> Zip berisi folder `piper/` di dalamnya. Bila diekstrak apa adanya ke
> `C:\piper`, hasilnya jadi `C:\piper\piper\piper.exe` (dua level).
> Pindahkan isinya naik satu level, atau sesuaikan `TTS_BINARY` di `.env`.

### 2. Unduh voice Indonesia

<https://huggingface.co/rhasspy/piper-voices/tree/main/id/id_ID>

Ambil **dua** berkas dari salah satu voice (mis. `news_tts`, kualitas `medium`):

- `id_ID-news_tts-medium.onnx`
- `id_ID-news_tts-medium.onnx.json`  ← wajib ikut, jangan hanya .onnx

Simpan di folder yang sama dengan `piper.exe`.

### 3. Cek dari terminal

Jalankan di PowerShell (**satu baris**, jangan ikut menyalin tanda ``` dari
dokumen ini):

```
cd C:\piper
"Nomor antrian ef de nol nol lima." | .\piper.exe --model id_ID-news_tts-medium.onnx --output_file test.wav
```

Putar `test.wav`. Bila terdengar suara Indonesia, Piper siap.

### 4. Arahkan konfigurasi

Default sudah menunjuk ke `C:\piper\`. Bila lokasinya berbeda,
tambahkan ke `.env`:

```env
TTS_BINARY="C:\piper\piper.exe"
TTS_MODEL="C:\piper\id_ID-news_tts-medium.onnx"
TTS_LENGTH_SCALE=1.15    # >1 lebih pelan, <1 lebih cepat
TTS_ENABLED=true         # false = paksa pakai suara browser
```

Lalu jalankan `php artisan config:clear`.

### Verifikasi lewat aplikasi

Buka di browser:

```
http://<server>/display/speech?no=FD005&dest=1102&area=klinik
```

- `{"url":"http://.../tts/xxx.wav","text":"..."}` → Piper aktif.
- `{"url":null,...,"reason":"piper-unavailable"}` → Piper belum terbaca,
  cek lagi lokasi `piper.exe` & berkas `.onnx`.

---

## PENTING: klik layar sekali agar suara keluar

Browser (Chrome/Edge) **memblokir suara otomatis** sampai ada interaksi
pengguna di halaman. Ini aturan browser, bukan bug aplikasi. Gejalanya:
nomor tampil & berkedip, tapi **tidak ada suara sama sekali**.

**Solusi:** setelah membuka halaman layar, **klik layar satu kali**.
Bila suara masih terblokir, aplikasi menampilkan penanda
"Klik layar untuk mengaktifkan suara" — klik saja penandanya. Setelah itu
suara berfungsi terus selama halaman tidak dimuat ulang.

### Agar tidak perlu klik tiap kali (untuk layar kiosk)

Jalankan Chrome dengan flag berikut saat membuka layar:

```
chrome.exe --autoplay-policy=no-user-gesture-required --kiosk http://localhost/antrian/display/klinik
```

Buat shortcut di desktop dengan Target seperti di atas, lalu jadikan
shortcut itu yang dipakai/di-autostart di PC layar. Dengan flag ini suara
langsung berbunyi tanpa perlu diklik.

## Catatan

- **Extension browser (mis. Read Aloud) tidak bisa dipakai.** Extension hanya
  membacakan halaman atas permintaan pengguna dan tidak bisa dipanggil oleh
  JavaScript halaman. Layar antrian harus bersuara sendiri tanpa operator.
- **Tanpa internet.** Piper berjalan offline sepenuhnya, tanpa API key dan
  tanpa biaya langganan.
- **Folder cache** `public/tts` dibersihkan otomatis dari berkas yang lebih
  tua dari 30 hari.
- Mengubah `TTS_MODEL` atau `TTS_LENGTH_SCALE` otomatis membuat berkas cache
  baru (nama berkas ikut menyertakan konfigurasi), jadi tidak perlu
  menghapus cache manual.
