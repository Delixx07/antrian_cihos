<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — CIHOS Queue</title>
    <link rel="icon" href="{{ asset('cihoslogo.png') }}" type="image/png">
    <style>
        :root{
            --navy:#0a2a66; --navy-2:#123a8a; --brand:#2563eb;
            --ink:#1f2540; --muted:#8a94a6; --line:#eef0f4;
            --bg:#f5f7fb; --side:#ffffff; --active:#eef4ff;
        }
        *{box-sizing:border-box;margin:0;padding:0;}
        body{font-family:'Segoe UI',Tahoma,system-ui,sans-serif;color:var(--ink);background:var(--bg);}

        .layout{display:grid;grid-template-columns:248px 1fr;min-height:100vh;}

        /* ===== Sidebar ===== */
        .sidebar{background:var(--side);border-right:1px solid var(--line);display:flex;flex-direction:column;
            position:sticky;top:0;height:100vh;}
        .sidebar .brand{padding:1.4rem 1.5rem;border-bottom:1px solid var(--line);display:flex;justify-content:center;}
        .sidebar .brand img{height:48px;width:auto;}
        .nav{flex:1;overflow-y:auto;padding:1rem .8rem;}
        .nav .group{font-size:.68rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;
            color:#aeb6c4;padding:.9rem .8rem .4rem;}
        .nav a{display:flex;align-items:center;gap:.75rem;padding:.65rem .8rem;border-radius:9px;
            color:#556;font-size:.9rem;font-weight:500;text-decoration:none;margin-bottom:.15rem;transition:.14s;}
        .nav a svg{width:18px;height:18px;flex-shrink:0;opacity:.85;}
        .nav a:hover{background:#f5f7fb;color:var(--navy);}
        .nav a.active{background:var(--active);color:var(--brand);font-weight:600;}
        .nav a.active svg{opacity:1;}

        /* ===== Main ===== */
        .main{display:flex;flex-direction:column;min-width:0;}
        .topbar{height:70px;background:#fff;border-bottom:1px solid var(--line);
            display:flex;align-items:center;justify-content:space-between;padding:0 1.8rem;
            position:sticky;top:0;z-index:20;}
        .topbar .hi b{font-size:1rem;color:var(--ink);}
        .topbar .hi span{display:block;font-size:.78rem;color:var(--muted);}
        .topbar .hi b{letter-spacing:.01em;font-weight:800;color:var(--navy);}
        .topbar .user{display:flex;align-items:center;gap:.9rem;}
        .topbar .clock{display:inline-flex;align-items:center;gap:.4rem;background:#f5f7fb;color:#48546b;
            padding:.42rem .8rem;border-radius:9px;font-size:.82rem;font-weight:700;font-variant-numeric:tabular-nums;}
        .topbar .clock svg{opacity:.7;}
        @media (max-width:640px){.topbar .clock{display:none;}}
        .topbar .bell{width:38px;height:38px;border-radius:50%;display:grid;place-items:center;
            color:#667;background:#f5f7fb;position:relative;}
        .topbar .bell::after{content:"";position:absolute;top:9px;right:11px;width:7px;height:7px;
            border-radius:50%;background:#ef4444;border:2px solid #fff;}
        .topbar .who{display:flex;align-items:center;gap:.6rem;padding-left:.9rem;border-left:1px solid var(--line);}
        .topbar .who .av{width:38px;height:38px;border-radius:50%;background:linear-gradient(135deg,var(--navy),var(--brand));
            color:#fff;display:grid;place-items:center;font-weight:700;font-size:.85rem;overflow:hidden;flex-shrink:0;}
        .topbar .who .av img{width:100%;height:100%;object-fit:cover;}
        .topbar .who .nm b{font-size:.86rem;color:var(--ink);display:block;line-height:1.1;}
        .topbar .who .nm span{font-size:.72rem;color:var(--muted);}
        .logout{background:none;border:none;color:var(--muted);cursor:pointer;padding:.4rem;display:flex;border-radius:6px;}
        .logout:hover{color:#ef4444;background:#fef2f2;}

        .content{flex:1;padding:1.8rem;}

        /* ===== Reusable UI ===== */
        .page-title{font-size:1.3rem;font-weight:800;color:var(--navy);margin-bottom:1.2rem;}
        .card{background:#fff;border:1px solid var(--line);border-radius:14px;}
        .btn-primary{display:inline-flex;align-items:center;gap:.4rem;background:var(--brand);color:#fff;border:none;
            padding:.6rem 1rem;border-radius:9px;font-size:.88rem;font-weight:600;cursor:pointer;text-decoration:none;transition:.15s;}
        .btn-primary:hover{background:#1d4ed8;}
        .input{width:100%;padding:.65rem .9rem .65rem 2.4rem;border:1px solid var(--line);border-radius:9px;
            font-size:.9rem;color:var(--ink);background:#fff;outline:none;transition:.15s;}
        .input:focus{border-color:var(--brand);box-shadow:0 0 0 3px #2563eb1f;}

        /* Tabel */
        table.data{width:100%;border-collapse:collapse;font-size:.88rem;}
        table.data th{text-align:left;padding:.85rem 1rem;color:var(--muted);font-weight:600;font-size:.78rem;
            text-transform:uppercase;letter-spacing:.02em;background:#fafbfd;border-bottom:1px solid var(--line);}
        table.data td{padding:.85rem 1rem;border-bottom:1px solid var(--line);color:#444;}
        table.data tbody tr:hover{background:#fafbfd;}

        @media (max-width:900px){
            .layout{grid-template-columns:1fr;}
            .sidebar{position:fixed;left:-260px;z-index:50;transition:.2s;width:248px;}
            .sidebar.open{left:0;}
        }
    </style>
    @stack('head')
</head>
<body>
    @php
        $name = session('name', 'Admin');
        $role = session('role', '');
        $isAdmin = $role === 'administrator';
        $isDoctor = $role === 'klinik';
        // Inisial dari nama tanpa gelar depan (dr., drg., prof., ns., dll) supaya
        // "dr. Christy Thong" → "CT", bukan "DC".
        $titles = ['dr','drg','prof','ns','apt','h','hj','ir','drs','dra','st'];
        $initSource = collect(explode(' ', trim($name)))
            ->reject(fn ($w) => in_array(mb_strtolower(rtrim($w, '.')), $titles, true))
            ->values();
        $initials = ($initSource->isNotEmpty() ? $initSource : collect(explode(' ', $name)))
            ->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode('');
        // Label role untuk subtitle kartu user.
        $roleLabel = [
            'administrator'      => 'Administrator',
            'spv'                => 'Supervisor',
            'klinik'             => 'Dokter',
            'farmasi'            => 'Farmasi',
            'kasir_administrasi' => 'Kasir Administrasi',
            'kasir_farmasi'      => 'Kasir Farmasi',
        ][$role] ?? 'Pengguna';
        $cur = request()->route()?->getName();

        // Foto profil dokter (bila login sebagai dokter & ada fotonya).
        $avatarUrl = null;
        if ($pid = session('paramedic_id')) {
            $photo = \App\Models\DoctorPhoto::find($pid);
            if ($photo && $photo->filename && is_file(public_path('doctor-photos/'.$photo->filename))) {
                $avatarUrl = $photo->url();
            }
        }

        if ($isDoctor) {
            // Dokter: menu minimal — cukup Antrian miliknya.
            $nav = [
                ['section' => 'ANTRIAN SAYA', 'items' => [
                    ['route' => 'queue.index', 'label' => 'Antrian', 'icon' => 'list'],
                ]],
            ];
        } else {
            $nav = [
                ['section' => 'UTAMA', 'items' => [
                    ['route' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'grid'],
                ]],
                ['section' => 'DATA MASTER', 'items' => [
                    ['route' => 'clinics.index',   'label' => 'Daftar Klinik',  'icon' => 'building'],
                    ['route' => 'doctors.index',   'label' => 'Daftar Dokter',  'icon' => 'users'],
                    ['route' => 'schedules.index', 'label' => 'Jadwal Dokter',  'icon' => 'calendar'],
                ]],
                ['section' => 'OPERASIONAL', 'items' => [
                    ['route' => 'queue.index',   'label' => 'Antrian',         'icon' => 'list'],
                    ['route' => 'reports.queue', 'label' => 'Laporan Antrian', 'icon' => 'chart'],
                ]],
                // Menu pengaturan hanya untuk administrator.
                ['section' => 'PENGATURAN', 'admin' => true, 'items' => [
                    ['route' => 'users.index',   'label' => 'Manajemen User',     'icon' => 'users'],
                    ['route' => 'media.index',   'label' => 'Media Layar Tunggu', 'icon' => 'image'],
                ]],
            ];
            // Buang grup admin-only bila bukan admin.
            $nav = array_values(array_filter($nav, fn ($g) => empty($g['admin']) || $isAdmin));
        }
        $icons = [
            'grid'     => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
            'building' => '<rect x="4" y="3" width="16" height="18" rx="1"/><path d="M9 8h.01M15 8h.01M9 12h.01M15 12h.01M9 16h6"/>',
            'calendar' => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
            'list'     => '<path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/>',
            'users'    => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
            'image'    => '<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/>',
            'video'    => '<rect x="2" y="5" width="15" height="14" rx="2"/><path d="M22 8l-5 4 5 4z"/>',
            'chart'    => '<path d="M3 3v18h18"/><rect x="7" y="12" width="3" height="6"/><rect x="12" y="8" width="3" height="10"/><rect x="17" y="5" width="3" height="13"/>',
        ];
    @endphp

    <div class="layout">
        {{-- Sidebar --}}
        <aside class="sidebar" id="sidebar">
            <div class="brand"><img src="{{ asset('cihoslogo.png') }}" alt="Ciputra Hospital"></div>
            <nav class="nav">
                @foreach ($nav as $grp)
                    <div class="group">{{ $grp['section'] }}</div>
                    @foreach ($grp['items'] as $it)
                        @php $active = $cur === $it['route']; @endphp
                        <a href="{{ \Illuminate\Support\Facades\Route::has($it['route']) ? route($it['route']) : '#' }}"
                           class="{{ $active ? 'active' : '' }}">
                            <svg fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">{!! $icons[$it['icon']] ?? '' !!}</svg>
                            <span>{{ $it['label'] }}</span>
                        </a>
                    @endforeach
                @endforeach
            </nav>
        </aside>

        {{-- Main --}}
        <div class="main">
            <header class="topbar">
                <div class="hi">
                    <b>@yield('pagehead', 'CIPUTRA HOSPITAL — Sistem Antrian')</b>
                    <span>@yield('pagesub', $roleLabel)</span>
                </div>
                <div class="user">
                    <div class="clock" id="topClock" title="Waktu server">
                        <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                        <span id="clockText">—</span>
                    </div>
                    <div class="bell">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                    </div>
                    <div class="who">
                        <div class="av">
                            @if ($avatarUrl)
                                <img src="{{ $avatarUrl }}" alt="{{ $name }}">
                            @else
                                {{ strtoupper($initials) }}
                            @endif
                        </div>
                        <div class="nm"><b>{{ $name }}</b><span>{{ $roleLabel }}</span></div>
                        <form method="post" action="{{ route('logout') }}">
                            @csrf
                            <button class="logout" type="submit" title="Logout">
                                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></svg>
                            </button>
                        </form>
                    </div>
                </div>
            </header>

            <main class="content">
                @yield('content')
            </main>
        </div>
    </div>
    <script>
        // Jam live di topbar (waktu lokal browser).
        (function(){
            var el = document.getElementById('clockText');
            if(!el) return;
            var days = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
            function tick(){
                var d = new Date();
                var p = n => String(n).padStart(2,'0');
                el.textContent = days[d.getDay()]+', '+p(d.getHours())+':'+p(d.getMinutes())+':'+p(d.getSeconds());
            }
            tick(); setInterval(tick, 1000);
        })();
    </script>

    {{-- ── SUARA PANGGILAN ──────────────────────────────────────────────
         Begitu tombol Panggil/Recall ditekan (klinik / kasir / farmasi),
         halaman dimuat ulang dan controller menitipkan data panggilan di
         flash session 'say'. Skrip ini memutar audionya di browser petugas —
         TIDAK bergantung pada layar/display mana pun yang sedang terbuka.

         ANTREAN: panggilan disimpan di localStorage, lalu diputar SATU PER
         SATU. Bila ada panggilan lain (bahkan dari tab/PC yang sama) yang
         sedang berbunyi, panggilan berikutnya MENUNGGU sampai selesai.
    ------------------------------------------------------------------- --}}
    <script>
    (function(){
        var SPEECH = @json(route('display.speech'));
        /*
         * Audio dirender di SERVER (Edge TTS, voice Gadis/wanita).
         *
         * Catatan: Web Speech API bawaan browser sempat dipertimbangkan —
         * lebih instan & offline — tapi Windows hanya menyediakan SATU voice
         * Indonesia yaitu Andika (PRIA). Tidak ada voice wanita id-ID, jadi
         * jalur server tetap dipakai agar suaranya perempuan & seragam di
         * semua perangkat.
         */
        var QKEY = 'antrian_say_queue';    // daftar tunggu pengumuman
        var LKEY = 'antrian_say_lock';     // penanda sedang berbunyi
        var LOCK_MS = 30000;               // lock kedaluwarsa (tab ditutup dll)

        function readQ(){
            try{ return JSON.parse(localStorage.getItem(QKEY) || '[]'); }catch(e){ return []; }
        }
        function writeQ(q){
            try{ localStorage.setItem(QKEY, JSON.stringify(q)); }catch(e){}
        }
        function locked(){
            var t = parseInt(localStorage.getItem(LKEY) || '0', 10);
            return t && (Date.now() - t) < LOCK_MS;
        }
        function lock(){   try{ localStorage.setItem(LKEY, String(Date.now())); }catch(e){} }
        function unlock(){ try{ localStorage.removeItem(LKEY); }catch(e){} }

        var player = new Audio();
        player.preload = 'auto';

        /*
         * BEL PEMBUKA & PENUTUP — dari public/bel.mp3 (8,38 detik).
         * Berkas berisi dua blok bunyi yang dipisah hening 3,89–5,52 dtk:
         *   blok 1 (0    – 4,0 dtk)  → bel PEMBUKA, sebelum pengumuman
         *   blok 2 (5,45 dtk – habis)→ bel PENUTUP, sesudah pengumuman
         * Keduanya dipotong dari SATU berkas dengan mengatur currentTime lalu
         * berhenti saat batasnya tercapai.
         *
         * CATATAN: bila bel.mp3 diganti, cek ulang batas heningnya —
         * kalau meleset, bel bisa terpotong atau ikut membawa bunyi blok lain.
         */
        var BEL_URL   = @json(asset('bel.mp3'));
        var BEL_OPEN  = { start: 0,    end: 4.0  };
        var BEL_CLOSE = { start: 5.45, end: null };   // null = sampai habis
        var bell = new Audio(BEL_URL);
        bell.preload = 'auto';

        /*
         * Putar satu segmen bel.
         *
         * Batas segmen dijaga pakai setTimeout berdasarkan DURASI (bukan
         * polling currentTime tiap puluhan milidetik). Polling + mengubah
         * `volume` berulang membuat bunyi patah-patah seperti radio rusak,
         * jadi tidak dipakai lagi.
         */
        function playBell(seg, done){
            var timer = null, finished = false;
            var stop = function(){
                if(finished) return;
                finished = true;
                if(timer){ clearTimeout(timer); timer = null; }
                bell.removeEventListener('ended', stop);
                try{ bell.pause(); }catch(e){}
                done();
            };
            bell.addEventListener('ended', stop);
            try{
                bell.currentTime = seg.start;
                if(seg.end != null){
                    timer = setTimeout(stop, (seg.end - seg.start) * 1000);
                }
                bell.play().catch(function(){ stop(); });
            }catch(e){ stop(); }
        }

        /*
         * Halaman antrian auto-reload tiap 15 detik, sedangkan pengumuman
         * berdurasi ~20 detik (diputar 2x) — reload di tengah membuat suara
         * TERPOTONG. Karena itu selama masih berbunyi, reload ditahan dulu:
         * window.__sayBusy dibaca oleh skrip auto-reload di tiap halaman.
         */
        window.__sayBusy = false;

        // Putar 2x, lalu lepas lock & lanjut ke antrean berikutnya.
        function playTwice(src, done){
            var round = 0;
            var onEnd = function(){
                round++;
                if(round < 2){
                    setTimeout(function(){
                        try{ player.currentTime = 0; player.play().catch(fail); }catch(e){ fail(); }
                    }, 1200);
                } else {
                    player.removeEventListener('ended', onEnd);
                    done();
                }
            };
            var fail = function(){ player.removeEventListener('ended', onEnd); done(); };

            player.addEventListener('ended', onEnd);
            player.src = src;
            player.currentTime = 0;
            player.play().catch(function(err){
                // Autoplay diblokir → bunyikan pada klik pertama di halaman.
                if(err && err.name === 'NotAllowedError'){
                    document.addEventListener('click', function once(){
                        player.play().catch(function(){});
                    }, {once:true});
                }
                fail();
            });
        }

        // Ambil satu dari antrean bila tidak ada yang sedang berbunyi.
        function pump(){
            if(locked()) return;                 // masih ada yang bicara → tunggu
            var q = readQ();
            if(!q.length) return;

            var item = q.shift();
            writeQ(q);
            lock();
            window.__sayBusy = true;    // tahan auto-reload halaman

            var url = SPEECH
                + '?no='   + encodeURIComponent(item.no || '')
                + '&dest=' + encodeURIComponent(item.dest || '')
                + '&area=' + encodeURIComponent(item.area || 'klinik');

            var finish = function(){
                unlock();
                window.__sayBusy = false;   // boleh reload lagi
                pump();
            };

            // Urutan: BEL PEMBUKA → pengumuman (2x) → BEL PENUTUP.
            fetch(url, {headers:{'X-Requested-With':'XMLHttpRequest'}})
                .then(function(r){ return r.json(); })
                .then(function(j){
                    if(!(j && j.url)){ finish(); return; }
                    playBell(BEL_OPEN, function(){
                        // Jeda pendek setelah bel pembuka agar tidak menempel.
                        setTimeout(function(){
                            playTwice(j.url, function(){
                                // Beri jeda sebelum bel penutup — tanpa ini
                                // belnya menempel persis di ujung kalimat &
                                // terdengar kasar.
                                setTimeout(function(){
                                    playBell(BEL_CLOSE, finish);
                                }, 900);
                            });
                        }, 350);
                    });
                })
                .catch(finish);
        }

        @if (session('say'))
            // Panggilan baru dari tombol yang baru ditekan → masuk antrean.
            (function(){
                var say = @json(session('say'));
                if(say && say.no){ var q = readQ(); q.push(say); writeQ(q); }
            })();
        @endif

        pump();
        setInterval(pump, 1000);   // cek antrean; juga lanjut bila lock kadaluarsa
    })();
    </script>

    @stack('scripts')
</body>
</html>
