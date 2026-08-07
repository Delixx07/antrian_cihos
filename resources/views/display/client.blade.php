<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Client Display {{ implode(' - ', $rooms) }} — Ciputra Hospital</title>
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
        /* Logo dipecah 2 crop dari file asli — bbox tinta:
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

        /* ---------- LAYOUT ---------- */
        .wrap{flex:1;display:grid;grid-template-columns:repeat({{ count($rooms) }},1fr);
            gap:1.4vw;padding:2vh 1.4vw 2.2vh;min-height:0;}
        .room{display:flex;flex-direction:column;gap:1.2vh;min-height:0;}

        /* ---------- KARTU DOKTER (poli + ruang + foto + now serving) ---------- */
        /* Tinggi dikunci juga — nama dokter kosong/panjang tidak boleh mengubah
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
        /* Ruang dibuat menonjol — ini penanda utama layar ini milik ruang mana. */
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
        .now .num{font-size:5.2vh;font-weight:800;line-height:1;font-variant-numeric:tabular-nums;
            letter-spacing:.02em;text-shadow:0 4px 20px rgba(0,0,0,.32);}
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
        /* Jumlah antre per sesi — daftar chip bisa panjang, angka ini ringkasannya. */
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
        /* Garis pembatas tengah — setinggi area daftar. */
        .list::before{content:"";position:absolute;left:50%;top:.6vh;bottom:.6vh;
            border-left:1px solid rgba(11,47,107,.22);pointer-events:none;}
        .list .q{background:var(--glass-strong);border:1px solid var(--glass-brd);border-radius:9px;
            display:flex;flex-direction:column;align-items:center;justify-content:center;
            padding:.6vh .2vw;margin:0 .5vw;box-shadow:0 2px 8px rgba(0,27,69,.07);}
        .list .q .a{font-size:1.8vh;font-weight:800;color:var(--navy);font-variant-numeric:tabular-nums;
            line-height:1.15;}
        /* Chip pertama = giliran berikutnya, diberi label supaya tidak ambigu. */
        .list .q.lead{background:rgba(253,244,222,.92);border-color:rgba(201,153,46,.5);
            box-shadow:0 3px 12px rgba(201,153,46,.18);}
        .list .q.lead .a{color:var(--brand);}
        .list .q .nx{font-size:.95vh;font-weight:800;letter-spacing:.12em;text-transform:uppercase;
            color:var(--brand);line-height:1;margin-top:.15vh;}
        .list .none{grid-column:1/-1;margin:2.4vh auto;text-align:center;font-size:1.9vh;
            font-style:italic;font-weight:700;color:#8494b3;}

        /* ---------- MARQUEE ---------- */
        .mq{background:rgba(6,34,80,.86);-webkit-backdrop-filter:var(--blur);backdrop-filter:var(--blur);
            border-top:1px solid rgba(255,255,255,.14);color:#fff;height:3.2vh;min-height:24px;
            display:flex;align-items:center;justify-content:flex-end;padding:0 2rem;flex-shrink:0;}
        .mq span{font-size:1.45vh;font-weight:800;letter-spacing:.1em;}

        @media (prefers-reduced-motion:reduce){ .now .tag .pulse{animation:none;} }
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

    <main class="wrap">
        @foreach ($rooms as $rc)
            <div class="room" data-room="{{ $rc }}">
                <div class="rcard">
                    <div class="ph"><span class="phi"></span></div>
                    <div class="meta">
                        <div class="poli">—</div>
                        <div class="nm">—</div>
                        <div class="rm"><span>Room</span><b>{{ $rc }}</b></div>
                    </div>
                </div>
                <div class="now">
                    <div class="tag"><i class="pulse"></i><span>Now Serving</span></div>
                    <div class="num idle">—</div>
                </div>
                <div class="sessions"></div>
            </div>
        @endforeach
    </main>

    <footer class="mq"><span>WELCOME TO CIPUTRA HOSPITAL SURABAYA</span></footer>

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
            return arr.map(function(no,i){
                // Chip pertama diberi label "NEXT" agar highlight-nya tidak ambigu.
                // `.col2` = chip yang memulai kolom baru → dapat garis pembatas.
                var cls = 'q' + (i===0?' lead':'');
                return '<div class="'+cls+'"><span class="a">'+esc(no)+'</span>'+
                    (i===0?'<span class="nx">Next</span>':'')+'</div>';
            }).join('');
        }
        function jamText(w){ return (w&&w.start)? (w.start+(w.end?' - '+w.end:'')) : ''; }
        function sessHtml(s){
            var jm = jamText(s.jam);
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
         * CATATAN SUARA: halaman ini TIDAK mengeluarkan suara.
         * Semua pengumuman rumah sakit diputar oleh SATU perangkat yang
         * membuka /display/speaker — perangkat itu membaca panggilan langsung
         * dari database, jadi tetap berbunyi walau layar mana pun tidak dibuka.
         */
        function paintCard(el, c){
            el.querySelector('.rcard .poli').textContent = (c.poli||'—').toUpperCase();
            var ph = el.querySelector('.rcard .ph');
            if(c.photo){ ph.innerHTML='<img src="'+esc(c.photo)+'" onerror="this.parentNode.innerHTML=&quot;'+AVA.replace(/"/g,'&quot;')+'&quot;">'; }
            else { ph.innerHTML=AVA; }
            el.querySelector('.rcard .nm').textContent = c.doctor || '-';

            var num = el.querySelector('.now .num');
            if(c.sedang){ num.textContent=c.sedang; num.classList.remove('idle'); }
            else { num.textContent='Waiting for call…'; num.classList.add('idle'); }

            el.querySelector('.sessions').innerHTML = (c.sesi||[]).map(sessHtml).join('');
        }
        function refresh(){
            document.querySelectorAll('.room').forEach(function(el){
                var room = el.dataset.room;
                fetch(JSON_BASE+'/'+encodeURIComponent(room)+'/json').then(r=>r.json()).then(function(j){
                    if(j.error||!j.cards) return;
                    var c = j.cards.find(function(x){return String(x.room)===String(room);}) || j.cards[0];
                    if(c) paintCard(el, c);
                }).catch(function(){});
            });
        }
        refresh(); setInterval(refresh, 4000);
    </script>
</body>
</html>
