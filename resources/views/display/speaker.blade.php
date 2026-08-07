<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Speaker Pusat — Antrian Ciputra Hospital</title>
    <link rel="icon" href="{{ asset('cihoslogo_biruijo.png') }}" type="image/png">
    <style>
        :root{--navy:#0b2f6b;--navy3:#001b45;--gold:#c9992e;--gold-lt:#e8be5a;
              --ink:#14243f;--muted:#5c6f92;--line:rgba(11,47,107,.12);}
        *{box-sizing:border-box;margin:0;padding:0;}
        html,body{height:100%;}
        body{font-family:'Segoe UI',system-ui,sans-serif;color:var(--ink);min-height:100vh;
            display:flex;align-items:center;justify-content:center;padding:4vh 4vw;
            background:linear-gradient(150deg,#f4f7fc,#e8eef8);}
        .card{background:#fff;border-radius:20px;box-shadow:0 20px 60px rgba(0,27,69,.16);
            width:100%;max-width:680px;overflow:hidden;}
        .hd{background:linear-gradient(150deg,var(--navy3),var(--navy) 60%,#15498f);
            color:#fff;padding:2.4rem 2rem;text-align:center;}
        .hd h1{font-size:1.5rem;font-weight:800;letter-spacing:.04em;}
        .hd p{font-size:.92rem;opacity:.8;margin-top:.5rem;line-height:1.5;}
        .body{padding:2rem;}

        .state{display:flex;align-items:center;gap:1rem;padding:1.2rem 1.4rem;border-radius:14px;
            background:#f6f8fc;border:1px solid var(--line);}
        .dot{width:14px;height:14px;border-radius:50%;background:#cbd5e1;flex-shrink:0;}
        .dot.on{background:#16a34a;box-shadow:0 0 0 0 rgba(22,163,74,.6);animation:p 2s infinite;}
        .dot.play{background:var(--gold);box-shadow:0 0 0 0 rgba(201,153,46,.6);animation:p 1s infinite;}
        .dot.off{background:#dc2626;}
        @keyframes p{70%{box-shadow:0 0 0 12px rgba(22,163,74,0);}100%{box-shadow:0 0 0 0 rgba(22,163,74,0);}}
        .state .t1{font-weight:800;font-size:1.05rem;}
        .state .t2{font-size:.86rem;color:var(--muted);margin-top:.15rem;}

        .now{margin-top:1.2rem;padding:1.4rem;border-radius:14px;text-align:center;
            background:linear-gradient(150deg,var(--navy3),var(--navy));color:#fff;display:none;}
        .now.on{display:block;}
        .now .lbl{font-size:.75rem;font-weight:800;letter-spacing:.18em;text-transform:uppercase;color:var(--gold-lt);}
        .now .no{font-size:3rem;font-weight:800;line-height:1.1;margin-top:.3rem;font-variant-numeric:tabular-nums;}
        .now .to{font-size:.95rem;opacity:.85;margin-top:.2rem;}

        .row{display:flex;gap:1rem;margin-top:1.2rem;}
        .stat{flex:1;background:#f6f8fc;border:1px solid var(--line);border-radius:14px;
            padding:1rem;text-align:center;}
        .stat b{display:block;font-size:1.8rem;font-weight:800;color:var(--navy);font-variant-numeric:tabular-nums;}
        .stat span{font-size:.78rem;color:var(--muted);font-weight:700;letter-spacing:.08em;text-transform:uppercase;}

        .btn{display:block;width:100%;margin-top:1.4rem;padding:1.1rem;border:none;border-radius:14px;
            background:var(--gold);color:#3a2a05;font-size:1.05rem;font-weight:800;cursor:pointer;
            letter-spacing:.03em;}
        .btn:hover{filter:brightness(1.06);}
        .btn.hide{display:none;}
        .note{margin-top:1.2rem;font-size:.82rem;color:var(--muted);line-height:1.6;
            background:#fffbeb;border:1px solid #fde68a;border-radius:12px;padding:.9rem 1rem;}
        .log{margin-top:1.2rem;max-height:170px;overflow:auto;font-size:.8rem;
            border-top:1px solid var(--line);padding-top:.8rem;}
        .log div{padding:.3rem 0;border-bottom:1px solid #f1f5f9;color:var(--muted);
            font-variant-numeric:tabular-nums;}
        .log b{color:var(--ink);}
    </style>
</head>
<body>
    <div class="card">
        <div class="hd">
            <h1>🔊 Speaker Pusat Antrian</h1>
            <p>Halaman ini memutar SEMUA pengumuman antrian rumah sakit,<br>
               satu per satu secara berurutan. Biarkan tab ini terbuka.</p>
        </div>
        <div class="body">
            <div class="state">
                <div class="dot" id="dot"></div>
                <div>
                    <div class="t1" id="st1">Belum aktif</div>
                    <div class="t2" id="st2">Klik tombol di bawah untuk mengaktifkan suara.</div>
                </div>
            </div>

            <div class="now" id="now">
                <div class="lbl">Sedang memanggil</div>
                <div class="no" id="nowNo">—</div>
                <div class="to" id="nowTo"></div>
            </div>

            <div class="row">
                <div class="stat"><b id="cPend">0</b><span>Menunggu</span></div>
                <div class="stat"><b id="cDone">0</b><span>Selesai</span></div>
            </div>

            <button class="btn" id="btn" onclick="start()">▶ Aktifkan Speaker</button>

            <div class="note" id="note">
                <b>Penting:</b> browser memblokir suara otomatis sampai tombol di atas
                ditekan. Tekan sekali saja, lalu biarkan halaman ini terbuka.
                Untuk otomatis tanpa klik, buka halaman ini lewat
                <code>buka-speaker.bat</code>.
            </div>

            <div class="log" id="log"></div>
        </div>
    </div>

    <script>
        var URL_NEXT = "{{ route('display.speech.next') }}";
        var URL_DONE = "{{ route('display.speech.done') }}";
        var player = new Audio();
        var running = false, busy = false, done = 0;

        function el(id){ return document.getElementById(id); }
        function log(msg){
            var d = document.createElement('div');
            var t = new Date().toTimeString().slice(0,8);
            d.innerHTML = '<b>'+t+'</b> — '+msg;
            el('log').prepend(d);
            while(el('log').children.length > 40) el('log').lastChild.remove();
        }
        function setState(cls, t1, t2){
            el('dot').className = 'dot ' + cls;
            el('st1').textContent = t1;
            el('st2').textContent = t2;
        }

        function start(){
            // Buka izin autoplay (lewat klik user, bila memang dibutuhkan).
            player.muted = true;
            player.src = 'data:audio/wav;base64,UklGRiQAAABXQVZFZm10IBAAAAABAAEAgD4AAAB9AAACABAAZGF0YQAAAAA=';
            player.play().then(function(){
                player.pause(); player.muted = false; go();
            }).catch(function(){ player.muted = false; go(); });
        }

        /*
         * MULAI OTOMATIS — tidak perlu menekan tombol apa pun.
         *
         * Bila halaman dibuka lewat buka-speaker.bat (memakai flag
         * --autoplay-policy=no-user-gesture-required), suara langsung boleh
         * berbunyi sehingga speaker aktif sendiri saat PC menyala.
         *
         * Bila ternyata browser masih memblokir (dibuka manual tanpa flag),
         * tombol "Aktifkan Speaker" otomatis muncul kembali — lihat play().
         */
        window.addEventListener('load', function(){
            player.muted = true;
            player.src = 'data:audio/wav;base64,UklGRiQAAABXQVZFZm10IBAAAAABAAEAgD4AAAB9AAACABAAZGF0YQAAAAA=';
            player.play().then(function(){
                player.pause(); player.muted = false;
                go();                       // izin ada → langsung jalan
            }).catch(function(){
                player.muted = false;
                // Diblokir → tetap pantau antrean, tapi tunggu 1x klik.
                setState('off','Menunggu izin suara','Klik di mana saja untuk mengaktifkan.');
            });
        });

        // Klik di mana pun pada halaman = mengaktifkan (bukan hanya tombol).
        document.addEventListener('click', function(){ if(!running) start(); }, {passive:true});
        function go(){
            running = true;
            el('btn').classList.add('hide');
            el('note').style.display = 'none';
            setState('on','Speaker aktif','Menunggu panggilan…');
            log('Speaker diaktifkan.');
            tick();
            setInterval(tick, 1500);
        }

        // Ambil satu pengumuman; server menahan bila masih ada yang berbunyi.
        function tick(){
            if(!running || busy) return;
            fetch(URL_NEXT, {headers:{'X-Requested-With':'XMLHttpRequest'}})
                .then(function(r){ return r.json(); })
                .then(function(j){
                    el('cPend').textContent = j.pending || 0;
                    if(!j.item) return;
                    if(!j.item.url){ log('Gagal render audio: '+j.item.no); return; }
                    play(j.item);
                })
                .catch(function(){
                    setState('off','Koneksi terputus','Mencoba menyambung ulang…');
                });
        }

        function play(item){
            busy = true;
            var word = item.area === 'klinik' ? 'Ruang' : 'Loket';
            el('now').classList.add('on');
            el('nowNo').textContent = item.no;
            el('nowTo').textContent = item.dest ? (word+' '+item.dest) : '';
            setState('play','Sedang memutar…', item.no + (item.dest ? ' → '+word+' '+item.dest : ''));
            log('Memanggil <b>'+item.no+'</b>'+(item.dest?' → '+word+' '+item.dest:''));

            var finish = function(){
                // Beri tahu server agar antrean berikutnya boleh jalan.
                fetch(URL_DONE+'?key='+encodeURIComponent(item.key),
                      {headers:{'X-Requested-With':'XMLHttpRequest'}})
                    .catch(function(){})
                    .finally(function(){
                        busy = false; done++;
                        el('cDone').textContent = done;
                        el('now').classList.remove('on');
                        setState('on','Speaker aktif','Menunggu panggilan…');
                    });
            };

            var round = 0;
            var onEnd = function(){
                round++;
                if(round < 2){
                    setTimeout(function(){
                        try{ player.currentTime = 0; player.play().catch(fail); }catch(e){ fail(); }
                    }, 1200);
                } else {
                    player.removeEventListener('ended', onEnd);
                    setTimeout(finish, 400);
                }
            };
            var fail = function(){
                player.removeEventListener('ended', onEnd);
                log('Gagal memutar audio.');
                finish();
            };

            player.addEventListener('ended', onEnd);
            player.src = item.url;
            player.currentTime = 0;
            player.play().catch(function(err){
                if(err && err.name === 'NotAllowedError'){
                    running = false;
                    el('btn').classList.remove('hide');
                    el('note').style.display = '';
                    setState('off','Suara diblokir browser','Klik tombol untuk mengaktifkan lagi.');
                }
                fail();
            });
        }
    </script>
</body>
</html>
