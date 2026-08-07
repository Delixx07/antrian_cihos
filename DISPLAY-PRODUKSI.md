# Display & Antrian — Setup Produksi (URL langsung `/display`)

Aplikasi **display** kini menyatu di dalam Laravel **antrian** (1 codebase).
Route yang tersedia:

| URL (di dalam antrian)        | Halaman                          |
|-------------------------------|----------------------------------|
| `/display`                    | Menu pemilihan display           |
| `/display/klinik` (kasir/farmasi) | Main Display per area         |
| `/display/klinik?floor=18`    | Main Display per zona            |
| `/display/client/1859`        | Client Display 1 ruang           |
| `/display/client/1859,1860`   | Client Display 2 ruang           |

Di server **dev** ini, antrian ada di subfolder → aksesnya `http://IP/antrian/display`.

## Agar `/display` LANGSUNG (tanpa `/antrian`) — pola antrian_old

Sama seperti antrian_old yang aksesnya `/dashboard` (bukan `/antrian_old/dashboard`),
caranya: **jadikan `antrian/public` sebagai DocumentRoot** web server.

### Langkah (di server produksi):

1. Edit `C:\xampp\apache\conf\httpd.conf`, ubah:
   ```apache
   DocumentRoot "C:/xampp/htdocs"
   <Directory "C:/xampp/htdocs">
   ```
   menjadi:
   ```apache
   DocumentRoot "C:/xampp/htdocs/antrian/public"
   <Directory "C:/xampp/htdocs/antrian/public">
   ```
   (atau pakai VirtualHost khusus — lebih rapi bila ada beberapa app)

2. Pastikan `AllowOverride All` pada blok Directory itu (agar `.htaccess` Laravel jalan).

3. **Restart Apache** (via XAMPP Control Panel).

Setelah itu:
- `http://IP/display` → menu display **langsung** ✓
- `http://IP/display/client/1859` → client display ✓
- `http://IP/login`, `/antrian`, dll juga langsung dari root.

### Catatan
- Timezone sudah di-set **Asia/Jakarta** (`config/app.php`) — konsisten dgn appointment & MySQL.
- Folder lama `c:\xampp\htdocs\display` (PHP polos) sudah tak dipakai — boleh dihapus
  setelah verifikasi produksi OK.
- Menu memakai CDN (Tailwind + Material Symbols + Google Fonts) → butuh internet.
  Bila server produksi tanpa internet, minta di-host lokal (bisa disiapkan terpisah).
