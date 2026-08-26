<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Antrian Ciputra Hospital</title>
    <style>
        body{font-family:'Segoe UI',system-ui,sans-serif;background:#f1f5f9;margin:0;padding:2rem;color:#0f172a;}
        .bar{display:flex;align-items:center;justify-content:space-between;max-width:900px;margin:0 auto 1.5rem;}
        .bar b{font-size:1.1rem;}
        form{margin:0;}
        .logout{background:#ef4444;color:#fff;border:none;padding:.5rem 1rem;border-radius:8px;cursor:pointer;font-weight:600;}
        .card{max-width:900px;margin:0 auto;background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:2rem;}
    </style>
</head>
<body>
    <div class="bar">
        <b>Sistem Antrian - Ciputra Hospital Surabaya</b>
        <form method="post" action="{{ route('logout') }}">
            @csrf
            <button class="logout" type="submit">Keluar</button>
        </form>
    </div>
    <div class="card">
        <p>Selamat datang, <b>{{ session('name') }}</b>.</p>
        <p style="margin-top:.5rem;color:#64748b;">Halaman utama antrian. Menu (Master, Antrian, dll) akan ditambahkan di sini.</p>
    </div>
</body>
</html>
