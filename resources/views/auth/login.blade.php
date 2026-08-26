<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login</title>
    <link rel="icon" href="{{ asset('cihoslogo.png') }}" type="image/png">
    <style>
        :root{
            --navy:#0a2a66;
            --navy-2:#123a8a;
            --ink:#2b3350;
            --muted:#98a2b3;
            --line:#eceef3;
            --field:#f4f5f8;
        }
        *{box-sizing:border-box;margin:0;padding:0;}
        body{
            min-height:100vh;font-family:'Segoe UI',Tahoma,system-ui,sans-serif;color:var(--ink);
            display:grid;grid-template-columns:70% 30%;background:#fff;
        }

        /* ===== KIRI: ilustrasi (gambar berlatar putih → beri latar biru-soft
           yang menyatu + garis pemisah supaya batas ke form jelas) ===== */
        .illus{
            position:relative;overflow:hidden;background:#d7e8fb; /* senada latar gambar */
            display:flex;align-items:center;justify-content:center;
        }
        /* cover + rata kiri: gambar mengisi penuh (tak ada ruang kosong),
           karakter menempel ke kiri; sisi KANAN gambar yang terpotong. */
        .illus img{width:100%;height:100%;object-fit:cover;object-position:left center;display:block;}

        /* ===== KANAN: form ===== */
        .side{display:flex;align-items:center;justify-content:center;padding:2rem 1.5rem;}
        .box{width:100%;max-width:330px;}
        .box .logo{margin-bottom:1.8rem;text-align:center;}
        .box .logo img{height:130px;width:auto;}
        .box h1{font-size:1.6rem;font-weight:800;color:#1f2540;line-height:1.25;}
        .box .sub{color:var(--muted);font-size:.9rem;margin-top:.5rem;margin-bottom:1.8rem;line-height:1.5;}

        .err{background:#fdecec;border:1px solid #f5b5b5;color:#b42318;font-size:.82rem;
            padding:.6rem .8rem;border-radius:8px;margin-bottom:1rem;text-align:center;}

        /* floating label (animasi label naik) */
        .field{position:relative;margin-bottom:1.4rem;}
        .field input{
            width:100%;padding:.95rem 1rem;border:1.5px solid var(--line);border-radius:9px;
            background:var(--field);color:var(--ink);font-size:.95rem;transition:.16s;
        }
        .field input::placeholder{color:transparent;}
        .field input:focus{outline:none;border-color:var(--navy);background:#fff;box-shadow:0 0 0 4px #0a2a6618;}
        .field label{
            position:absolute;left:1rem;top:50%;transform:translateY(-50%);color:var(--muted);
            font-size:.95rem;pointer-events:none;background:var(--field);padding:0 .25rem;
            transition:transform .16s ease-in, top .16s, font-size .16s, color .16s, background .16s;
        }
        .field input:focus + label,
        .field input:not(:placeholder-shown) + label{
            top:-.55rem;transform:translateY(0);left:.75rem;
            font-size:.72rem;font-weight:700;color:var(--navy);background:#fff;
        }
        .field .show{position:absolute;right:.9rem;top:50%;transform:translateY(-50%);background:none;border:none;
            color:var(--muted);cursor:pointer;z-index:2;display:flex;padding:.25rem;border-radius:6px;transition:.15s;}
        .field .show:hover{color:var(--navy);background:#eef2fb;}
        .field .show svg{width:20px;height:20px;}
        .field .show .eye-off{display:none;}
        .field .show.on .eye{display:none;}
        .field .show.on .eye-off{display:block;}

        .row{display:flex;align-items:center;justify-content:space-between;margin:.3rem 0 1.4rem;}
        .row label{display:flex;align-items:center;gap:.45rem;font-size:.85rem;color:#556;cursor:pointer;}
        .row input{width:15px;height:15px;accent-color:var(--navy);cursor:pointer;}

        .btn{
            width:100%;padding:.85rem;border:none;border-radius:8px;cursor:pointer;
            background:var(--navy);color:#fff;font-size:.98rem;font-weight:700;transition:.18s;
        }
        .btn:hover{background:var(--navy-2);transform:translateY(-1px);box-shadow:0 8px 20px rgba(10,42,102,.28);}
        .btn:active{transform:translateY(0);}

        .foot{margin-top:2.2rem;font-size:.82rem;color:var(--muted);}
        .foot b{color:#1f2540;}

        @media (max-width:820px){
            body{grid-template-columns:1fr;}
            .illus{min-height:220px;}
        }
    </style>
</head>
<body>
    {{-- KIRI: ilustrasi --}}
    <div class="illus">
        <img src="{{ asset('login_foto.png') }}" alt="Ilustrasi Ciputra Hospital">
    </div>

    {{-- KANAN: form login --}}
    <div class="side">
        <div class="box">
            <div class="logo"><img src="{{ asset('cihoslogo.png') }}" alt="Ciputra Hospital Surabaya"></div>

            <h1>CIHOS Queue</h1>
            <p class="sub">Sign in to continue to the Queue System.</p>

            @if ($errors->any())
                <div class="err">{{ $errors->first() }}</div>
            @endif

            @if (session('room_conflict'))
                <div class="err" style="background:#fff7e6;border-color:#f5c26b;color:#a15c00;">
                    Ruang <b>{{ session('room_conflict')['room'] }}</b> sudah ditempati
                    <b>{{ session('room_conflict')['doctor'] }}</b>. Satu ruang hanya untuk satu dokter.
                </div>
            @endif

            <form method="post" action="{{ route('login.attempt') }}">
                @csrf
                <div class="field">
                    <input id="username" name="username" type="text" placeholder="Username"
                           value="{{ old('username') }}" autocomplete="off" required autofocus>
                    <label for="username">Username</label>
                </div>

                <div class="field">
                    <input id="password" name="password" type="password" placeholder="Password" required>
                    <label for="password">Password</label>
                    <button type="button" class="show" onclick="togglePw(this)" aria-label="Show password">
                        <svg class="eye" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8M1.173 8a13 13 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5s3.879 1.168 5.168 2.457A13 13 0 0 1 14.828 8q-.086.13-.195.288c-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5s-3.879-1.168-5.168-2.457A13 13 0 0 1 1.172 8z"/><path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5M4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0"/></svg>
                        <svg class="eye-off" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M13.359 11.238C15.06 9.72 16 8 16 8s-3-5.5-8-5.5a7 7 0 0 0-2.79.588l.77.771A6 6 0 0 1 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13 13 0 0 1 14.828 8q-.086.13-.195.288c-.335.48-.83 1.12-1.465 1.755q-.247.248-.517.486z"/><path d="M11.297 9.176a3.5 3.5 0 0 0-4.474-4.474l.823.823a2.5 2.5 0 0 1 2.829 2.829zm-2.943 1.299.822.822a3.5 3.5 0 0 1-4.474-4.474l.823.823a2.5 2.5 0 0 0 2.829 2.829"/><path d="M3.35 5.47q-.27.24-.518.487A13 13 0 0 0 1.172 8l.195.288c.335.48.83 1.12 1.465 1.755C4.121 11.332 5.881 12.5 8 12.5c.716 0 1.39-.133 2.02-.36l.77.772A7 7 0 0 1 8 13.5C3 13.5 0 8 0 8s.939-1.721 2.641-3.238zM1 1l14 14-.707.707L.293 1.707z"/></svg>
                    </button>
                </div>

                <div class="row">
                    <label><input type="checkbox" name="remember" value="1" {{ (old('remember') || !old('username')) ? 'checked' : '' }}> Remember Me</label>
                </div>

                <button class="btn" type="submit">Login</button>
            </form>

            <div class="foot">© {{ date('Y') }} Ciputra Hospital Surabaya - Queue System</div>
        </div>
    </div>

    <script>
        function togglePw(btn){
            var i=document.getElementById('password');
            var show=i.type==='password';
            i.type=show?'text':'password';
            btn.classList.toggle('on', show); // tampilkan ikon mata-tercoret saat password terlihat
        }
    </script>
</body>
</html>
