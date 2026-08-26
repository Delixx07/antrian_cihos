<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pilih Ruang - CIHOS Queue</title>
    <link rel="icon" href="{{ asset('cihoslogo.png') }}" type="image/png">
    <style>
        :root{--navy:#0a2a66;--navy2:#123a8a;--ink:#2b3350;--muted:#98a2b3;--line:#eceef3;}
        *{box-sizing:border-box;margin:0;padding:0;}
        body{min-height:100vh;font-family:'Segoe UI',Tahoma,system-ui,sans-serif;color:var(--ink);
            display:flex;align-items:center;justify-content:center;padding:2rem;
            background:linear-gradient(180deg,#eef3fb,#e3eaf5);}
        .box{width:100%;max-width:460px;background:#fff;border-radius:18px;padding:2rem;
            box-shadow:0 20px 60px rgba(10,42,102,.18);}
        .logo{text-align:center;margin-bottom:1.2rem;}
        .logo img{height:84px;}
        h1{font-size:1.4rem;font-weight:800;color:#1f2540;text-align:center;}
        .sub{color:var(--muted);font-size:.9rem;text-align:center;margin:.4rem 0 1.6rem;}
        .warn{background:#fff7e6;border:1px solid #f5c26b;color:#a15c00;font-size:.85rem;
            padding:.7rem .9rem;border-radius:9px;margin-bottom:1.2rem;text-align:center;}
        .rooms{display:flex;flex-direction:column;gap:.8rem;margin-bottom:.5rem;}
        .room{display:flex;align-items:center;gap:1rem;width:100%;text-align:left;cursor:pointer;
            background:#f4f7fd;border:1.5px solid #e2e8f4;border-radius:12px;padding:1rem 1.15rem;
            font:inherit;color:inherit;transition:.15s;}
        .room:hover{border-color:var(--navy);background:#fff;box-shadow:0 8px 20px rgba(10,42,102,.12);transform:translateY(-2px);}
        .room .ic{width:46px;height:46px;border-radius:11px;background:#e7effc;color:var(--navy);
            display:flex;align-items:center;justify-content:center;flex-shrink:0;}
        .room .ic svg{width:24px;height:24px;}
        .room .t{min-width:0;}
        .room .t .code{font-size:1.15rem;font-weight:800;color:#1f2540;}
        .room .t .lbl{font-size:.82rem;color:var(--muted);}
        .room.busy{cursor:not-allowed;opacity:.72;background:#faf0f0;border-color:#f0d0d0;}
        .room.busy:hover{transform:none;box-shadow:none;border-color:#f0d0d0;}
        .room.busy .ic{background:#fde8e8;color:#b42318;}
        .room.busy .t .lbl{color:#b42318;font-weight:600;}
        .foot{margin-top:1.4rem;text-align:center;}
        .foot a{color:var(--muted);font-size:.82rem;text-decoration:none;}
        .foot a:hover{color:var(--navy);}
    </style>
</head>
<body>
    <div class="box">
        <div class="logo"><img src="{{ asset('cihoslogo.png') }}" alt="Ciputra Hospital"></div>
        <h1>Pilih Ruang Praktik</h1>
        <p class="sub">Anda terjadwal di beberapa ruang hari ini. Pilih ruang yang Anda tempati sekarang.</p>

        @if (session('room_conflict'))
            <div class="warn">
                Ruang <b>{{ session('room_conflict')['room'] }}</b> sudah ditempati
                <b>{{ session('room_conflict')['doctor'] }}</b>. Silakan pilih ruang lain.
            </div>
        @endif
        @if ($errors->any())
            <div class="warn">{{ $errors->first() }}</div>
        @endif

        <div class="rooms">
            @foreach ($rooms as $room)
                @php $busy = isset($occupied[$room]); @endphp
                @if ($busy)
                    <div class="room busy" title="Sudah ditempati">
                        <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 21V5a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v16M4 21h16M14 12h.01"/></svg></span>
                        <span class="t"><span class="code">Ruang {{ $room }}</span><span class="lbl">Ditempati {{ $occupied[$room] }}</span></span>
                    </div>
                @else
                    <form method="post" action="{{ route('room.pick') }}">
                        @csrf
                        <input type="hidden" name="room_code" value="{{ $room }}">
                        <button type="submit" class="room">
                            <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 21V5a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v16M4 21h16M14 12h.01"/></svg></span>
                            <span class="t"><span class="code">Ruang {{ $room }}</span><span class="lbl">Klik untuk masuk ruang ini</span></span>
                        </button>
                    </form>
                @endif
            @endforeach
        </div>

        <div class="foot">
            <form method="post" action="{{ route('logout') }}">@csrf
                <a href="#" onclick="this.closest('form').submit();return false;">Batal &amp; keluar</a>
            </form>
        </div>
    </div>
</body>
</html>
