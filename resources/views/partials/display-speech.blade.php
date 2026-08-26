{{-- Pengumuman suara bersama untuk layar display (klinik/kasir/farmasi).
     Includer WAJIB set `AREA` dan `DEST_WORD_ID` (var JS) SEBELUM @include ini.
     Menyediakan fungsi global callSound(no, dest, seq) + overlay #audioLock
     (klik-untuk-aktifkan bila autoplay diblokir browser). --}}
<style>
    .alock{position:fixed;inset:0;z-index:99;display:none;
        align-items:center;justify-content:center;cursor:pointer;
        background:rgba(0,27,69,.55);-webkit-backdrop-filter:blur(6px);backdrop-filter:blur(6px);}
    .alock.on{display:flex;}
    .alock .box{background:#fff;border-radius:18px;padding:3.4vh 3vw;text-align:center;
        box-shadow:0 20px 60px rgba(0,27,69,.4);max-width:60vw;}
    .alock .ic{font-size:6vh;line-height:1;}
    .alock .t1{font-size:3vh;font-weight:800;color:#0b2f6b;margin-top:1.4vh;}
    .alock .t2{font-size:2vh;font-weight:600;color:#5c6f92;margin-top:1vh;line-height:1.5;}
</style>

<div class="alock" id="audioLock" onclick="unlockAudio()">
    <div class="box">
        <div class="ic">🔇</div>
        <div class="t1">Klik layar untuk mengaktifkan suara</div>
        <div class="t2">Browser memblokir suara otomatis.<br>Cukup klik sekali - pesan ini akan hilang.</div>
    </div>
</div>

<script>
    // "FD005" → "F D 0 0 5" agar TTS mengeja jelas.
    // "FD005" → "ef de nol nol lima" - angka & huruf dieja memakai lafal
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
        if(!s || s==='-') return '';
        return /^\d+$/.test(s) ? s : spellNumber(s);
    }
    /*
     * Pilih voice PEREMPUAN berbahasa Indonesia.
     *
     * PENTING: suara Indonesia hanya terdengar alami bila perangkat layar
     * punya voice id-ID terpasang. Windows biasanya HANYA punya voice
     * Inggris (David/Zira/Mark) - tanpa voice Indonesia, teks tetap dibaca
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
    // Chime "ding-dong" ala bandara sebelum pengumuman suara.
    function ding(){try{var ctx=ding._c||(ding._c=new(window.AudioContext||window.webkitAudioContext)());
        if(ctx.state==='suspended'){ctx.resume();}
        [880,1174].forEach(function(f,i){var o=ctx.createOscillator(),g=ctx.createGain();o.type='sine';o.frequency.value=f;
        o.connect(g);g.connect(ctx.destination);var t=ctx.currentTime+i*0.18;g.gain.setValueAtTime(0.0001,t);
        g.gain.exponentialRampToValueAtTime(0.25,t+0.02);g.gain.exponentialRampToValueAtTime(0.0001,t+0.35);o.start(t);o.stop(t+0.36);});}catch(e){}}

    /*
     * BEL PEMBUKA & PENUTUP - dari public/bel.mp3 (sama seperti Client
     * Display, display/client.blade.php). Berkas berisi dua blok bunyi:
     *   blok 1 (0    – 4,0 dtk)  → bel PEMBUKA, sebelum pengumuman
     *   blok 2 (5,45 dtk – habis)→ bel PENUTUP, sesudah pengumuman
     */
    var BEL_URL   = "{{ asset('bel.mp3') }}";
    var BEL_OPEN  = { start: 0,    end: 4.0  };
    var BEL_CLOSE = { start: 5.45, end: null };
    var bell = new Audio(BEL_URL);
    bell.preload = 'auto';
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
            if(seg.end != null){ timer = setTimeout(stop, (seg.end - seg.start) * 1000); }
            bell.play().catch(function(){ stop(); });
        }catch(e){ stop(); }
    }
    /*
     * Pengumuman suara.
     *
     * UTAMA: audio dirender di SERVER (Piper TTS, Bahasa Indonesia) lalu
     * diputar sebagai <audio>. Dengan begitu semua perangkat - Windows,
     * Android, TV box - berbunyi sama persis tanpa perlu memasang voice.
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
    function announce(no, dest, seq, onDone){
        var qs = 'no='+encodeURIComponent(no||'')+
                 '&dest='+encodeURIComponent(dest||'')+
                 '&area='+encodeURIComponent(AREA);
        var finish = onDone || function(){};

        // MODE PUSAT: cukup daftarkan ke antrean; speaker pusat yang
        // memutar. Layar ini tidak mengeluarkan suara sama sekali.
        if(SOUND_MODE === 'central'){
            fetch(ENQUEUE_URL+'?'+qs+'&seq='+encodeURIComponent(seq||0),
                  {headers:{'X-Requested-With':'XMLHttpRequest'}}).catch(function(){}).finally(finish);
            return;
        }

        // MODE LOKAL: layar ini yang berbunyi.
        fetch(SPEECH_URL+'?'+qs, {headers:{'X-Requested-With':'XMLHttpRequest'}})
            .then(function(r){ return r.json(); })
            .then(function(j){
                if(j && j.url){ playTwice(j.url, j.url_short || j.url, finish); }
                else { announceBrowser(no, dest).then(finish); }
            })
            .catch(function(){ announceBrowser(no, dest).then(finish); });
    }
    // Pengumuman diputar 2x ala bandara: versi LENGKAP dulu, lalu versi
    // RINGKAS sebagai pengulangan, dipisah jeda ~1,2 detik. onDone dipanggil
    // setelah pengulangan KEDUA selesai (dipakai utk memicu bel penutup).
    function playTwice(url, urlShort, onDone){
        var finish = onDone || function(){};
        try{
            player.pause();
            player.src = url;
            player.currentTime = 0;
            var second = function(){
                player.removeEventListener('ended', second);
                setTimeout(function(){
                    try{
                        // Elemen audio BARU (bukan reuse `player`) - urlShort biasanya
                        // SAMA persis dgn url (server belum kirim versi ringkas
                        // terpisah), dan reuse elemen yg sama utk src identik bisa
                        // bikin browser tak reset status "ended"-nya dgn benar,
                        // sehingga event 'ended' terpicu prematur (bel penutup
                        // nyelonong di tengah putaran kedua).
                        var p2 = new Audio(urlShort);
                        p2.addEventListener('ended', finish, {once:true});
                        p2.play().catch(finish);
                    }catch(e){ finish(); }
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
                finish();
            });
        }catch(e){ finish(); }
    }
    /*
     * SUARA BROWSER (SpeechSynthesisUtterance) - meniru program lama
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
            if(dest && String(dest).trim() && String(dest).trim() !== '-'){
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
    /*
     * ANTREAN SUARA LOKAL - BUG YANG DIPERBAIKI: kalau 2 counter memanggil
     * berdekatan, panggilan kedua dulu langsung memicu callSound() lagi
     * walau urutan bel+suara panggilan pertama belum selesai, sehingga
     * dua-duanya berbunyi bertumpuk. Sekarang diantre (mirip sayQueue di
     * display/client.blade.php) - panggilan baru menunggu urutan yang
     * sedang jalan benar-benar tuntas (sampai bel penutup) baru diputar.
     */
    var soundBusy = false;
    var soundQueue = [];
    function callSound(no, dest, seq){
        // Mode pusat: layar diam total - chime & suara dari speaker pusat,
        // antreannya sudah ditangani server (AnnouncementQueue).
        if(SOUND_MODE === 'central'){ announce(no, dest, seq); return; }
        if(soundBusy){ soundQueue.push({no:no, dest:dest, seq:seq}); return; }
        playSoundNow(no, dest, seq);
    }
    function playSoundNow(no, dest, seq){
        soundBusy = true;

        // Mode pusat sudah ditangani di callSound() (fire-and-forget ke
        // antrean server) - dari sini seterusnya pasti mode LOKAL.
        //
        // BUG: "bel bunyi lalu sunyi lama". Mesin suara default (Edge TTS)
        // butuh INTERNET & mensintesis ulang tiap kalimat BARU (bisa
        // beberapa detik) - sebelumnya fetch ke server baru dimulai
        // SETELAH bel pembuka selesai, jadi jeda sintesis itu terasa
        // sebagai keheningan panjang sebelum suara keluar. Sekarang fetch
        // dimulai BERBARENGAN dengan bel pembuka (bukan menunggunya
        // selesai dulu) - selama sintesisnya tak lebih lama dari durasi
        // bel (~4 detik), waktu tunggunya "tersembunyi" di balik bel.
        var qs = 'no='+encodeURIComponent(no||'')+'&dest='+encodeURIComponent(dest||'')+'&area='+encodeURIComponent(AREA);
        var speechPromise = fetch(SPEECH_URL+'?'+qs, {headers:{'X-Requested-With':'XMLHttpRequest'}})
            .then(function(r){ return r.json(); })
            .catch(function(){ return null; });
        var bellDonePromise = new Promise(function(resolve){ playBell(BEL_OPEN, resolve); });

        // Urutan ala bandara: BEL PEMBUKA (+ fetch suara berbarengan) → pengumuman (2x) → BEL PENUTUP.
        Promise.all([bellDonePromise, speechPromise]).then(function(res){
            var j = res[1];
            setTimeout(function(){
                var afterVoice = function(){
                    setTimeout(function(){
                        playBell(BEL_CLOSE, function(){
                            soundBusy = false;
                            if(soundQueue.length){
                                var next = soundQueue.shift();
                                playSoundNow(next.no, next.dest, next.seq);
                            }
                        });
                    }, 900);
                };
                if(j && j.url){ playTwice(j.url, j.url_short || j.url, afterVoice); }
                else { announceBrowser(no, dest).then(afterVoice); }
            }, 350);
        });
    }

    var audioUnlocked=false;
    function unlockAudio(){
        hideAudioBlocked();
        if(audioUnlocked) return;
        audioUnlocked=true;
        try{ ding._c = ding._c || new (window.AudioContext||window.webkitAudioContext)(); if(ding._c.state==='suspended') ding._c.resume(); }catch(e){}
        try{ if('speechSynthesis' in window){ var w=new SpeechSynthesisUtterance(' '); w.volume=0; speechSynthesis.speak(w); } }catch(e){}
        try{
            bell.muted = true;
            bell.play().then(function(){ bell.pause(); bell.currentTime = 0; bell.muted = false; }).catch(function(){ bell.muted = false; });
        }catch(e){}
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
</script>
