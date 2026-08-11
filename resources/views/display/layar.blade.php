<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $judul }} — Ciputra Hospital</title>
    <link rel="icon" href="{{ asset('cihoslogo_biruijo.png') }}" type="image/png">
    <style>
        :root{
            /* Palet: BIRU sebagai primary (mengikuti background #002050–#004090)
               + EMAS sebagai aksen penghias. */
            --navy:#0b2f6b;--navy3:#001b45;
            --brand:#c9992e;--brand-lt:#e8be5a;--brand-pale:rgba(201,153,46,.14);
            --gold:#c9992e;--gold-lt:#e8be5a;--gold-pale:rgba(201,153,46,.14);
            --ink:#14243f;--muted:#5c6f92;--line:rgba(11,47,107,.12);
            /* Glassmorphism: latar semi-transparan + blur + tepi terang. */
            --glass:rgba(255,255,255,.62);
            --glass-strong:rgba(255,255,255,.80);
            --glass-brd:rgba(255,255,255,.68);
            --blur:blur(18px) saturate(150%);
            --shadow:0 8px 32px rgba(0,27,69,.16);
            --r:18px;
        }
        *{box-sizing:border-box;margin:0;padding:0;}
        html,body{height:100%;}
        body{font-family:'Segoe UI',system-ui,sans-serif;color:var(--ink);
            overflow:hidden;height:100vh;display:flex;flex-direction:column;
            background:#d1d5db url('{{ asset('login_bg.png') }}') center/cover fixed no-repeat;}
        /* Overlay tipis saja — glass butuh latar yang masih terlihat di belakangnya. */
        body::before{content:"";position:fixed;inset:0;background:rgba(238,243,251,.26);z-index:-1;}

        /* ---------- HEADER: bar glass melayang (rounded, tidak full-bleed) ---------- */
        .hdwrap{flex-shrink:0;padding:1.2vh 1.4vw .2vh;}
        .hd{background:linear-gradient(120deg,rgba(255,255,255,.62),rgba(255,255,255,.42));
            -webkit-backdrop-filter:var(--blur);backdrop-filter:var(--blur);
            border:1px solid var(--glass-brd);border-radius:var(--r);
            box-shadow:0 8px 28px rgba(15,30,46,.14),inset 0 1px 0 rgba(255,255,255,.55);
            color:var(--navy);display:flex;align-items:center;justify-content:space-between;
            padding:0 2rem;height:10.5vh;min-height:74px;}
        /* Logo dipecah jadi 2 crop dari file ASLI (tipografi brand dipertahankan,
           tidak diganti teks HTML). Kanvas 3000x2386:
             IKON     : x 36%..61.33%,   y 19.11%..39.85%  (rasio 1.222)
             WORDMARK : x 11.6%..87.5%,  y 42.37%..69.03%  (rasio 3.580)
           Tiap kotak meng-crop area itu dengan memperbesar gambar lalu menggeser. */
        .hd .b{display:flex;align-items:center;gap:1.2rem;min-width:0;}
        .hd .b .crop{position:relative;overflow:hidden;flex-shrink:0;}
        .hd .b .crop img{position:absolute;height:auto;max-width:none;}
        /* aspect-ratio WAJIB sama dengan rasio area crop, karena offset `top`
           dihitung relatif tinggi wadah — rasio meleset = artwork lain ikut masuk.
           Kedua crop dipas ke batas tinta (bbox) masing-masing, dan tingginya
           disamakan agar ikon C tidak tampak lebih tinggi dari wordmark. */
        .hd .b .mark{height:7vh;min-height:50px;aspect-ratio:1.5510;}
        .hd .b .mark img{width:394.74%;left:-142.11%;top:-92.04%;}
        .hd .b .wmk{height:7vh;min-height:50px;aspect-ratio:3.5594;}
        .hd .b .wmk img{width:131.69%;left:-15.28%;top:-157.66%;}
        .hd .r{text-align:right;}
        .hd .r .cl{font-size:3vh;font-weight:800;font-variant-numeric:tabular-nums;line-height:1.1;}
        .hd .r .dt{font-size:1.5vh;font-weight:600;color:var(--muted);}

        /* ---------- LAYOUT ---------- */
        /* justify-content:center → sisa ruang dibagi rata atas & bawah, sehingga
           jarak ke header dan ke marquee seimbang. `gap` = jarak baris promosi
           /Now Serving ke panel Next in Queue. */
        .main{flex:1;display:flex;flex-direction:column;justify-content:center;
            gap:4vh;padding:2.6vh 1.4vw 2.6vh;min-height:0;}
        /* Baris atas: media 16:9 (WAJIB) + Now Serving, tinggi identik.
           Daftar antrean pindah ke bawah (full width). */
        .top{flex:0 0 auto;display:grid;grid-template-columns:1fr 1fr;gap:1.4vw;min-height:0;align-items:start;}
        .top>*{height:min(52vh, calc((100vw - 2.8vw - 1.4vw) / 2 * 9 / 16));}

        /* ---------- MEDIA 16:9 (dipertahankan) ---------- */
        .media{border-radius:var(--r);overflow:hidden;position:relative;
            border:1px solid #c9992e;
            box-shadow:var(--shadow);display:flex;align-items:center;justify-content:center;min-height:0;}
        .media video,.media.banner img{position:absolute;inset:0;width:100%;height:100%;}
        .media.video{background:#04173a;}
        .media video{object-fit:cover;}
        /* Rasio tiap banner tidak persis 16:9 (1.771 vs 1.791) — `cover` memangkas
           <0.6% sehingga nol margin putih untuk rasio apa pun. */
        .media.banner{background:#fff;}
        .media.banner img{object-fit:cover;opacity:0;transition:opacity 1s;}
        .media.banner img.on{opacity:1;}
        .media .none{color:#5c6f92;font-size:1.8vh;font-weight:600;}
        .dots{position:absolute;left:0;right:0;bottom:1.1vh;display:flex;justify-content:center;gap:.5rem;z-index:2;}
        .dots i{width:.85vh;height:.85vh;border-radius:50%;background:rgba(255,255,255,.55);
            box-shadow:0 1px 4px rgba(0,0,0,.4);transition:background .3s,transform .3s;}
        .dots i.on{background:#fff;transform:scale(1.25);}

        /* ---------- NOW SERVING (kanan promosi, tinggi = media 16:9) ---------- */
        .now{border-radius:var(--r);box-shadow:var(--shadow);overflow:hidden;min-height:0;
            background:linear-gradient(150deg,rgba(0,27,69,.93),rgba(11,47,107,.89) 55%,rgba(21,73,143,.85));
            -webkit-backdrop-filter:var(--blur);backdrop-filter:var(--blur);
            border:1px solid rgba(255,255,255,.18);color:#fff;
            display:flex;flex-direction:column;align-items:center;justify-content:center;
            text-align:center;padding:2vh 1.2vw;gap:1vh;}
        .now .tag{display:flex;align-items:center;gap:.6rem;font-size:1.7vh;font-weight:700;
            letter-spacing:.18em;text-transform:uppercase;color:#e8be5a;}
        .now .tag .pulse{width:1vh;height:1vh;border-radius:50%;background:var(--brand-lt);
            box-shadow:0 0 0 0 rgba(232,190,90,.75);animation:pulse 2s infinite;}
        @keyframes pulse{70%{box-shadow:0 0 0 1.4vh rgba(232,190,90,0);}100%{box-shadow:0 0 0 0 rgba(232,190,90,0);}}
        .now .num{font-size:15vh;font-weight:800;line-height:1;font-variant-numeric:tabular-nums;
            letter-spacing:.02em;text-shadow:0 4px 24px rgba(0,0,0,.35);}
        .now .idle-txt{font-size:3.6vh;font-weight:300;letter-spacing:.08em;text-transform:uppercase;opacity:.7;}
        .now .dest{display:inline-flex;align-items:baseline;gap:.7rem;background:rgba(255,255,255,.15);
            border:1px solid rgba(255,255,255,.22);border-radius:999px;padding:.9vh 1.8rem;}
        .now .dest span{font-size:1.7vh;font-weight:700;letter-spacing:.12em;text-transform:uppercase;opacity:.85;}
        .now .dest b{font-size:4vh;font-weight:800;font-variant-numeric:tabular-nums;line-height:1;}
        .now.blink{animation:flash 1s ease-in-out 3;}
        @keyframes flash{0%,100%{filter:none;}50%{filter:brightness(1.8) saturate(1.3);}}

        /* ---------- NEXT IN QUEUE (full width, di bawah promosi) ---------- */
        /* flex:0 0 auto → panel setinggi isinya saja, tidak meregang penuh. */
        .qlist{flex:0 0 auto;background:var(--glass);-webkit-backdrop-filter:var(--blur);backdrop-filter:var(--blur);
            border:1px solid var(--glass-brd);border-radius:var(--r);box-shadow:var(--shadow);
            display:flex;flex-direction:column;overflow:hidden;min-height:0;}
        .qlist .hd2{flex-shrink:0;display:flex;align-items:center;gap:.9rem;
            padding:1.1vh 1.2vw .9vh;border-bottom:1px solid var(--line);}
        .qlist .hd2 .t{font-size:1.8vh;font-weight:800;letter-spacing:.14em;text-transform:uppercase;color:var(--navy);}
        .qlist .hd2 .line{flex:1;height:1px;background:linear-gradient(90deg,var(--line),transparent);}
        /* Chip total: kartu hanya preview, angka ini mewakili keseluruhan antrean. */
        .qlist .hd2 .cnt{font-size:1.6vh;font-weight:800;color:var(--navy);background:var(--brand-pale);
            border:1px solid rgba(201,153,46,.20);border-radius:999px;padding:.35vh .9rem;white-space:nowrap;}
        .qlist .hd2 .cnt b{color:var(--brand);font-variant-numeric:tabular-nums;}
        /* Kartu antrean berjajar — memanfaatkan lebar penuh layar. */
        /* Tinggi kartu tetap & ringkas — cukup untuk nomor + tujuan. */
        .qlist .rows{display:grid;grid-template-columns:repeat(6,1fr);gap:1vw;
            padding:1.1vh 1.2vw 1.3vh;}
        .qc{background:var(--glass-strong);border:1px solid var(--glass-brd);border-radius:14px;
            height:14vh;min-height:92px;
            display:flex;flex-direction:column;align-items:center;justify-content:center;gap:.4vh;
            position:relative;overflow:hidden;transition:opacity .3s;
            box-shadow:0 3px 12px rgba(15,30,46,.08);}
        .qc .pos{position:absolute;top:.6vh;left:.6vw;font-size:1.45vh;font-weight:800;color:#6b7d9e;
            font-variant-numeric:tabular-nums;}
        .qc .no{font-size:4.4vh;font-weight:800;color:var(--navy);line-height:1;
            font-variant-numeric:tabular-nums;letter-spacing:.01em;}
        .qc .to{display:flex;align-items:baseline;gap:.4rem;}
        .qc .to span{font-size:1.4vh;font-weight:800;letter-spacing:.1em;text-transform:uppercase;color:#4a5f85;}
        .qc .to b{font-size:2.1vh;font-weight:800;color:var(--ink);font-variant-numeric:tabular-nums;}
        /* Kartu pertama = giliran berikutnya. */
        .qc.lead{background:rgba(253,244,222,.88);border-color:rgba(201,153,46,.45);
            box-shadow:0 4px 16px rgba(201,153,46,.20);}
        .qc.lead .no{color:var(--brand);}
        .qc.empty{opacity:.45;}
        .qc.empty .no,.qc.empty .to b{color:#a9b6cf;}
        /* BOOKING: pasien sudah punya jadwal tapi BELUM check-in. Ditampilkan
           samar & bergaris putus-putus supaya urutan antrian terlihat utuh
           sejak awal — jadi saat ia check-in, ia tidak terkesan menyelip. */
        .qc.booking{opacity:.5;border-style:dashed;background:rgba(255,255,255,.45);}
        .qc.booking .no{color:#6b7d9e;font-weight:700;}
        .qc.booking .to b{color:#7c8aa6;}
        .qc .bk{font-size:1.15vh;font-weight:800;letter-spacing:.1em;text-transform:uppercase;
            color:#8d9ab4;margin-top:.2vh;}

        /* ---------- MARQUEE (glass gelap) ---------- */
        .mq{background:rgba(6,34,80,.86);-webkit-backdrop-filter:var(--blur);backdrop-filter:var(--blur);
            border-top:1px solid rgba(255,255,255,.14);color:#fff;height:3.2vh;min-height:24px;
            display:flex;align-items:center;overflow:hidden;flex-shrink:0;}
        .mq .t{white-space:nowrap;font-size:1.45vh;font-weight:800;letter-spacing:.06em;text-transform:uppercase;
            animation:scroll 26s linear infinite;padding-left:100%;}
        @keyframes scroll{from{transform:translateX(0);}to{transform:translateX(-100%);}}

        /* Penanda audio diblokir browser (autoplay policy). Muncul hanya bila
           pemutaran ditolak — petugas cukup klik layar sekali. */
        .alock{position:fixed;inset:0;z-index:99;display:none;
            align-items:center;justify-content:center;cursor:pointer;
            background:rgba(0,27,69,.55);-webkit-backdrop-filter:blur(6px);backdrop-filter:blur(6px);}
        .alock.on{display:flex;}
        .alock .box{background:#fff;border-radius:var(--r);padding:3.4vh 3vw;text-align:center;
            box-shadow:0 20px 60px rgba(0,27,69,.4);max-width:60vw;}
        .alock .ic{font-size:6vh;line-height:1;}
        .alock .t1{font-size:3vh;font-weight:800;color:var(--navy);margin-top:1.4vh;}
        .alock .t2{font-size:2vh;font-weight:600;color:var(--muted);margin-top:1vh;line-height:1.5;}

        @media (prefers-reduced-motion:reduce){
            .now .tag .pulse,.now.blink,.mq .t{animation:none;}
        }
    </style>
</head>
<body>
    <div class="hdwrap">
        <header class="hd">
            <div class="b">
                <div class="crop mark"><img src="{{ asset('cihoslogo_biruijo.png') }}" alt=""></div>
                <div class="crop wmk"><img src="{{ asset('cihoslogo_biruijo.png') }}" alt="Ciputra Hospital Surabaya — Enhancing Life"></div>
            </div>
            <div class="r">
                <div class="cl" id="cl">--:--:--</div>
                <div class="dt" id="dt">—</div>
            </div>
        </header>
    </div>

    <main class="main">
        <div class="top">
            {{-- Now Serving — KIRI --}}
            <div class="now" id="nowCard">
                <div class="tag"><i class="pulse"></i><span>Now Serving</span></div>
                <div class="idle-txt" id="idle">Waiting for call…</div>
                <div class="num" id="num" style="display:none;">0000</div>
                <div class="dest" id="dest" style="display:none;">
                    <span>{{ $area === 'klinik' ? 'Room' : 'Counter' }}</span><b id="room">—</b>
                </div>
            </div>

            {{-- Media promosi — KANAN, container WAJIB 16:9 --}}
            @if ($video)
                <div class="media video"><video src="{{ $video->url() }}" autoplay muted loop playsinline></video></div>
            @elseif ($banners->count())
                <div class="media banner" id="bannerBox">
                    @foreach ($banners as $i => $b)
                        <img src="{{ $b->url() }}" class="{{ $i===0?'on':'' }}" alt="{{ $b->nama }}">
                    @endforeach
                    @if ($banners->count() > 1)
                        <div class="dots" id="dots">
                            @foreach ($banners as $i => $b)<i class="{{ $i===0?'on':'' }}"></i>@endforeach
                        </div>
                    @endif
                </div>
            @else
                <div class="media"><div class="none">No active media</div></div>
            @endif
        </div>

        {{-- Next in Queue — full width di bawah promosi --}}
        <div class="qlist">
            <div class="hd2">
                <div class="t">Next in Queue</div>
                <div class="line"></div>
                <div class="cnt" id="cntChip" style="display:none;">Waiting <b id="cntNum">0</b></div>
            </div>
            <div class="rows" id="qrows">
                @for ($i=0;$i<6;$i++)
                    <div class="qc empty">
                        <div class="pos">{{ $i+1 }}</div>
                        <div class="no">—</div>
                        <div class="to"><span>{{ $area === 'klinik' ? 'Room' : 'Counter' }}</span><b>—</b></div>
                    </div>
                @endfor
            </div>
        </div>
    </main>

    @php $welcomeEn = 'WELCOME TO CIPUTRA HOSPITAL SURABAYA'; @endphp
    <footer class="mq"><div class="t">{{ $welcomeEn }} &nbsp;•&nbsp; {{ $welcomeEn }} &nbsp;•&nbsp; {{ $welcomeEn }}</div></footer>

    {{-- Muncul hanya bila browser memblokir audio (butuh 1x klik) --}}
    <div class="alock" id="audioLock" onclick="unlockAudio()">
        <div class="box">
            <div class="ic">🔇</div>
            <div class="t1">Klik layar untuk mengaktifkan suara</div>
            <div class="t2">Browser memblokir suara otomatis.<br>Cukup klik sekali — pesan ini akan hilang.</div>
        </div>
    </div>

    <script>
        var JSON_URL = "{{ $jsonUrl }}";
        var AREA = "{{ $area }}"; // klinik | kasir | farmasi
        var DEST_WORD = AREA === 'klinik' ? 'room' : 'counter';       // label di layar (Inggris)
        var DEST_WORD_ID = AREA === 'klinik' ? 'ruangan' : 'counter'; // untuk suara (samakan dgn SpeechController)
        var DAYS=['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
        var MONTHS=['January','February','March','April','May','June','July','August','September','October','November','December'];
        function tick(){var d=new Date(),p=n=>String(n).padStart(2,'0');
            document.getElementById('cl').textContent=p(d.getHours())+':'+p(d.getMinutes())+':'+p(d.getSeconds());
            document.getElementById('dt').textContent=DAYS[d.getDay()]+', '+p(d.getDate())+' '+MONTHS[d.getMonth()]+' '+d.getFullYear();}
        setInterval(tick,1000);tick();

        // Slideshow banner + sinkronisasi titik indikator.
        (function(){var imgs=document.querySelectorAll('#bannerBox img');if(imgs.length<2)return;
            var dots=document.querySelectorAll('#dots i'),i=0;
            setInterval(function(){
                imgs[i].classList.remove('on');if(dots[i])dots[i].classList.remove('on');
                i=(i+1)%imgs.length;
                imgs[i].classList.add('on');if(dots[i])dots[i].classList.add('on');
            },6000);})();

        // Chime "ding-dong" ala bandara sebelum pengumuman suara.
        function ding(){try{var ctx=ding._c||(ding._c=new(window.AudioContext||window.webkitAudioContext)());
            if(ctx.state==='suspended'){ctx.resume();}
            [880,1174].forEach(function(f,i){var o=ctx.createOscillator(),g=ctx.createGain();o.type='sine';o.frequency.value=f;
            o.connect(g);g.connect(ctx.destination);var t=ctx.currentTime+i*0.18;g.gain.setValueAtTime(0.0001,t);
            g.gain.exponentialRampToValueAtTime(0.25,t+0.02);g.gain.exponentialRampToValueAtTime(0.0001,t+0.35);o.start(t);o.stop(t+0.36);});}catch(e){}}

        // "FD005" → "F D 0 0 5" agar TTS mengeja jelas.
        // "FD005" → "ef de nol nol lima" — angka & huruf dieja memakai lafal
        // Indonesia supaya tidak terdengar seperti bahasa Inggris.
        var DIGIT_ID = {'0':'nol','1':'satu','2':'dua','3':'tiga','4':'empat',
                        '5':'lima','6':'enam','7':'tujuh','8':'delapan','9':'sembilan'};
        var LETTER_ID = {A:'a',B:'be',C:'ce',D:'de',E:'e',F:'ef',G:'ge',H:'ha',I:'i',
                         J:'je',K:'ka',L:'el',M:'em',N:'en',O:'o',P:'pe',Q:'ki',R:'er',
                         S:'es',T:'te',U:'u',V:'fe',W:'we',X:'eks',Y:'ye',Z:'zet'};
        function spellNumber(no){
            return String(no||'').toUpperCase().split('').map(function(ch){
                if(DIGIT_ID[ch]) return DIGIT_ID[ch];
                if(LETTER_ID[ch]) return LETTER_ID[ch];
                return ch;
            }).join(' ');
        }
        // Tujuan (ruang/loket): angka murni dibiarkan utuh agar dibaca sebagai
        // bilangan oleh TTS ("1102" → "seribu seratus dua"); bila mengandung
        // huruf (mis. counter "A2") dieja per karakter.
        function destText(d){
            var s = String(d==null?'':d).trim();
            if(!s || s==='—') return '';
            return /^\d+$/.test(s) ? s : spellNumber(s);
        }
        /*
         * Pilih voice PEREMPUAN berbahasa Indonesia.
         *
         * PENTING: suara Indonesia hanya terdengar alami bila perangkat layar
         * punya voice id-ID terpasang. Windows biasanya HANYA punya voice
         * Inggris (David/Zira/Mark) — tanpa voice Indonesia, teks tetap dibaca
         * tapi dengan aksen Inggris (terdengar aneh).
         * Pasang lewat: Settings → Time & Language → Language & region →
         * tambah "Indonesia" → Language options → Speech (Basic typing / TTS).
         * Cek voice yang tersedia dari console: speechSynthesis.getVoices()
         */
        function pickIdVoice(){
            var vs = speechSynthesis.getVoices() || [];
            var id = vs.filter(function(v){ return /^id([-_]|$)/i.test(v.lang); });
            if(!id.length) return null;
            // Nama voice Indonesia yang umum di Windows/Chrome/Android.
            var female = /(gadis|damayanti|google bahasa indonesia|female|wanita|perempuan|sri|indah)/i;
            var f = id.find(function(v){ return female.test(v.name); });
            if(f) return f;
            var notMale = id.find(function(v){ return !/(male|pria|ardi|rizwan|andika)/i.test(v.name); });
            return notMale || id[0];
        }
        // Peringatan sekali di console bila voice Indonesia belum terpasang.
        function warnNoIdVoice(){
            if(warnNoIdVoice._done) return;
            var vs = speechSynthesis.getVoices() || [];
            if(!vs.length) return; // daftar voice belum siap
            warnNoIdVoice._done = true;
            if(!vs.some(function(v){ return /^id([-_]|$)/i.test(v.lang); })){
                console.warn('[antrian] Voice bahasa Indonesia (id-ID) tidak ditemukan di perangkat ini. '+
                    'Pengumuman akan dibaca voice non-Indonesia sehingga terdengar beraksen asing. '+
                    'Pasang paket suara Indonesia di Windows: Settings > Time & Language > '+
                    'Language & region > Indonesia > Language options > Speech.');
            }
        }
        function makeUtter(text){
            var u=new SpeechSynthesisUtterance(text);
            u.lang='id-ID'; u.rate=0.9; u.pitch=1.1; u.volume=1;
            var v=pickIdVoice(); if(v) u.voice=v;
            return u;
        }
        /*
         * Pengumuman suara.
         *
         * UTAMA: audio dirender di SERVER (Piper TTS, Bahasa Indonesia) lalu
         * diputar sebagai <audio>. Dengan begitu semua perangkat — Windows,
         * Android, TV box — berbunyi sama persis tanpa perlu memasang voice.
         *
         * CADANGAN: bila server belum punya Piper, pakai Web Speech API
         * browser (kualitas bergantung voice yang terpasang di perangkat).
         */
        var SPEECH_URL  = "{{ route('display.speech') }}";
        var ENQUEUE_URL = "{{ route('display.speech.enqueue') }}";
        /*
         * MODE SUARA:
         *   'central' → layar TIDAK bersuara; panggilan didaftarkan ke antrean
         *               server, lalu diputar oleh SATU speaker pusat
         *               (buka /display/speaker di PC yang tersambung sound
         *               system). Dua panggilan bersamaan otomatis diantre.
         *   'local'   → layar ini ikut bersuara sendiri (bisa bertumpuk bila
         *               ada beberapa layar).
         */
        var SOUND_MODE = "{{ request('sound', config('tts.sound_mode', 'local')) }}";
        var player = new Audio();
        player.preload = 'auto';

        /*
         * AUTOPLAY: browser memblokir audio sampai ada interaksi user di
         * halaman. Di layar kiosk tidak ada yang mengklik, sehingga suara
         * tidak akan pernah keluar. Karena itu tampilkan penanda agar petugas
         * tahu harus mengklik layar SEKALI setelah membuka halaman.
         */
        function showAudioBlocked(){
            var el = document.getElementById('audioLock');
            if(el) el.classList.add('on');
        }
        function hideAudioBlocked(){
            var el = document.getElementById('audioLock');
            if(el) el.classList.remove('on');
        }
        function announce(no, dest, seq){
            var qs = 'no='+encodeURIComponent(no||'')+
                     '&dest='+encodeURIComponent(dest||'')+
                     '&area='+encodeURIComponent(AREA);

            // MODE PUSAT: cukup daftarkan ke antrean; speaker pusat yang
            // memutar. Layar ini tidak mengeluarkan suara sama sekali.
            if(SOUND_MODE === 'central'){
                fetch(ENQUEUE_URL+'?'+qs+'&seq='+encodeURIComponent(seq||0),
                      {headers:{'X-Requested-With':'XMLHttpRequest'}}).catch(function(){});
                return;
            }

            // MODE LOKAL: layar ini yang berbunyi.
            fetch(SPEECH_URL+'?'+qs, {headers:{'X-Requested-With':'XMLHttpRequest'}})
                .then(function(r){ return r.json(); })
                .then(function(j){
                    if(j && j.url){ playTwice(j.url, j.url_short || j.url); }
                    else { announceBrowser(no, dest); }
                })
                .catch(function(){ announceBrowser(no, dest); });
        }
        // Pengumuman diputar 2x ala bandara: versi LENGKAP dulu, lalu versi
        // RINGKAS sebagai pengulangan, dipisah jeda ~1,2 detik.
        function playTwice(url, urlShort){
            try{
                player.pause();
                player.src = url;
                player.currentTime = 0;
                var second = function(){
                    player.removeEventListener('ended', second);
                    setTimeout(function(){
                        try{ player.src = urlShort; player.currentTime=0; player.play().catch(function(){}); }catch(e){}
                    }, 1200);
                };
                player.addEventListener('ended', second);
                player.play().then(function(){
                    audioUnlocked = true; hideAudioBlocked();
                }).catch(function(err){
                    // Diblokir autoplay → beri tahu petugas & coba suara browser.
                    if(err && err.name === 'NotAllowedError'){
                        showAudioBlocked();
                        console.warn('[antrian] Audio diblokir browser. Klik layar sekali untuk mengaktifkan suara.');
                    }
                });
            }catch(e){}
        }
        /*
         * SUARA BROWSER (SpeechSynthesisUtterance) — meniru program lama
         * registrasi.php:
         *   - kalimat dipecah jadi potongan: frasa, HURUF satu per satu,
         *     dan ANGKA berurutan DIGABUNG ("nol nol lima").
         *   - tiap potongan diucapkan BERURUTAN (menunggu onend), bukan
         *     ditumpuk, sehingga tidak saling memotong.
         *   - cancel() hanya SEKALI di awal panggilan, tidak di tiap potongan.
         */
        function sleep(ms){ return new Promise(function(r){ setTimeout(r, ms); }); }

        // Ucapkan satu potongan, selesai saat onend/onerror.
        function speakPart(text, delayMs){
            return new Promise(function(resolve){
                var run = function(){
                    var u = makeUtter(String(text));
                    u.onend = function(){ resolve(true); };
                    u.onerror = function(){ resolve(false); };
                    try{
                        if(speechSynthesis.paused){ speechSynthesis.resume(); }
                        speechSynthesis.speak(u);
                    }catch(e){ resolve(false); }
                };
                delayMs > 0 ? setTimeout(run, delayMs) : run();
            });
        }

        // Bilangan 0–99 dalam kata (untuk menyebut nomor ruang).
        // "1838" → "delapan belas, tiga puluh delapan" (dipecah per 2 digit).
        // Pasangan berpuluhan nol tetap menyebut "nol": "05" → "nol lima".
        function roomWordsId(s){
            var d = String(s);
            if(d.length % 2 === 1) d = '0' + d;
            var out = [];
            for(var i = 0; i < d.length; i += 2){
                var p = d.substr(i, 2);
                out.push(p.charAt(0) === '0'
                    ? DIGIT_ID[p.charAt(0)] + ' ' + DIGIT_ID[p.charAt(1)]
                    : numberWordsId(parseInt(p, 10)));
            }
            return out.join(', ');
        }

        function numberWordsId(n){
            var s = ['nol','satu','dua','tiga','empat','lima','enam','tujuh','delapan','sembilan'];
            if(n < 10) return s[n];
            if(n === 10) return 'sepuluh';
            if(n === 11) return 'sebelas';
            if(n < 20) return s[n-10]+' belas';
            var p = s[Math.floor(n/10)]+' puluh', r = n % 10;
            return r ? p+' '+s[r] : p;
        }

        // "FD005" → [{t:'F',d:120},{t:'D',d:120},{t:'nol nol lima',d:0}]
        function splitNomor(s){
            var res = [], buf = [];
            String(s == null ? '' : s).toUpperCase().split('').forEach(function(ch){
                if(DIGIT_ID[ch]){ buf.push(DIGIT_ID[ch]); return; }
                if(LETTER_ID[ch]){
                    if(buf.length){ res.push({t:buf.join(', '), d:0}); buf = []; }
                    res.push({t:ch, d:120});   // huruf apa adanya, spt program lama
                }
            });
            if(buf.length) res.push({t:buf.join(', '), d:0});
            return res;
        }

        // Rantai promise agar dua panggilan tidak bertumpuk (spt enqueueTTS lama).
        var ttsChain = Promise.resolve();
        function announceBrowser(no, dest){
            if(!('speechSynthesis' in window)) return;
            warnNoIdVoice();

            ttsChain = ttsChain.then(function(){
                try{ speechSynthesis.cancel(); }catch(e){}
                var seq = [{t:'Nomor antrian', d:80}].concat(splitNomor(no));
                if(dest && String(dest).trim() && String(dest).trim() !== '—'){
                    seq.push({t:'silakan menuju '+DEST_WORD_ID, d:150});
                    // Ruang klinik: sebut 2 digit terakhir sbg bilangan utuh
                    // ("1859" → "lima puluh sembilan"). Selain itu dieja.
                    var d = String(dest).trim();
                    if(AREA === 'klinik' && /^\d+$/.test(d)){
                        // Nomor ruang disebut LENGKAP, dipecah per DUA digit:
                        // "1838" → "delapan belas, tiga puluh delapan".
                        // (samakan dengan SpeechController::roomSpoken())
                        seq.push({t: roomWordsId(d), d:0});
                    } else {
                        seq = seq.concat(splitNomor(d));
                    }
                }
                // Jalankan berurutan.
                return seq.reduce(function(p, s){
                    return p.then(function(){ return speakPart(s.t, s.d); });
                }, Promise.resolve());
            }).catch(function(e){ console.error('TTS chain:', e); });

            return ttsChain;
        }
        if('speechSynthesis' in window){ speechSynthesis.onvoiceschanged=function(){speechSynthesis.getVoices();}; }
        setInterval(function(){
            if(('speechSynthesis' in window) && speechSynthesis.speaking && speechSynthesis.paused){ speechSynthesis.resume(); }
        }, 5000);
        function callSound(no, dest, seq){
            // Mode pusat: layar diam total — chime & suara dari speaker pusat.
            if(SOUND_MODE === 'central'){ announce(no, dest, seq); return; }
            ding(); setTimeout(function(){ announce(no, dest, seq); }, 850);
        }

        var audioUnlocked=false;
        function unlockAudio(){
            hideAudioBlocked();
            if(audioUnlocked) return;
            audioUnlocked=true;
            try{ ding._c = ding._c || new (window.AudioContext||window.webkitAudioContext)(); if(ding._c.state==='suspended') ding._c.resume(); }catch(e){}
            try{ if('speechSynthesis' in window){ var w=new SpeechSynthesisUtterance(' '); w.volume=0; speechSynthesis.speak(w); } }catch(e){}
            // Buka izin autoplay untuk <audio> Piper: putar 1 audio senyap.
            // Memakai WAV diam yang valid agar izin benar-benar terpakai.
            try{
                player.muted = true;
                player.src = 'data:audio/wav;base64,UklGRiQAAABXQVZFZm10IBAAAAABAAEAgD4AAAB9AAACABAAZGF0YQAAAAA=';
                player.play().then(function(){
                    player.pause(); player.muted = false;
                }).catch(function(){ player.muted = false; });
            }catch(e){}
        }
        ['click','touchstart','keydown'].forEach(function(ev){
            document.addEventListener(ev, unlockAudio, {once:false, passive:true});
        });

        /*
         * lastSeq = penanda panggilan terakhir yang SUDAH diumumkan.
         * `primed` = apakah layar sudah menerima data pertama dari server.
         *
         * Keduanya dipisah karena: saat halaman baru dibuka, panggilan yang
         * SEDANG berjalan tidak boleh langsung diteriakkan (itu riwayat, bukan
         * panggilan baru). Tapi sesudah itu, panggilan pertama setelah layar
         * kosong HARUS berbunyi — dulu ikut terbungkam karena lastSeq di-reset
         * ke null tiap kali tidak ada pasien.
         */
        var lastSeq=null, primed=false;
        function esc(s){return String(s==null?'':s);}
        function poll(){
            fetch(JSON_URL,{headers:{'X-Requested-With':'XMLHttpRequest'}}).then(r=>r.json()).then(function(j){
                if(j.error) return;

                // --- Now Serving ---
                var idle=document.getElementById('idle'),num=document.getElementById('num'),
                    dest=document.getElementById('dest');
                if(j.current){
                    idle.style.display='none';num.style.display='block';dest.style.display='inline-flex';
                    num.textContent=j.current.no||'0000';
                    document.getElementById('room').textContent=j.current.tujuan?esc(j.current.tujuan):'—';
                    var key=(j.current.no||'')+'|'+(j.current.seq||'');
                    if(key!==lastSeq){
                        var c=document.getElementById('nowCard');c.classList.remove('blink');void c.offsetWidth;c.classList.add('blink');
                        // Bunyikan KECUALI ini data pertama sejak halaman dibuka
                        // (panggilan yang sudah berjalan sebelum layar menyala).
                        if(primed) callSound(j.current.no, j.current.tujuan, j.current.seq);
                        lastSeq=key;
                    }
                }else{
                    idle.style.display='block';num.style.display='none';dest.style.display='none';
                    // JANGAN reset lastSeq ke null di sini — kalau direset,
                    // panggilan berikutnya dianggap "data pertama" dan bisu.
                    lastSeq='';
                }
                primed = true;

                var q=(j.queue||[]);

                // --- Kartu antrean menunggu ---
                var cards=document.querySelectorAll('#qrows .qc');
                for(var i=0;i<cards.length;i++){var cd=cards[i],r=q[i];
                    var bk = !!(r && r.booking);
                    // "lead" (giliran berikutnya) hanya untuk pasien yang
                    // sudah check-in — booking belum boleh dipanggil.
                    cd.classList.toggle('lead', i===0 && !!r && !bk);
                    cd.classList.toggle('booking', bk);
                    if(r){cd.classList.remove('empty');
                        cd.querySelector('.no').textContent=esc(r.no);
                        cd.querySelector('.to b').textContent=r.tujuan?esc(r.tujuan):'—';}
                    else{cd.classList.add('empty');
                        cd.querySelector('.no').textContent='—';
                        cd.querySelector('.to b').textContent='—';}}

                // --- Total menunggu ---
                var chip=document.getElementById('cntChip'),total=j.waiting_total;
                if(typeof total==='number'&&total>0){chip.style.display='block';
                    document.getElementById('cntNum').textContent=total;}
                else{chip.style.display='none';}
            }).catch(function(){});
        }
        poll();setInterval(poll,4000);
    </script>
</body>
</html>
