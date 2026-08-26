<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Client Display {{ implode(' - ', $rooms) }} - Ciputra Hospital</title>
    <link rel="icon" href="{{ asset('cihoslogo_biruijo.png') }}" type="image/png">
    <style>
        :root{
            /* Palet disamakan dengan layar utama: BIRU primary + EMAS aksen. */
            --navy:#0b2f6b;--navy3:#001b45;
            --brand:#c9992e;--brand-lt:#e8be5a;--brand-pale:rgba(201,153,46,.14);
            --gold:#c9992e;--gold-lt:#e8be5a;--gold-pale:rgba(201,153,46,.14);
            --ink:#14243f;--muted:#5c6f92;--line:rgba(11,47,107,.12);
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
        body::before{content:"";position:fixed;inset:0;background:rgba(238,243,251,.26);z-index:-1;}

        /* ---------- HEADER: bar glass melayang (sama dgn layar utama) ---------- */
        .hdwrap{flex-shrink:0;padding:1.2vh 1.4vw .2vh;}
        .hd{background:linear-gradient(120deg,rgba(255,255,255,.62),rgba(255,255,255,.42));
            -webkit-backdrop-filter:var(--blur);backdrop-filter:var(--blur);
            border:1px solid var(--glass-brd);border-radius:var(--r);
            box-shadow:0 8px 28px rgba(15,30,46,.14),inset 0 1px 0 rgba(255,255,255,.55);
            color:var(--navy);display:flex;align-items:center;justify-content:space-between;
            padding:0 2rem;height:10.5vh;min-height:74px;}
        /* Logo dipecah 2 crop dari file asli - bbox tinta:
             IKON     : x 36.0000%..61.3333%, y 18.9019%..39.4384%  (rasio 1.5510)
             WORDMARK : x 11.6000%..87.5333%, y 42.2883%..69.1115%  (rasio 3.5594)
           aspect-ratio wajib sama dgn rasio crop (offset `top` relatif tinggi wadah). */
        .hd .b{display:flex;align-items:center;gap:1.2rem;min-width:0;}
        .hd .b .crop{position:relative;overflow:hidden;flex-shrink:0;}
        .hd .b .crop img{position:absolute;height:auto;max-width:none;}
        .hd .b .mark{height:7vh;min-height:50px;aspect-ratio:1.5510;}
        .hd .b .mark img{width:394.74%;left:-142.11%;top:-92.04%;}
        .hd .b .wmk{height:7vh;min-height:50px;aspect-ratio:3.5594;}
        .hd .b .wmk img{width:131.69%;left:-15.28%;top:-157.66%;}
        .hd .r{text-align:right;}
        .hd .r .cl{font-size:3vh;font-weight:800;font-variant-numeric:tabular-nums;line-height:1.1;}
        .hd .r .dt{font-size:1.5vh;font-weight:600;color:var(--muted);}
        /* Muncul kalau refresh() gagal berturut-turut - layar tak crash, tapi
           petugas perlu tahu data yang tampil mungkin sudah tak terbaru. */
        .hd .r .offline{display:none;align-items:center;justify-content:flex-end;gap:.4rem;
            margin-top:.4vh;font-size:1.05vh;font-weight:700;color:#dc2626;letter-spacing:.03em;}
        .hd .r .offline.show{display:flex;}
        .hd .r .offline .dot{width:.9vh;height:.9vh;border-radius:50%;background:#dc2626;flex-shrink:0;
            animation:offlineBlink 1s infinite;}
        @keyframes offlineBlink{50%{opacity:.25;}}

        /* ---------- LAYOUT ---------- */
        .wrap{flex:1;display:grid;grid-template-columns:repeat({{ count($rooms) }},1fr);
            gap:1.4vw;padding:2vh 1.4vw 2.2vh;min-height:0;}
        .room{display:flex;flex-direction:column;gap:1.2vh;min-height:0;}

        /* ---------- KARTU DOKTER (poli + ruang + foto + now serving) ---------- */
        /* Tinggi dikunci juga - nama dokter kosong/panjang tidak boleh mengubah
           tinggi kartu, agar kedua kolom ruang tetap sejajar. */
        .rcard{flex-shrink:0;height:15vh;min-height:104px;
            background:var(--glass);-webkit-backdrop-filter:var(--blur);backdrop-filter:var(--blur);
            border:1px solid var(--glass-brd);border-radius:var(--r);box-shadow:var(--shadow);
            padding:1.4vh 1.2vw;display:flex;align-items:center;gap:1vw;}
        .rcard .ph{width:11vh;height:11vh;flex-shrink:0;border-radius:14px;overflow:hidden;
            border:1px solid var(--glass-brd);background:rgba(255,255,255,.75);
            display:grid;place-items:center;box-shadow:0 4px 14px rgba(0,27,69,.12);}
        .rcard .ph img{width:100%;height:100%;object-fit:cover;object-position:top center;}
        .rcard .ph svg{width:58%;height:58%;color:#a9b6cf;}
        .rcard .meta{flex:1;min-width:0;display:flex;flex-direction:column;gap:.5vh;}
        .rcard .poli{font-size:1.5vh;font-weight:800;letter-spacing:.14em;text-transform:uppercase;
            color:var(--brand);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
        .rcard .nm{font-size:2.5vh;font-weight:800;color:var(--navy);line-height:1.15;
            overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
        /* Ruang dibuat menonjol - ini penanda utama layar ini milik ruang mana. */
        .rcard .rm{display:inline-flex;align-items:baseline;gap:.45rem;align-self:flex-start;
            background:var(--brand-pale);border:1px solid rgba(201,153,46,.20);
            border-radius:999px;padding:.3vh .8rem;}
        .rcard .rm span{font-size:1.3vh;font-weight:800;letter-spacing:.1em;text-transform:uppercase;color:#4a5f85;}
        .rcard .rm b{font-size:2vh;font-weight:800;color:var(--navy);font-variant-numeric:tabular-nums;}

        /* ---------- NOW SERVING ---------- */
        /* Tinggi DIKUNCI: isi berubah (nomor vs "waiting") tapi kotak tetap sama,
           supaya kolom antar-ruang selalu sejajar. */
        .now{flex-shrink:0;height:11.5vh;min-height:80px;
            border-radius:var(--r);box-shadow:var(--shadow);overflow:hidden;
            background:linear-gradient(150deg,rgba(0,27,69,.93),rgba(11,47,107,.89) 55%,rgba(21,73,143,.85));
            -webkit-backdrop-filter:var(--blur);backdrop-filter:var(--blur);
            border:1px solid rgba(255,255,255,.18);color:#fff;
            display:flex;flex-direction:column;align-items:center;justify-content:center;
            text-align:center;padding:1.6vh 1vw;gap:.5vh;}
        .now .tag{display:flex;align-items:center;gap:.6rem;font-size:1.5vh;font-weight:700;
            letter-spacing:.18em;text-transform:uppercase;color:#e8be5a;}
        .now .tag .pulse{width:.9vh;height:.9vh;border-radius:50%;background:var(--brand-lt);
            box-shadow:0 0 0 0 rgba(232,190,90,.75);animation:pulse 2s infinite;}
        @keyframes pulse{70%{box-shadow:0 0 0 1.2vh rgba(232,190,90,0);}100%{box-shadow:0 0 0 0 rgba(232,190,90,0);}}
        /* min-height + centering di sini SAMA untuk kedua state (nomor besar
           vs "waiting for call…") - teks idle jauh lebih kecil, tanpa ini
           blok kontennya jadi lebih pendek dan label "NOW SERVING" ikut
           bergeser naik/turun antar kartu, membuat dua kartu di sebelahan
           terlihat tidak sejajar. */
        .now .num{font-size:5.2vh;font-weight:800;line-height:1;font-variant-numeric:tabular-nums;
            letter-spacing:.02em;text-shadow:0 4px 20px rgba(0,0,0,.32);
            min-height:5.2vh;display:flex;align-items:center;justify-content:center;}
        .now .num.idle{font-size:2.4vh;font-weight:300;letter-spacing:.08em;text-transform:uppercase;opacity:.7;}

        /* ---------- SESI ---------- */
        /* Panel sesi mengisi tinggi yang tersedia (layar tidak menyisakan area
           kosong besar di bawah); chip nomor menempel ke atas via align-content
           pada .list, jadi ruang sisa ada di dalam panel, bukan di luar. */
        .sessions{flex:1;display:grid;grid-template-columns:1fr 1fr;gap:1vw;min-height:0;}
        .sess{background:var(--glass);-webkit-backdrop-filter:var(--blur);backdrop-filter:var(--blur);
            border:1px solid var(--glass-brd);border-radius:var(--r);box-shadow:var(--shadow);
            display:flex;flex-direction:column;overflow:hidden;min-height:0;}
        .sess-h{flex-shrink:0;background:rgba(255,255,255,.42);border-bottom:1px solid var(--line);
            padding:1vh .8vw;display:flex;align-items:center;justify-content:space-between;gap:.6rem;}
        .sess-h .ttl{display:flex;flex-direction:column;gap:.2vh;text-align:left;min-width:0;}
        .sess-h .lbl{font-size:1.6vh;font-weight:800;letter-spacing:.12em;color:var(--navy);line-height:1;}
        .sess-h .jm{font-size:1.35vh;font-weight:700;color:var(--muted);line-height:1;min-height:1.5vh;
            font-variant-numeric:tabular-nums;}
        /* Jumlah antre per sesi - daftar chip bisa panjang, angka ini ringkasannya. */
        .sess-h .cnt{flex-shrink:0;font-size:1.35vh;font-weight:800;color:var(--navy);
            background:var(--brand-pale);border:1px solid rgba(201,153,46,.20);
            border-radius:999px;padding:.3vh .7rem;white-space:nowrap;}
        .sess-h .cnt b{color:var(--brand);font-variant-numeric:tabular-nums;}

        /* Panel dibagi 2 kolom tetap oleh garis pembatas permanen. Nomor
           mengisi kolom KIRI ke bawah dulu (maks. ROWS), lalu meluber ke
           kolom KANAN. Garis selalu tampak, walau kanan masih kosong. */
        .list{flex:1;overflow:auto;padding:.9vh .6vw 1.1vh;display:grid;
            grid-auto-flow:column;grid-template-rows:repeat(var(--rows,8),auto);
            grid-template-columns:1fr 1fr;gap:.5vh 0;align-content:start;
            position:relative;}
        /* Garis pembatas tengah - setinggi area daftar. */
        .list::before{content:"";position:absolute;left:50%;top:.6vh;bottom:.6vh;
            border-left:1px solid rgba(11,47,107,.22);pointer-events:none;}
        /* min-height TETAP walau isinya 1 baris (nomor saja) atau 2 baris
           (nomor + label "Next"/"Belum Check-in") - tanpa ini baris grid
           auto-sizing ikut tinggi konten tiap chip, jadi tinggi antar chip
           beda-beda tergantung ada label atau tidak. */
        .list .q{background:var(--glass-strong);border:1px solid var(--glass-brd);border-radius:9px;
            display:flex;flex-direction:column;align-items:center;justify-content:center;
            min-height:4.6vh;padding:.6vh .2vw;margin:0 .5vw;box-shadow:0 2px 8px rgba(0,27,69,.07);}
        .list .q .a{font-size:1.8vh;font-weight:800;color:var(--navy);font-variant-numeric:tabular-nums;
            line-height:1.15;}
        /* Chip pertama = giliran berikutnya, diberi label supaya tidak ambigu. */
        .list .q.lead{background:rgba(253,244,222,.92);border-color:rgba(201,153,46,.5);
            box-shadow:0 3px 12px rgba(201,153,46,.18);}
        .list .q.lead .a{color:var(--brand);}
        .list .q .nx{font-size:.95vh;font-weight:800;letter-spacing:.12em;text-transform:uppercase;
            color:var(--brand);line-height:1;margin-top:.15vh;}
        /* Placeholder pasien yang sudah dapat nomor tapi belum check-in di
           pendaftaran - nomornya tetap tampil & tetap dihitung "Waiting"
           (posisi sudah pasti, tak akan meloncat begitu ia check-in), tapi
           dibuat pudar & tidak setebal yang lain - tanpa label tambahan,
           cukup beda tampilan saja. */
        .list .q.pending{opacity:.8;}
        .list .q.pending .a{color:var(--muted);font-weight:600;}
        .list .none{grid-column:1/-1;margin:2.4vh auto;text-align:center;font-size:1.9vh;
            font-style:italic;font-weight:700;color:#8494b3;}

        /* ---------- MARQUEE ---------- */
        .mq{background:rgba(6,34,80,.86);-webkit-backdrop-filter:var(--blur);backdrop-filter:var(--blur);
            border-top:1px solid rgba(255,255,255,.14);color:#fff;height:3.2vh;min-height:24px;
            display:flex;align-items:center;overflow:hidden;flex-shrink:0;}
        .mq .t{white-space:nowrap;font-size:1.45vh;font-weight:800;letter-spacing:.1em;
            animation:scroll 26s linear infinite;padding-left:100%;}
        @keyframes scroll{from{transform:translateX(0);}to{transform:translateX(-100%);}}

        @media (prefers-reduced-motion:reduce){ .now .tag .pulse,.mq .t{animation:none;} }

        /* Muncul hanya bila browser memblokir audio panggilan (butuh 1x klik). */
        .alock{position:fixed;inset:0;z-index:99;display:none;
            align-items:center;justify-content:center;cursor:pointer;
            background:rgba(11,47,107,.55);-webkit-backdrop-filter:blur(6px);backdrop-filter:blur(6px);}
        .alock.on{display:flex;}
        .alock .box{background:#fff;border-radius:var(--r);padding:3.4vh 3vw;text-align:center;
            box-shadow:0 20px 60px rgba(0,27,69,.4);max-width:60vw;}
        .alock .ic{font-size:6vh;line-height:1;}
        .alock .t1{font-size:3vh;font-weight:800;color:var(--navy);margin-top:1.4vh;}
        .alock .t2{font-size:2vh;font-weight:600;color:var(--muted);margin-top:1vh;line-height:1.5;}
    </style>
</head>
<body>
    <div class="hdwrap">
        <header class="hd">
            <div class="b">
                <div class="crop mark"><img src="{{ asset('cihoslogo_biruijo.png') }}" alt=""></div>
                <div class="crop wmk"><img src="{{ asset('cihoslogo_biruijo.png') }}" alt="Ciputra Hospital Surabaya - Enhancing Life"></div>
            </div>
            <div class="r">
                <div class="cl" id="cl">--:--:--</div>
                <div class="dt" id="dt">-</div>
                <div class="offline" id="offlineBadge"><span class="dot"></span>Koneksi terputus - data mungkin tak terbaru</div>
            </div>
        </header>
    </div>

    <main class="wrap">
        @foreach ($rooms as $rc)
            <div class="room" data-room="{{ $rc }}">
                <div class="rcard">
                    <div class="ph"><span class="phi"></span></div>
                    <div class="meta">
                        <div class="poli">-</div>
                        <div class="nm">-</div>
                        <div class="rm"><span>Room</span><b>{{ $rc }}</b></div>
                    </div>
                </div>
                <div class="now">
                    <div class="tag"><i class="pulse"></i><span>Now Serving</span></div>
                    <div class="num idle">-</div>
                </div>
                <div class="sessions"></div>
            </div>
        @endforeach
    </main>

    <footer class="mq"><span class="t">WELCOME TO CIPUTRA HOSPITAL SURABAYA</span></footer>

    <div class="alock" id="audioLock" onclick="unlockAudio()">
        <div class="box">
            <div class="ic">🔇</div>
            <div class="t1">Klik layar untuk mengaktifkan suara</div>
            <div class="t2">Browser memblokir suara otomatis.<br>Cukup klik sekali - pesan ini akan hilang.</div>
        </div>
    </div>

    <script>
        var JSON_BASE = @json(url('display/client'));  // + /{room}/json
        var DAYS=['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
        var MONTHS=['January','February','March','April','May','June','July','August','September','October','November','December'];
        function tick(){var d=new Date(),p=n=>String(n).padStart(2,'0');
            document.getElementById('cl').textContent=p(d.getHours())+':'+p(d.getMinutes())+':'+p(d.getSeconds());
            document.getElementById('dt').textContent=DAYS[d.getDay()]+', '+p(d.getDate())+' '+MONTHS[d.getMonth()]+' '+d.getFullYear();}
        setInterval(tick,1000);tick();

        function esc(s){return String(s==null?'':s).replace(/[&<>"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));}
        var AVA='<svg fill="currentColor" viewBox="0 0 20 20"><path clip-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" fill-rule="evenodd"></path></svg>';

        var ROWS = 8; // nomor mengalir ke bawah dulu, maks 8, lalu kolom baru.
        function listHtml(arr){
            // Tanpa antrean → kosong saja, tanpa teks apa pun.
            if(!arr||!arr.length) return '';
            // "Next" nempel ke pasien NYATA pertama (sudah check-in) - placeholder
            // yang belum check-in tak pernah jadi giliran berikutnya.
            var leadIdx = -1;
            for (var i=0;i<arr.length;i++){ if(!arr[i].pending){ leadIdx=i; break; } }
            return arr.map(function(item,i){
                var cls = 'q' + (i===leadIdx?' lead':'') + (item.pending?' pending':'');
                return '<div class="'+cls+'"><span class="a">'+esc(item.ticket)+'</span>'+
                    (i===leadIdx?'<span class="nx">Next</span>':'')+
                    '</div>';
            }).join('');
        }
        function jamText(w){ return (w&&w.start)? (w.start+(w.end?' - '+w.end:'')) : ''; }
        function sessHtml(s){
            var jm = jamText(s.jam);
            // Pasien belum check-in tetap dihitung - nomornya sudah pasti ada.
            var n  = (s.list||[]).length;
            var cnt = n ? '<span class="cnt">Waiting <b>'+n+'</b></span>' : '';
            return '<div class="sess">'+
                '<div class="sess-h">'+
                    '<span class="ttl"><span class="lbl">'+esc(s.label)+'</span>'+
                    '<span class="jm">'+esc(jm)+'</span></span>'+cnt+
                '</div>'+
                '<div class="list">'+listHtml(s.list)+'</div></div>';
        }
        /*
         * SUARA PANGGILAN - layar ini bersuara SENDIRI (langsung di TV/device
         * yang membuka halaman ini), tanpa butuh perangkat speaker terpisah.
         * `seq` (panggil_count) dari tiap kartu dipakai untuk mendeteksi
         * panggilan BARU: begitu naik dibanding nilai terakhir yang sudah
         * diumumkan untuk ruang itu, "sedang" diumumkan. `primed` mencegah
         * pasien yang SUDAH sedang dilayani saat halaman baru dibuka ikut
         * diteriakkan seolah panggilan baru.
         */
        var SPEECH = @json(url('display/speech'));
        var BEL_URL = @json(asset('bel.mp3'));
        var BEL_OPEN  = { start: 0,    end: 4.0  };
        var BEL_CLOSE = { start: 5.45, end: null };
        var bell = new Audio(BEL_URL); bell.preload = 'auto';
        var player = new Audio(); player.preload = 'auto';
        var sayQueue = [], saying = false;
        var lastSeq = {}, primed = {};

        var audioUnlocked = false;
        function showAudioBlocked(){ var el=document.getElementById('audioLock'); if(el) el.classList.add('on'); }
        function hideAudioBlocked(){ var el=document.getElementById('audioLock'); if(el) el.classList.remove('on'); }
        function unlockAudio(){
            hideAudioBlocked();
            if(audioUnlocked) return;
            audioUnlocked = true;
            try{
                bell.muted = true;
                bell.play().then(function(){ bell.pause(); bell.currentTime=0; bell.muted=false; }).catch(function(){ bell.muted=false; });
            }catch(e){}
            try{
                player.muted = true;
                player.src = 'data:audio/wav;base64,UklGRiQAAABXQVZFZm10IBAAAAABAAEAgD4AAAB9AAACABAAZGF0YQAAAAA=';
                player.play().then(function(){ player.pause(); player.muted=false; }).catch(function(){ player.muted=false; });
            }catch(e){}
        }
        ['click','touchstart','keydown'].forEach(function(ev){
            document.addEventListener(ev, unlockAudio, {once:false, passive:true});
        });

        function playBell(seg, done){
            var timer=null, finished=false;
            var stop=function(){
                if(finished) return; finished=true;
                if(timer){ clearTimeout(timer); timer=null; }
                bell.removeEventListener('ended', stop);
                try{ bell.pause(); }catch(e){}
                done();
            };
            bell.addEventListener('ended', stop);
            try{
                bell.currentTime = seg.start;
                if(seg.end != null){ timer = setTimeout(stop, (seg.end-seg.start)*1000); }
                bell.play().catch(function(err){ if(err && err.name==='NotAllowedError'){ showAudioBlocked(); } stop(); });
            }catch(e){ stop(); }
        }
        function playTwice(src, done){
            var round = 0;
            var onEnd = function(){
                round++;
                if(round < 2){
                    setTimeout(function(){ try{ player.currentTime=0; player.play().catch(fail); }catch(e){ fail(); } }, 1200);
                } else { player.removeEventListener('ended', onEnd); done(); }
            };
            var fail = function(){ player.removeEventListener('ended', onEnd); done(); };
            player.addEventListener('ended', onEnd);
            player.src = src; player.currentTime = 0;
            player.play().catch(function(err){ if(err && err.name==='NotAllowedError'){ showAudioBlocked(); } fail(); });
        }
        function sayPump(){
            if(saying || !sayQueue.length) return;
            saying = true;
            var item = sayQueue.shift();
            var url = SPEECH + '?no=' + encodeURIComponent(item.no) + '&dest=' + encodeURIComponent(item.dest) + '&area=klinik';
            var finish = function(){ saying = false; sayPump(); };
            fetch(url, {headers:{'X-Requested-With':'XMLHttpRequest'}}).then(function(r){ return r.json(); })
                .then(function(j){
                    if(!(j && j.url)){ finish(); return; }
                    playBell(BEL_OPEN, function(){
                        setTimeout(function(){
                            playTwice(j.url, function(){
                                setTimeout(function(){ playBell(BEL_CLOSE, finish); }, 900);
                            });
                        }, 350);
                    });
                })
                .catch(finish);
        }
        // Deteksi panggilan baru dari data kartu yang baru dipoll.
        function detectNewCall(c){
            var room = String(c.room);
            var seq = c.seq || 0;
            if(!primed[room]){
                // Poll pertama untuk ruang ini: catat baseline, jangan umumkan
                // (itu kondisi yang sudah berjalan, bukan panggilan baru).
                lastSeq[room] = seq;
                primed[room] = true;
                return;
            }
            if(seq !== lastSeq[room]){
                lastSeq[room] = seq;
                if(c.sedang){
                    sayQueue.push({ no: c.sedang, dest: room });
                    sayPump();
                }
            }
        }

        function paintCard(el, c){
            el.querySelector('.rcard .poli').textContent = (c.poli||'-').toUpperCase();
            var ph = el.querySelector('.rcard .ph');
            if(c.photo){ ph.innerHTML='<img src="'+esc(c.photo)+'" onerror="this.parentNode.innerHTML=&quot;'+AVA.replace(/"/g,'&quot;')+'&quot;">'; }
            else { ph.innerHTML=AVA; }
            el.querySelector('.rcard .nm').textContent = c.doctor || '-';

            var num = el.querySelector('.now .num');
            if(c.sedang){ num.textContent=c.sedang; num.classList.remove('idle'); }
            else { num.textContent='Waiting for call…'; num.classList.add('idle'); }

            el.querySelector('.sessions').innerHTML = (c.sesi||[]).map(sessHtml).join('');
        }
        // Tandai layar "offline" kalau SEMUA ruang gagal di-refresh 2x
        // BERTURUT-TURUT (~8 detik tanpa kontak) - bukan sekali gagal langsung,
        // supaya blip jaringan sekejap tak bikin badge berkedip-kedip tiap poll.
        var pollFails = 0;
        function setOffline(bad){
            pollFails = bad ? pollFails + 1 : 0;
            document.getElementById('offlineBadge').classList.toggle('show', pollFails >= 2);
        }

        function refresh(){
            var tasks = Array.prototype.map.call(document.querySelectorAll('.room'), function(el){
                var room = el.dataset.room;
                return fetch(JSON_BASE+'/'+encodeURIComponent(room)+'/json').then(function(r){ return r.json(); }).then(function(j){
                    if(j.error||!j.cards) return true;
                    var c = j.cards.find(function(x){return String(x.room)===String(room);}) || j.cards[0];
                    if(c){ detectNewCall(c); paintCard(el, c); }
                    return true;
                }).catch(function(){ return false; });
            });
            // Offline hanya kalau SEMUA kartu gagal - satu ruang bermasalah
            // sendirian (mis. dokter belum login) bukan berarti koneksi putus.
            Promise.all(tasks).then(function(results){
                setOffline(results.length > 0 && results.indexOf(true) === -1);
            });
        }
        refresh(); setInterval(refresh, 4000);
    </script>
</body>
</html>
