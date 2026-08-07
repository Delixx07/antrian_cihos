
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <link rel="shortcut icon" href="<?php echo base_url('assets/file/logo_only.png')?>"/>
    <title>Display Antrian Ciputra Hospital</title>
    <link rel="stylesheet" href="<?= base_url('assets/css/display-css.css') ?>">

  <style>
    
  </style>
</head>

<body>
  <main class="screen">

    <!-- TOP ROW: Video + Queue List -->
    <section class="top">

      <!-- Video Panel -->
      <article class="panel video-wrap">
        <header class="panel-header">
          <h1 class="title">Promotion</h1>
          <span class="badge" id="clock">--:--:--</span>
        </header>

        <div class="video-body">
          <div class="video-frame">
            <!-- OPSI A: pakai video lokal -->
            <video autoplay muted loop playsinline>
              <!-- ganti src sesuai file video kamu -->
              <source src="<?= base_url('assets/file/PROMO TV POLI 1920x1080 UPDATE 11MEI2026.mp4') ?>" type="video/mp4" />
              Video unavailable.
            </video>

          </div>
        </div>
      </article>

      <!-- Queue Panel -->
      <article class="panel queue-wrap">
        <header class="panel-header">
          <h1 class="title">Queue Registration</h1>
        </header>

        <div class="queue-body">
          <div class="queue-list" id="queueList">
            
          </div>
        </div>

        <footer class="queue-footer">
          <span id="footerInfo"></span>
        </footer>
      </article>

    </section>

    <!-- BOTTOM ROW: Counters 1-3 -->
    <section class="bottom">
      <article class="panel counter">
        <div class="head">
          <h2 class="name">Counter 1</h2>
          <span class="state">Active</span>
        </div>
        <div class="served">
          <div class="label">Queue Calling</div>
          <div class="number" id="c1"></div>
        </div>
      </article>

      <article class="panel counter">
        <div class="head">
          <h2 class="name">Counter 2</h2>
          <span class="state">Active</span>
        </div>
        <div class="served">
          <div class="label">Queue Calling</div>
          <div class="number" id="c2"></div>
        </div>
      </article>

      <article class="panel counter">
        <div class="head">
          <h2 class="name">Counter 3</h2>
          <span class="state">Active</span>
        </div>
        <div class="served">
          <div class="label">Queue Calling</div>
          <div class="number" id="c3">—</div>
        </div>
      </article>
    </section>

  </main>



<script>
let audioUnlocked = true;        // asumsi autoplay-policy sudah allow
let unlockInstalled = false;

let pendingCalls = [];           // queue call saat audio masih blocked

// Chain agar Counter 1 & 2 tidak overlap
let ttsChain = Promise.resolve();
function enqueueTTS(task) {
  ttsChain = ttsChain.then(task).catch(err => console.error('TTS chain error:', err));
  return ttsChain;
}

// ---------- VOICES LOADER ----------
let cachedVoices = [];
let voicesLoadedOnce = false;

function loadVoices() {
  return new Promise((resolve) => {
    const synth = window.speechSynthesis;

    const v = synth.getVoices();
    if (v && v.length) {
      cachedVoices = v;
      voicesLoadedOnce = true;
      return resolve(v);
    }

    synth.onvoiceschanged = () => {
      const v2 = synth.getVoices();
      if (v2 && v2.length) {
        cachedVoices = v2;
        voicesLoadedOnce = true;
      }
      resolve(v2 || []);
    };
  });
}

// panggil sekali di awal supaya voices lebih cepat muncul
loadVoices();

function pickBestVoice(preferredLang = 'id-ID') {
  const voices = cachedVoices || [];
  if (!voices.length) return null;

  const pref = preferredLang.toLowerCase();
  const prefPrefix = pref.split('-')[0];

  // 1) exact match id-ID
  let v = voices.find(x => (x.lang || '').toLowerCase() === pref);
  if (v) return v;

  // 2) prefix match id-*
  v = voices.find(x => (x.lang || '').toLowerCase().startsWith(prefPrefix));
  if (v) return v;

  // 3) fallback english
  v = voices.find(x => (x.lang || '').toLowerCase().startsWith('en'));
  if (v) return v;

  // 4) default
  return voices[0] || null;
}

