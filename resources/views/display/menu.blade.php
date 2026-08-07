<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ciputra Hospital - Display Selection</title>
<link rel="icon" href="{{ asset('cihoslogo.png') }}" type="image/png">
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<script>
    tailwind.config = { theme: { extend: {
        colors: {
            "primary":"#003f87","primary-container":"#0056b3","on-primary":"#ffffff",
            "on-primary-fixed-variant":"#c7d8f7","on-surface":"#191c1e","on-surface-variant":"#424752",
            "outline-variant":"#c2c6d4","surface":"#f7f9fb","tertiary-container":"#006645","on-tertiary":"#ffffff",
        },
        fontFamily: { sans: ["Inter","system-ui","sans-serif"] },
        spacing: { md:"16px", sm:"8px", lg:"24px", xl:"32px", xs:"4px", gutter:"16px", "container-margin":"24px" },
    }}}
</script>
<style>
    body { background-color:#F8FAFC; font-family:"Inter",system-ui,sans-serif; }
    .glass-card { background:rgba(255,255,255,0.7); backdrop-filter:blur(10px); border:1px solid rgba(255,255,255,0.5);
        box-shadow:0 4px 6px -1px rgba(0,0,0,.05), 0 2px 4px -1px rgba(0,0,0,.03); }
    .hover-lift { transition:transform .2s ease, box-shadow .2s ease, border-color .2s ease; }
    .hover-lift:hover { transform:translateY(-2px); box-shadow:0 10px 15px -3px rgba(0,0,0,.05), 0 4px 6px -2px rgba(0,0,0,.025);
        border-color:rgba(0,86,179,.3); }
    .icon-box-primary  { background-color:#F0F5FF; color:#0056B3; }
    .icon-box-tertiary { background-color:#E6F8EF; color:#006645; }
    .material-symbols-outlined { font-family:"Material Symbols Outlined"; font-weight:normal; font-style:normal; line-height:1;
        letter-spacing:normal; text-transform:none; display:inline-block; white-space:nowrap; direction:ltr; }
</style>
</head>
<body class="text-on-surface antialiased min-h-screen flex flex-col">

<header class="fixed top-0 left-0 w-full z-50 flex justify-between items-center px-container-margin h-16 bg-primary text-on-primary shadow-md">
    <div class="flex items-center gap-md">
        <img alt="Ciputra Hospital" class="h-9 object-contain" src="{{ asset('cihoslogo.png') }}" style="filter:brightness(0) invert(1);" onerror="this.style.display='none'"/>
    </div>
    <div class="flex items-center gap-lg">
        <div class="text-right leading-tight">
            <div class="text-sm text-on-primary-fixed-variant" id="current-date">—</div>
            <div class="text-xl font-semibold tracking-tight" id="current-time">--:--:--</div>
        </div>
        <button onclick="toggleFS()" class="p-2 rounded-full hover:bg-primary-container/40 transition-colors focus:outline-none">
            <span class="material-symbols-outlined">fullscreen</span>
        </button>
    </div>
</header>

<main class="flex-grow pt-24 pb-lg px-container-margin md:px-xl max-w-7xl mx-auto w-full">
    <div class="text-center mb-xl">
        <h1 class="text-3xl font-semibold tracking-tight text-on-surface">Silahkan Pilih <span class="text-primary-container">Display</span></h1>
    </div>

    {{-- Main Display --}}
    <section class="mb-xl">
        <h2 class="text-xl font-semibold text-primary mb-md">Main Display</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-gutter">
            @foreach ($mainAreas as $a)
                @php $url = route('display.show', $a['params']['area']).'?'.http_build_query(collect($a['params'])->except('area')->all()); @endphp
                <a href="{{ $url }}" class="glass-card rounded-xl p-md flex items-center justify-between w-full hover-lift group no-underline">
                    <div class="flex items-center gap-md">
                        <div class="w-12 h-12 rounded-lg flex items-center justify-center icon-box-primary group-hover:bg-primary-container group-hover:text-on-primary transition-colors">
                            <span class="material-symbols-outlined">{{ $a['icon'] }}</span>
                        </div>
                        <span class="text-base font-medium text-on-surface">{{ $a['label'] }}</span>
                    </div>
                    <span class="material-symbols-outlined text-outline-variant group-hover:text-primary transition-colors">chevron_right</span>
                </a>
            @endforeach
        </div>
    </section>

    {{-- Zona Klinik --}}
    @if (!empty($zoneData))
    <section>
        <h2 class="text-xl font-semibold text-primary mb-md">Zona Klinik</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-gutter">
            @foreach ($zoneData as $zone => $d)
                <button onclick="openZone('{{ $zone }}')" class="glass-card rounded-xl p-md flex items-start justify-between w-full hover-lift group text-left">
                    <div class="flex items-start gap-md">
                        <div class="w-10 h-10 rounded-lg flex items-center justify-center icon-box-tertiary group-hover:bg-tertiary-container group-hover:text-on-tertiary transition-colors shrink-0 mt-1">
                            <span class="material-symbols-outlined text-[20px]">location_on</span>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-on-surface">Zona {{ $zone }}</div>
                            <div class="text-sm text-on-surface-variant mt-xs">{{ $d['name'] }}</div>
                        </div>
                    </div>
                    <span class="material-symbols-outlined text-outline-variant group-hover:text-tertiary-container transition-colors mt-2">chevron_right</span>
                </button>
            @endforeach
        </div>
    </section>
    @endif
</main>

{{-- Popup pilih display per zona --}}
<div id="ov" class="fixed inset-0 z-[100] hidden items-center justify-center p-6" style="background:rgba(8,20,45,.55);backdrop-filter:blur(6px);" onclick="if(event.target===this)closeZone()">
    <div class="w-full max-w-lg rounded-2xl overflow-hidden bg-white shadow-2xl">
        <div class="flex items-center gap-3 px-6 py-5 bg-primary text-on-primary">
            <span class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0" style="background:rgba(255,255,255,.16);"><span class="material-symbols-outlined">location_on</span></span>
            <div><h3 id="popTitle" class="text-lg font-semibold">Pilih Display</h3><div id="popSub" class="text-sm text-on-primary-fixed-variant">—</div></div>
            <button onclick="closeZone()" class="ml-auto w-9 h-9 rounded-lg text-xl leading-none hover:bg-red-500/80 transition-colors" style="background:rgba(255,255,255,.12);">×</button>
        </div>
        <div id="popBody" class="p-6 flex flex-col gap-3"></div>
    </div>
</div>

<script>
    var ZONES = @json($zoneData, JSON_UNESCAPED_UNICODE);
    var MAIN_URL = @json(route('display.show', 'klinik'));      // + ?floor=zona
    var CLIENT_URL = @json(url('display/client'));               // + /{room}

    function pbtn(href, icon, label, meta, isMain){
        var base = isMain ? 'bg-primary text-on-primary'
            : 'bg-[#f1f6fe] text-primary border border-[#dce8fa] hover:bg-[#e3eefe]';
        return '<a href="'+href+'" class="flex items-center gap-3 rounded-xl px-4 py-3 font-semibold no-underline transition-transform hover:translate-x-1 '+base+'">'
            + '<span class="material-symbols-outlined">'+icon+'</span>'+label
            + (meta?'<span class="ml-auto text-xs font-medium opacity-70">'+meta+'</span>':'') + '</a>';
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
        var ov=document.getElementById('ov'); ov.classList.remove('hidden'); ov.classList.add('flex');
    }
    function closeZone(){ var ov=document.getElementById('ov'); ov.classList.add('hidden'); ov.classList.remove('flex'); }
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
