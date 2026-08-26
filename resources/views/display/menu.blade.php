<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ciputra Hospital - Display Selection</title>
<link rel="icon" href="{{ asset('cihoslogo.png') }}" type="image/png">
<style>
    :root{
        --primary:#003f87; --primary-container:#0056b3; --on-primary:#fff;
        --on-primary-fixed-variant:#c7d8f7; --on-surface:#191c1e; --on-surface-variant:#424752;
        --outline-variant:#c2c6d4; --surface:#f7f9fb; --tertiary-container:#006645; --on-tertiary:#fff;
    }
    *{box-sizing:border-box;margin:0;padding:0;}
    body{background:var(--surface);color:var(--on-surface);font-family:'Segoe UI',Tahoma,system-ui,sans-serif;
        -webkit-font-smoothing:antialiased;min-height:100vh;display:flex;flex-direction:column;}
    img{max-width:100%;display:block;}
    button{font:inherit;}

    /* ---------- HEADER ---------- */
    .hd{position:fixed;top:0;left:0;width:100%;z-index:50;display:flex;align-items:center;justify-content:space-between;
        padding:0 24px;height:64px;background:var(--primary);color:var(--on-primary);
        box-shadow:0 4px 6px -1px rgba(0,0,0,.1),0 2px 4px -2px rgba(0,0,0,.1);}
    .hd-l{display:flex;align-items:center;gap:16px;}
    .hd-l img{height:36px;width:auto;object-fit:contain;filter:brightness(0) invert(1);}
    .hd-r{display:flex;align-items:center;gap:24px;}
    .clock{text-align:right;line-height:1.25;}
    .clock .dt{font-size:.875rem;color:var(--on-primary-fixed-variant);}
    .clock .tm{font-size:1.25rem;font-weight:600;letter-spacing:-.01em;}
    .fs-btn{padding:8px;border-radius:999px;background:transparent;border:none;color:inherit;cursor:pointer;
        display:flex;transition:background .15s;}
    .fs-btn:hover{background:rgba(255,255,255,.18);}

    /* ---------- MAIN ---------- */
    .main{flex:1;padding:96px 24px 24px;max-width:1280px;margin:0 auto;width:100%;}
    @media (min-width:768px){.main{padding-left:32px;padding-right:32px;}}
    .hero{text-align:center;margin-bottom:32px;}
    .hero h1{font-size:1.875rem;font-weight:600;letter-spacing:-.01em;}
    .hero .accent{color:var(--primary-container);}
    .sec{margin-bottom:32px;}
    .sec h2{font-size:1.25rem;font-weight:600;color:var(--primary);margin-bottom:16px;}
    .grid{display:grid;grid-template-columns:1fr;gap:16px;}
    @media (min-width:768px){.grid{grid-template-columns:repeat(2,1fr);}}
    @media (min-width:1024px){.grid{grid-template-columns:repeat(3,1fr);}}

    /* ---------- CARD ---------- */
    .card{background:rgba(255,255,255,.7);backdrop-filter:blur(10px);border:1px solid rgba(255,255,255,.5);
        box-shadow:0 4px 6px -1px rgba(0,0,0,.05),0 2px 4px -1px rgba(0,0,0,.03);
        border-radius:12px;padding:16px;display:flex;align-items:center;justify-content:space-between;width:100%;
        text-decoration:none;color:inherit;font:inherit;text-align:left;cursor:pointer;
        transition:transform .2s ease,box-shadow .2s ease,border-color .2s ease;}
    .card:hover{transform:translateY(-2px);box-shadow:0 10px 15px -3px rgba(0,0,0,.05),0 4px 6px -2px rgba(0,0,0,.025);
        border-color:rgba(0,86,179,.3);}
    .card-l{display:flex;align-items:center;gap:16px;min-width:0;}
    .card-l.top{align-items:flex-start;}
    .icon-box{width:48px;height:48px;border-radius:8px;display:flex;align-items:center;justify-content:center;
        flex-shrink:0;transition:background .2s,color .2s;}
    .icon-box svg{width:24px;height:24px;}
    .icon-box.sm{width:40px;height:40px;margin-top:4px;}
    .icon-box.sm svg{width:20px;height:20px;}
    .icon-primary{background:#F0F5FF;color:#0056B3;}
    .card:hover .icon-primary{background:var(--primary-container);color:var(--on-primary);}
    .icon-tertiary{background:#E6F8EF;color:#006645;}
    .card:hover .icon-tertiary{background:var(--tertiary-container);color:var(--on-tertiary);}
    .card-label{font-size:1rem;font-weight:500;}
    .zone-t{font-size:.875rem;font-weight:500;}
    .zone-d{font-size:.875rem;color:var(--on-surface-variant);margin-top:4px;}
    .chev{color:var(--outline-variant);transition:color .2s;flex-shrink:0;margin-top:0;}
    .chev.top{margin-top:8px;}
    .card:hover .chev{color:var(--primary-container);}
    .card.zone:hover .chev{color:var(--tertiary-container);}

    /* ---------- MODAL ---------- */
    .ov{position:fixed;inset:0;z-index:100;display:none;align-items:center;justify-content:center;padding:24px;
        background:rgba(8,20,45,.55);backdrop-filter:blur(6px);}
    .ov.show{display:flex;}
    .modal{width:100%;max-width:32rem;border-radius:16px;overflow:hidden;background:#fff;
        box-shadow:0 25px 50px -12px rgba(0,0,0,.25);}
    .modal-hd{display:flex;align-items:center;gap:12px;padding:20px 24px;background:var(--primary);color:var(--on-primary);}
    .modal-ico{width:40px;height:40px;border-radius:12px;display:flex;align-items:center;justify-content:center;
        flex-shrink:0;background:rgba(255,255,255,.16);}
    .modal-ico svg{width:20px;height:20px;}
    .modal-hd h3{font-size:1.125rem;font-weight:600;}
    .modal-hd .sub{font-size:.875rem;color:var(--on-primary-fixed-variant);}
    .modal-close{margin-left:auto;width:36px;height:36px;border-radius:8px;font-size:1.25rem;line-height:1;
        background:rgba(255,255,255,.12);color:#fff;border:none;cursor:pointer;transition:background .15s;flex-shrink:0;}
    .modal-close:hover{background:rgba(239,68,68,.8);}
    .modal-body{padding:24px;display:flex;flex-direction:column;gap:12px;}
    .pbtn{display:flex;align-items:center;gap:12px;border-radius:12px;padding:12px 16px;font-weight:600;
        text-decoration:none;color:inherit;transition:transform .15s;}
    .pbtn:hover{transform:translateX(4px);}
    .pbtn.main{background:var(--primary);color:var(--on-primary);}
    .pbtn.alt{background:#f1f6fe;color:var(--primary-container);border:1px solid #dce8fa;}
    .pbtn.alt:hover{background:#e3eefe;}
    .pbtn .ico{display:flex;flex-shrink:0;}
    .pbtn .ico svg{width:20px;height:20px;}
    .pbtn .meta{margin-left:auto;font-size:.75rem;font-weight:500;opacity:.7;}
</style>
</head>
<body>

@php
    // Set ikon (SVG minimalis, stroke, mandiri - tak bergantung font ikon dari
    // CDN) untuk Main Display, kartu Zona, dan tombol di modal pilih display.
    // Dipakai di server (foreach di bawah) DAN di klien (di-expose ke JS lewat
    // @json() untuk pbtn() saat modal zona dibangun secara dinamis).
    $icons = [
        'calendar_today'  => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
        'local_hospital'  => '<circle cx="12" cy="12" r="10"/><path d="M12 8v8M8 12h8"/>',
        'medical_services'=> '<path d="M22 12h-4l-3 9L9 3l-3 9H2"/>',
        'credit_card'     => '<rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/>',
        'receipt_long'    => '<path d="M6 3h12a1 1 0 0 1 1 1v16l-3-2-3 2-3-2-3 2-3-2V4a1 1 0 0 1 1-1z"/><path d="M9 8h6M9 12h6"/>',
        'medication'      => '<rect x="3" y="8" width="18" height="8" rx="4" transform="rotate(45 12 12)"/><path d="M8.5 15.5l7-7"/>',
        'how_to_reg'      => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M17 11l2 2 4-4"/>',
        'location_on'     => '<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>',
        'tv'              => '<rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/>',
        'meeting_room'    => '<rect x="5" y="2" width="14" height="20" rx="1"/><circle cx="15" cy="12" r="1"/>',
        'chevron_right'   => '<path d="M9 18l6-6-6-6"/>',
        'fullscreen'      => '<path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"/>',
    ];
    $svgOpen = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">';
@endphp

<header class="hd">
    <div class="hd-l">
        <img alt="Ciputra Hospital" src="{{ asset('cihoslogo.png') }}" onerror="this.style.display='none'"/>
    </div>
    <div class="hd-r">
        <div class="clock">
            <div class="dt" id="current-date">-</div>
            <div class="tm" id="current-time">--:--:--</div>
        </div>
        <button onclick="toggleFS()" class="fs-btn" title="Fullscreen">
            {!! $svgOpen.$icons['fullscreen'].'</svg>' !!}
        </button>
    </div>
</header>

<main class="main">
    <div class="hero">
        <h1>Silahkan Pilih <span class="accent">Display</span></h1>
    </div>

    {{-- Main Display --}}
    <section class="sec">
        <h2>Main Display</h2>
        <div class="grid">
            @foreach ($mainAreas as $a)
                @php $url = route('display.show', $a['params']['area']).'?'.http_build_query(collect($a['params'])->except('area')->all()); @endphp
                <a href="{{ $url }}" class="card">
                    <div class="card-l">
                        <div class="icon-box icon-primary">
                            {!! $svgOpen.($icons[$a['icon']] ?? '').'</svg>' !!}
                        </div>
                        <span class="card-label">{{ $a['label'] }}</span>
                    </div>
                    <span class="chev">{!! $svgOpen.$icons['chevron_right'].'</svg>' !!}</span>
                </a>
            @endforeach
        </div>
    </section>

    {{-- Zona Klinik --}}
    @if (!empty($zoneData))
    <section class="sec">
        <h2>Zona Klinik</h2>
        <div class="grid">
            @foreach ($zoneData as $zone => $d)
                <button onclick="openZone('{{ $zone }}')" class="card zone">
                    <div class="card-l top">
                        <div class="icon-box sm icon-tertiary">
                            {!! $svgOpen.$icons['location_on'].'</svg>' !!}
                        </div>
                        <div>
                            <div class="zone-t">Zona {{ $zone }}</div>
                            <div class="zone-d">{{ $d['name'] }}</div>
                        </div>
                    </div>
                    <span class="chev top">{!! $svgOpen.$icons['chevron_right'].'</svg>' !!}</span>
                </button>
            @endforeach
        </div>
    </section>
    @endif
</main>

{{-- Popup pilih display per zona --}}
<div id="ov" class="ov" onclick="if(event.target===this)closeZone()">
    <div class="modal">
        <div class="modal-hd">
            <span class="modal-ico">{!! $svgOpen.$icons['location_on'].'</svg>' !!}</span>
            <div><h3 id="popTitle">Pilih Display</h3><div id="popSub" class="sub">-</div></div>
            <button onclick="closeZone()" class="modal-close">×</button>
        </div>
        <div id="popBody" class="modal-body"></div>
    </div>
</div>

<script>
    var ZONES  = @json($zoneData, JSON_UNESCAPED_UNICODE);
    var ICONS  = @json($icons, JSON_UNESCAPED_UNICODE);
    var MAIN_URL   = @json(route('display.show', 'klinik'));   // + ?floor=zona
    var CLIENT_URL = @json(url('display/client'));             // + /{room}
    var SVG_OPEN = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">';

    function ico(name){ return SVG_OPEN+(ICONS[name]||'')+'</svg>'; }

    function pbtn(href, icon, label, meta, isMain){
        var cls = 'pbtn '+(isMain ? 'main' : 'alt');
        return '<a href="'+href+'" class="'+cls+'">'
            + '<span class="ico">'+ico(icon)+'</span>'+label
            + (meta?'<span class="meta">'+meta+'</span>':'') + '</a>';
    }
    function openZone(z){
        var d = ZONES[z]; if(!d) return;
        document.getElementById('popTitle').textContent = 'Zona '+z;
        document.getElementById('popSub').textContent = d.name;
        var html = pbtn(MAIN_URL+'?floor='+z, 'tv', 'Main Display', 'Semua ruang', true);
        d.pairs.forEach(function(pair){
            var label = pair.length>1 ? (pair[0]+' – '+pair[1]) : pair[0];
            var room  = pair.join(',');
            html += pbtn(CLIENT_URL+'/'+encodeURIComponent(room), 'meeting_room', 'Client Display '+label, '', false);
        });
        document.getElementById('popBody').innerHTML = html;
        document.getElementById('ov').classList.add('show');
    }
    function closeZone(){ document.getElementById('ov').classList.remove('show'); }
    document.addEventListener('keydown',function(e){ if(e.key==='Escape') closeZone(); });

    var DAYS=['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
    var MONTHS=['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    function tick(){var d=new Date(),p=n=>String(n).padStart(2,'0');
        document.getElementById('current-time').textContent=p(d.getHours())+':'+p(d.getMinutes())+':'+p(d.getSeconds());
        document.getElementById('current-date').textContent=DAYS[d.getDay()]+', '+d.getDate()+' '+MONTHS[d.getMonth()]+' '+d.getFullYear();}
    setInterval(tick,1000);tick();
    function toggleFS(){ if(!document.fullscreenElement){document.documentElement.requestFullscreen();}else{document.exitFullscreen();} }
</script>
</body>
</html>