// ---------- UNLOCK OVERLAY (fallback kalau device strict) ----------
function installUnlockOverlayOnce() {
  if (unlockInstalled) return;
  unlockInstalled = true;

  const ov = document.createElement('div');
  ov.id = 'unlockOverlay';
  ov.style.cssText = `
    position:fixed; inset:0; z-index:99999;
    background:rgba(0,0,0,.70);
    color:#fff; display:flex; align-items:center; justify-content:center;
    font-family:system-ui; font-size:3vw; cursor:pointer;
    text-align:center; padding:24px;
  `;
  ov.textContent = 'Sentuh layar untuk mengaktifkan suara';
  document.body.appendChild(ov);

  const unlock = async () => {
    try {
      // coba "kick" synth
      window.speechSynthesis.cancel();
      const u = new SpeechSynthesisUtterance('Audio aktif');
      u.lang = 'id-ID';
      window.speechSynthesis.speak(u);
    } catch (e) {}

    audioUnlocked = true;
    ov.remove();

    // Jalankan call yang tertahan
    const q = [...pendingCalls];
    pendingCalls = [];
    q.forEach(({ fullNumber, counter }) => enqueueTTS(() => panggilAntrian(fullNumber, counter)));

    document.removeEventListener('click', unlock);
    document.removeEventListener('touchstart', unlock);
    document.removeEventListener('keydown', unlock);

    console.log('🔊 Audio unlocked by gesture');
  };

  document.addEventListener('click', unlock, { once: true });
  document.addEventListener('touchstart', unlock, { once: true });
  document.addEventListener('keydown', unlock, { once: true });
}

// ---------- CORE SPEAK (Promise-based, WITH DELAY) ----------
function sleep(ms) {
  return new Promise(r => setTimeout(r, ms));
}

/**
 * speak(text, delayMs)
 * - Tidak melakukan cancel di sini (biar tidak "interrupted")
 * - Resolve saat selesai atau error
 */
async function speak(text, delayMs = 0) {
  if (delayMs > 0) await sleep(delayMs);

  // pastikan voices kebaca
  if (!voicesLoadedOnce) await loadVoices();

  const synth = window.speechSynthesis;
  const u = new SpeechSynthesisUtterance(String(text));

  const voice = pickBestVoice('id-ID');
  if (voice) {
    u.voice = voice;
    u.lang  = voice.lang || 'id-ID'; // SAMAKAN lang dengan voice
  } else {
    u.lang = 'id-ID';
  }

  u.rate = 0.9;
  u.volume = 1;

  return new Promise((resolve) => {
    u.onend = () => resolve(true);

    u.onerror = (e) => {
      console.error('TTS error:', e.error, 'lang:', u.lang, 'voice:', u.voice?.name);

      // Kalau blocked / not-allowed, pasang overlay unlock
      // (error string berbeda2 tergantung platform, jadi kita cover beberapa)
      if (e.error === 'not-allowed' || e.error === 'audio-busy' || e.error === 'interrupted') {
        // interrupted di sini kita biarkan saja resolve, supaya chain lanjut.
      }
      resolve(false);
    };

    try {
      synth.speak(u);
    } catch (err) {
      console.error('speak() throw:', err);
      resolve(false);
    }
  });
}

// ---------- SPLIT NOMOR (A12 -> "A satu dua") ----------
function splitNomor(fullNumber) {
  const angka = ['nol','satu','dua','tiga','empat','lima','enam','tujuh','delapan','sembilan'];
  let result = [];
  let angkaBuffer = [];

  for (let char of String(fullNumber).toUpperCase()) {
    if (/[A-Z]/.test(char)) {
      if (angkaBuffer.length) {
        result.push({ text: angkaBuffer.join(' '), delay: 0 });
        angkaBuffer = [];
      }
      result.push({ text: char, delay: 120 });
    } else if (/\d/.test(char)) {
      angkaBuffer.push(angka[Number(char)]);
    }
  }

  if (angkaBuffer.length) result.push({ text: angkaBuffer.join(' '), delay: 0 });
  return result;
}

