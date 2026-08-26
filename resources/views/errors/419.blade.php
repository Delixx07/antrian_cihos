<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sesi Berakhir - CIHOS Queue</title>
    <style>
        *{box-sizing:border-box;}
        body{font-family:'Segoe UI',Tahoma,system-ui,sans-serif;background:#f5f7fb;color:#1f2540;
            min-height:100vh;display:flex;align-items:center;justify-content:center;text-align:center;margin:0;padding:1.5rem;}
        .box{max-width:26rem;}
        .ic{font-size:2.4rem;line-height:1;}
        h1{font-size:1.15rem;margin:.8rem 0 .4rem;color:#0a2a66;}
        p{font-size:.9rem;color:#8a94a6;line-height:1.6;margin:0;}
        a{color:#2563eb;font-weight:600;text-decoration:none;}
        a:hover{text-decoration:underline;}
    </style>
</head>
<body>
    <div class="box">
        <div class="ic">⏳</div>
        <h1>Sesi sudah berakhir</h1>
        <p>
            Halaman ini sempat tidak aktif terlalu lama. Mengarahkan ulang…<br>
            Kalau tidak otomatis pindah, <a href="{{ url('/login') }}" id="fallback">klik di sini</a>.
        </p>
    </div>
    <script>
        /*
         * location.replace() (BUKAN location.href) - mengganti entri riwayat
         * halaman ini, bukan menambah. Tanpa ini, halaman "419 hasil POST"
         * tetap tersimpan di riwayat tab; kalau browser MENDISKON tab ini
         * karena idle lalu memulihkannya nanti, ia bisa diam-diam mengirim
         * ULANG POST yang sama (form resubmission) dan "expired" muncul lagi
         * padahal penggunanya tidak menekan apa pun. Redirect via replace()
         * di sini menutup celah itu - begitu halaman ini dirender, ia langsung
         * pindah ke GET biasa, tidak ada lagi "hasil POST" yang bisa dipulihkan.
         */
        var dest = (document.referrer && document.referrer.indexOf(location.origin) === 0)
            ? document.referrer
            : @json(url('/login'));
        location.replace(dest);
    </script>
</body>
</html>
