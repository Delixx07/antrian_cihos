<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $judul }} - Ciputra Hospital</title>
    <link rel="icon" href="{{ asset('cihoslogo_biruijo.png') }}" type="image/png">
    <style>
        :root{
            /* Sama persis dgn display/layar.blade.php - biru + emas, glassmorphism. */
            --navy:#0b2f6b;--navy3:#001b45;
            --brand:#c9992e;--brand-lt:#e8be5a;--brand-pale:rgba(201,153,46,.14);
            --gold:#c9992e;--gold-lt:#e8be5a;
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

        /* ---------- HEADER: bar glass melayang (identik display/layar) ---------- */
        .hdwrap{flex-shrink:0;padding:1.2vh 1.4vw .2vh;}
        .hd{position:relative;background:linear-gradient(120deg,rgba(255,255,255,.62),rgba(255,255,255,.42));
            -webkit-backdrop-filter:var(--blur);backdrop-filter:var(--blur);
            border:1px solid var(--glass-brd);border-radius:var(--r);
            box-shadow:0 8px 28px rgba(15,30,46,.14),inset 0 1px 0 rgba(255,255,255,.55);
            color:var(--navy);display:flex;align-items:center;justify-content:space-between;
            padding:0 2rem;height:10.5vh;min-height:74px;}
        .hd .b{display:flex;align-items:center;gap:1.2rem;min-width:0;}
        .hd .b .crop{position:relative;overflow:hidden;flex-shrink:0;}
        .hd .b .crop img{position:absolute;height:auto;max-width:none;}
        .hd .b .mark{height:7vh;min-height:50px;aspect-ratio:1.5510;}
        .hd .b .mark img{width:394.74%;left:-142.11%;top:-92.04%;}
        .hd .b .wmk{height:7vh;min-height:50px;aspect-ratio:3.5594;}
        .hd .b .wmk img{width:131.69%;left:-15.28%;top:-157.66%;}
        /* Absolute + translate, BUKAN flex:1 - supaya benar-benar di tengah BAR,
           bukan di tengah ruang sisa antara logo & jam (yang lebarnya beda). */
        .hd .mid{position:absolute;left:50%;top:50%;transform:translate(-50%,-50%);
            white-space:nowrap;font-size:2.2vh;font-weight:800;letter-spacing:.1em;
            text-transform:uppercase;color:var(--navy);}
        .hd .r{text-align:right;}
        .hd .r .cl{font-size:3vh;font-weight:800;font-variant-numeric:tabular-nums;line-height:1.1;}
        .hd .r .dt{font-size:1.5vh;font-weight:600;color:var(--muted);}
        /* Muncul kalau poll() gagal berturut-turut - layar tak crash, tapi
           petugas perlu tahu data yang tampil mungkin sudah tak terbaru. */
        .hd .r .offline{display:none;align-items:center;justify-content:flex-end;gap:.4rem;
            margin-top:.4vh;font-size:1.05vh;font-weight:700;color:#dc2626;letter-spacing:.03em;}
        .hd .r .offline.show{display:flex;}
        .hd .r .offline .dot{width:.9vh;height:.9vh;border-radius:50%;background:#dc2626;flex-shrink:0;
            animation:offlineBlink 1s infinite;}
        @keyframes offlineBlink{50%{opacity:.25;}}

        /* ---------- LAYOUT ---------- */
        .main{flex:1;display:flex;flex-direction:column;justify-content:center;
            gap:2.6vh;padding:2.6vh 1.4vw 2.6vh;min-height:0;}
        .top{flex:0 0 auto;display:grid;grid-template-columns:1fr 1fr;gap:1.4vw;min-height:0;align-items:stretch;}
        .top>*{height:min(46vh,58vh);}

        /* ---------- HERO "Now Serving" (identik .now di display/layar) ---------- */
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

        /* ---------- QUEUE (glass panel, identik .qlist), dipecah 2 kolom ---------- */
        .qlist{background:var(--glass);-webkit-backdrop-filter:var(--blur);backdrop-filter:var(--blur);
            border:1px solid var(--glass-brd);border-radius:var(--r);box-shadow:var(--shadow);
            display:flex;flex-direction:column;overflow:hidden;min-height:0;}
        .qlist .hd2{flex-shrink:0;display:flex;align-items:center;gap:.9rem;
            padding:1.1vh 1.2vw .9vh;border-bottom:1px solid var(--line);}
        .qlist .hd2 .t{font-size:1.8vh;font-weight:800;letter-spacing:.14em;text-transform:uppercase;color:var(--navy);}
        .qlist .hd2 .line{flex:1;height:1px;background:linear-gradient(90deg,var(--line),transparent);}

        .qsplit{flex:1;display:grid;grid-template-columns:1fr 1fr;min-height:0;}
        .qcol{display:flex;flex-direction:column;min-height:0;padding:1vh 1.1vw;}
        .qcol+.qcol{border-left:1px solid var(--line);}
        .qcol .ct{font-size:1.5vh;font-weight:800;letter-spacing:.1em;text-transform:uppercase;
            color:var(--navy);padding-bottom:.7vh;margin-bottom:.5vh;border-bottom:1px solid var(--line);}
        .qcol .list{flex:1;overflow-y:auto;display:flex;flex-direction:column;gap:.5vh;}
        .qcol .row{background:var(--glass-strong);border:1px solid var(--glass-brd);border-radius:10px;
            padding:.7vh 1vw;font-size:2vh;font-weight:800;color:var(--navy);font-variant-numeric:tabular-nums;}
        .qcol .empty{color:var(--muted);font-size:1.5vh;font-weight:600;padding:.6vh .1vw;}

        /* ---------- SEDANG DIPROSES (4 kartu, gaya .qc di display/layar) ---------- */
        .procwrap{flex:0 0 auto;}
        .procrows{display:grid;grid-template-columns:repeat(4,1fr);gap:1vw;padding:1.1vh 1.2vw 1.3vh;}
        .qc{background:var(--glass-strong);border:1px solid var(--glass-brd);border-radius:14px;
            height:13vh;min-height:88px;
            display:flex;flex-direction:column;align-items:center;justify-content:center;gap:.4vh;
            position:relative;overflow:hidden;transition:opacity .3s;
            box-shadow:0 3px 12px rgba(15,30,46,.08);}
        .qc .no{font-size:4vh;font-weight:800;color:var(--navy);line-height:1;font-variant-numeric:tabular-nums;}
        .qc .to{display:flex;align-items:baseline;gap:.4rem;}
        .qc .to span{font-size:1.3vh;font-weight:800;letter-spacing:.1em;text-transform:uppercase;color:#4a5f85;}
        .qc .to b{font-size:1.9vh;font-weight:800;color:var(--ink);font-variant-numeric:tabular-nums;}
        .qc.empty{opacity:.45;}
        .qc.empty .no,.qc.empty .to b{color:#a9b6cf;}

        /* ---------- MARQUEE (identik display/layar) ---------- */
        .mq{background:rgba(6,34,80,.86);-webkit-backdrop-filter:var(--blur);backdrop-filter:var(--blur);
            border-top:1px solid rgba(255,255,255,.14);color:#fff;height:3.2vh;min-height:24px;
            display:flex;align-items:center;overflow:hidden;flex-shrink:0;}
        .mq .t{white-space:nowrap;font-size:1.45vh;font-weight:800;letter-spacing:.06em;text-transform:uppercase;
            animation:scroll 26s linear infinite;padding-left:100%;}
        @keyframes scroll{from{transform:translateX(0);}to{transform:translateX(-100%);}}

        @media (prefers-reduced-motion:reduce){ .now .tag .pulse,.now.blink,.mq .t{animation:none;} }
    </style>
</head>
<body>
    <div class="hdwrap">
        <header class="hd">
            <div class="b">
                <div class="crop mark"><img src="{{ asset('cihoslogo_biruijo.png') }}" alt=""></div>
                <div class="crop wmk"><img src="{{ asset('cihoslogo_biruijo.png') }}" alt="Ciputra Hospital Surabaya - Enhancing Life"></div>
            </div>
            <div class="mid">{{ $judul }}</div>
            <div class="r">
                <div class="cl" id="cl">--:--:--</div>
                <div class="dt" id="dt">-</div>
                <div class="offline" id="offlineBadge"><span class="dot"></span>Koneksi terputus - data mungkin tak terbaru</div>
            </div>
        </header>
    </div>

    <main class="main">
        <div class="top">
            {{-- Nomor sedang dipanggil - KIRI, gaya sama dgn "Now Serving" --}}
            <div class="now" id="heroCard">
                <div class="tag"><i class="pulse"></i><span>Now Serving - Pharmacy</span></div>
                <div class="idle-txt" id="idle">Waiting for call…</div>
                <div class="num" id="heroNum" style="display:none;">0000</div>
                <div class="dest" id="heroDestWrap" style="display:none;">
                    <span>Counter</span><b id="heroDest">-</b>
                </div>
            </div>

            {{-- Waiting queue - KANAN, dipecah Non Racik / Racik --}}
            <div class="qlist">
                <div class="hd2"><div class="t">Waiting Queue</div><div class="line"></div></div>
                <div class="qsplit">
                    <div class="qcol">
                        <div class="ct">Non Racik</div>
                        <div class="list" id="qNonRacik"><div class="empty">No queue</div></div>
                    </div>
                    <div class="qcol">
                        <div class="ct">Racik</div>
                        <div class="list" id="qRacik"><div class="empty">No queue</div></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- In progress - full width di bawah, 4 kartu --}}
        <div class="qlist procwrap">
            <div class="hd2"><div class="t">In Progress</div><div class="line"></div></div>
            <div class="procrows" id="processingRow">
                @for ($i = 0; $i < 4; $i++)
                    <div class="qc empty">
                        <div class="no">-</div>
                        <div class="to"><span>Counter</span><b>-</b></div>
                    </div>
                @endfor
            </div>
        </div>
    </main>

    <footer class="mq"><div class="t">{{ $runningText }} &nbsp;•&nbsp; {{ $runningText }} &nbsp;•&nbsp; {{ $runningText }}</div></footer>

    <script>
        var AREA = 'farmasi';
        var DEST_WORD_ID = 'counter';
    </script>
    @include('partials.display-speech')

    <script>
        var JSON_URL = "{{ $jsonUrl }}";
        var DAYS=['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
        var MONTHS=['January','February','March','April','May','June','July','August','September','October','November','December'];
        function tick(){var d=new Date(),p=n=>String(n).padStart(2,'0');
            document.getElementById('cl').textContent=p(d.getHours())+':'+p(d.getMinutes())+':'+p(d.getSeconds());
            document.getElementById('dt').textContent=DAYS[d.getDay()]+', '+p(d.getDate())+' '+MONTHS[d.getMonth()]+' '+d.getFullYear();}
        setInterval(tick,1000);tick();

        function esc(s){ return String(s==null?'':s); }

        function renderQueue(elId, rows){
            var el = document.getElementById(elId);
            if(!rows || !rows.length){
                el.innerHTML = '<div class="empty">No queue</div>';
                return;
            }
            el.innerHTML = rows.map(function(r){
                return '<div class="row">'+esc(r.no)+'</div>';
            }).join('');
        }

        var lastSeq=null, primed=false;

        // Tandai layar "offline" kalau poll() gagal 2x BERTURUT-TURUT (~8 detik
        // tanpa kontak) - bukan sekali gagal langsung, supaya blip jaringan
        // sekejap tak bikin badge berkedip-kedip tiap poll.
        var pollFails = 0;
        function setOffline(bad){
            pollFails = bad ? pollFails + 1 : 0;
            document.getElementById('offlineBadge').classList.toggle('show', pollFails >= 2);
        }

        function poll(){
            fetch(JSON_URL, {headers:{'X-Requested-With':'XMLHttpRequest'}}).then(function(r){ return r.json(); }).then(function(j){
                setOffline(false);
                if(j.error) return;

                // --- Nomor sedang dipanggil ---
                var hero = document.getElementById('heroCard');
                var idle = document.getElementById('idle'), num = document.getElementById('heroNum'),
                    destWrap = document.getElementById('heroDestWrap');
                if(j.current){
                    idle.style.display='none'; num.style.display='block'; destWrap.style.display='inline-flex';
                    num.textContent = j.current.no || '0000';
                    document.getElementById('heroDest').textContent = j.current.tujuan ? esc(j.current.tujuan) : '-';
                    var key = (j.current.no||'')+'|'+(j.current.seq||'');
                    if(key !== lastSeq){
                        hero.classList.remove('blink'); void hero.offsetWidth; hero.classList.add('blink');
                        if(primed) callSound(j.current.no, j.current.tujuan, j.current.seq);
                        lastSeq = key;
                    }
                } else {
                    idle.style.display='block'; num.style.display='none'; destWrap.style.display='none';
                    lastSeq = '';
                }
                primed = true;

                // --- Antrian menunggu, dua kolom ---
                renderQueue('qNonRacik', j.queue_non_racik);
                renderQueue('qRacik', j.queue_racik);

                // --- 4 kartu "sedang diproses" ---
                var cards = document.querySelectorAll('#processingRow .qc');
                var proc = j.processing || [];
                for(var i=0;i<cards.length;i++){
                    var p = proc[i];
                    if(p){
                        cards[i].classList.remove('empty');
                        cards[i].querySelector('.no').textContent = esc(p.no);
                        cards[i].querySelector('.to b').textContent = p.tujuan ? esc(p.tujuan) : '-';
                    } else {
                        cards[i].classList.add('empty');
                        cards[i].querySelector('.no').textContent = '-';
                        cards[i].querySelector('.to b').textContent = '-';
                    }
                }
            }).catch(function(){ setOffline(true); });
        }
        poll(); setInterval(poll, 4000);
    </script>
</body>
</html>