// ---------- MAIN CALL ----------
async function panggilAntrian(fullNumber, counter) {
  // kalau masih blocked, simpan dulu
  if (!audioUnlocked) {
    pendingCalls.push({ fullNumber, counter });
    installUnlockOverlayOnce();
    console.warn('Audio blocked. Call queued.');
    return;
  }

  const synth = window.speechSynthesis;

  // CANCEL SEKALI DI AWAL agar suara lama berhenti, tapi tidak membunuh utterance yang baru chain
  // Ini mencegah overlap. Jangan cancel di dalam speak().
  synth.cancel();

  await speak('Nomor antrian', 80);

  const bagianNomor = splitNomor(fullNumber);
  for (const item of bagianNomor) {
    await speak(item.text, item.delay);
  }

  await speak('ke counter', 120);
  await speak(String(counter), 80);
}

// ---------- PUBLIC HELPER: panggil tanpa overlap ----------
/**
 * Pakai ini dari kode kamu:
 * callAntrian("A12", 1);
 */
window.callAntrian = function(fullNumber, counter) {
  return enqueueTTS(() => panggilAntrian(fullNumber, counter));
};

// (Opsional) debug voices
window.debugVoices = async function() {
  await loadVoices();
  console.log('Total voices:', cachedVoices.length);
  console.table(cachedVoices.map(v => ({ name: v.name, lang: v.lang, local: v.localService })));
};
</script>

<script>
    // Jam realtime (badge kanan atas)
    const clockEl = document.getElementById('clock');
    function tick(){
      const d = new Date();
      const pad = n => String(n).padStart(2,'0');
      clockEl.textContent = `${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`;
    }
    setInterval(tick, 1000); tick();

    // (Opsional) helper untuk render list antrian dari array
    const queueListEl = document.getElementById('queueList');
    const footerInfoEl = document.getElementById('footerInfo');

    function renderQueue(items){
      queueListEl.innerHTML = items.map((it, idx) => `
        <div class="q-item">
          <div class="q-index">${idx+1}</div>
          <div class="q-number">${it.number}</div>
          <div class="q-status">${it.status}</div>
        </div>
      `).join('');
      footerInfoEl.textContent = `Total Waiting: ${items.length}`;
    }
    
    const queueBodyEl = document.querySelector('.queue-body');

    let direction = 1;       // 1 = scroll ke bawah, -1 = scroll ke atas
    const speed = 1;         // px per step
    const intervalTime = 20; // ms per step
    let isHolding = false;   // menahan sementara

    function autoScroll() {
        if (isHolding) return; // jika sedang hold, jangan scroll

        const maxScroll = queueBodyEl.scrollHeight - queueBodyEl.clientHeight;
        queueBodyEl.scrollTop += direction * speed;

        // jika sudah mencapai bawah
        if (queueBodyEl.scrollTop >= maxScroll) {
            isHolding = true;
            setTimeout(() => {
            direction = -1; // balik arah
            isHolding = false;
            }, 1000); // hold 1 detik
        }

        // jika sudah mencapai atas
        if (queueBodyEl.scrollTop <= 0) {
            isHolding = true;
            setTimeout(() => {
            direction = 1; // balik arah
            isHolding = false;
            }, 1000); // hold 1 detik
        }
    }

    // jalankan terus setiap interval
    setInterval(autoScroll, intervalTime);

    // Contoh data (silakan ganti dari API/WebSocket)
    renderQueue([
      
    ]);
</script>

<script>
    let lastDataHash = null;
    const RELOAD_KEY = 'call_reload_done';
    const lastCalledByCounter = {
        1: null,
        2: null,
        3: null
    };

  function sortObjectKeys(obj) {
    if (Array.isArray(obj)) return obj.map(sortObjectKeys);
    if (obj && typeof obj === "object") {
      return Object.keys(obj).sort().reduce((acc, key) => {
        acc[key] = sortObjectKeys(obj[key]);
        return acc;
      }, {});
    }
    return obj;
  }

  function stableHashData(data) {
    const normalized = sortObjectKeys(data);
    if (Array.isArray(data)) {
        normalized = [...data].sort((a, b) => Number(b.id) - Number(a.id));
    }
    return JSON.stringify(normalized);
  }

  function toQueueItemsOpen(data) {
    const arr = Array.isArray(data) ? data : (data.items ?? data.data ?? []);
    return [...arr]
      .filter(it =>
        (it.status ?? '').toString().toLowerCase() === 'open' &&
        (it.queueType ?? '').toString().toLowerCase() === 'walk-in'
      )
      .map(it => ({
        number: it.fullNumber ?? it.no_antrian ?? it.queue_no ?? '-',
        status: 'Waiting'
      }));
  }

  function buildUiSnapshot(data) {
    const arr = Array.isArray(data) ? data : (data.items ?? data.data ?? []);

    const open = arr
      .filter(it =>
        String(it.status).toLowerCase() === 'open' &&
        String(it.queueType).toLowerCase() === 'walk-in'
      )
      .map(it => ({
        id: Number(it.id) || 0,
        fullNumber: it.fullNumber ?? it.no_antrian ?? it.queue_no ?? '-'
      }))
      .sort((a, b) => b.id - a.id);

    const call = arr
      .filter(it => String(it.status).toLowerCase() === 'call')
      .map(it => ({
        id: Number(it.id) || 0,
        callCounter: Number(it.callCounter) || 0,
        fullNumber: it.fullNumber ?? it.no_antrian ?? it.queue_no ?? '-'
      }))
      .sort((a, b) => b.id - a.id);

    return { open, call };
  }

  function hasCallStatus(data) {
    const arr = Array.isArray(data) ? data : (data.items ?? data.data ?? []);
    return arr.some(it => String(it.status).toLowerCase() === 'call');
  }

  function updateCountersFromData(data) {
    const arr = Array.isArray(data) ? data : (data.items ?? data.data ?? []);
    const map = {};

    for (const it of arr) {
      if (String(it.status).toLowerCase() !== 'call') continue;

      const counter = Number(it.callCounter);
      if (!counter || map[counter]) continue;

      map[counter] =
        it.fullNumber ??
        it.no_antrian ??
        it.queue_no ??
        it.number ??
        '—';
    }

    [1, 2, 3].forEach(n => {
      const el = document.getElementById(`c${n}`);
      if (!el) return;

      const newNumber = map[n] ?? '—';
      const oldNumber = el.textContent.trim();

      if (newNumber !== oldNumber) {
        el.textContent = newNumber;
      }

      if (
        newNumber !== '—' &&
        lastCalledByCounter[n] !== newNumber
      ) {
        lastCalledByCounter[n] = newNumber;
        panggilAntrian(newNumber, `${n}`);
      }
    });
  }

  async function checkCarouselUpdate() {
    try {
      const response = await fetch("http://localhost/ApiAntrian/Registration/getQueueToday", { cache: "no-store" });
      if (!response.ok) return;

      const data = await response.json();

    /* ---------- RELOAD LOGIC (TRANSISI CALL) ---------- */
    const hasCallNow = hasCallStatus(data);
    const reloadDone = sessionStorage.getItem(RELOAD_KEY) === '1';

    if (hasCallNow && !reloadDone) {
        console.log('🔄 Reload 1x karena status CALL');

        sessionStorage.setItem(RELOAD_KEY, '1');

        setTimeout(() => {
            location.reload();
        }, 800);
    }

    if (!hasCallNow) {
      sessionStorage.removeItem(RELOAD_KEY);
    }

    /* ---------- UI UPDATE ---------- */
    const snapshot = buildUiSnapshot(data);
    const currentHash = stableHashData(snapshot);

    if (lastDataHash === null || currentHash !== lastDataHash) {
      lastDataHash = currentHash;

      renderQueue(toQueueItemsOpen(data));
      updateCountersFromData(data);

      console.log("✅ UI di-update karena data berubah");
    }

  } catch (err) {
    console.error("❌ API error:", err);
  }
}

/* ============================================================
   START POLLING
============================================================ */
setInterval(checkCarouselUpdate, 5000);
checkCarouselUpdate();
</script>

</body>
</html>
