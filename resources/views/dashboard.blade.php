@extends('layouts.app')
@section('title', 'Beranda')

@push('head')
<style>
    /* Kartu antrian */
    .qgrid{display:grid;grid-template-columns:repeat(4,1fr);gap:1.2rem;margin-bottom:1.4rem;}
    .qcard{background:#fff;border:1px solid var(--line);border-radius:14px;padding:1.3rem 1.4rem;
        display:flex;align-items:center;justify-content:space-between;}
    .qcard .lbl{font-size:.72rem;font-weight:700;letter-spacing:.05em;text-transform:uppercase;color:var(--muted);}
    .qcard .val{font-size:1.6rem;font-weight:800;color:var(--ink);margin-top:.4rem;}
    .qcard .ic{width:48px;height:48px;border-radius:12px;display:grid;place-items:center;}
    @media (max-width:1100px){.qgrid{grid-template-columns:repeat(2,1fr);}}
    @media (max-width:560px){.qgrid{grid-template-columns:1fr;}}

    /* Baris bawah: grafik + list dokter */
    .row2{display:grid;grid-template-columns:1.6fr 1fr;gap:1.4rem;}
    @media (max-width:1000px){.row2{grid-template-columns:1fr;}}
    .panel{background:#fff;border:1px solid var(--line);border-radius:14px;padding:1.3rem 1.4rem;}
    .panel h3{font-size:1rem;font-weight:700;color:var(--ink);margin-bottom:1rem;}

    /* Chart SVG */
    .chart{width:100%;height:280px;}
    .chart .grid-line{stroke:#eef0f4;stroke-width:1;}
    .chart .area{fill:url(#areaGrad);}
    .chart .line{fill:none;stroke:#3b82f6;stroke-width:2.5;stroke-linejoin:round;stroke-linecap:round;}
    .chart .dot{fill:#3b82f6;}
    .chart .lbl-x{fill:#9aa3b5;font-size:10px;}
    .chart .lbl-y{fill:#c3cad6;font-size:10px;}

    /* List dokter */
    .doc-search{position:relative;margin-bottom:.8rem;}
    .doc-search svg{position:absolute;left:.7rem;top:50%;transform:translateY(-50%);color:var(--muted);width:15px;height:15px;}
    .doc-search input{width:100%;padding:.55rem .8rem .55rem 2.1rem;border:1px solid var(--line);border-radius:9px;
        background:#f8fafc;font-size:.85rem;outline:none;}
    .doc-search input:focus{border-color:var(--brand);background:#fff;}
    .doc-list{max-height:300px;overflow-y:auto;}
    .doc-item{display:flex;align-items:center;gap:.7rem;padding:.6rem .2rem;border-bottom:1px solid var(--line);}
    .doc-item:last-child{border-bottom:none;}
    .doc-item .av{width:34px;height:34px;border-radius:50%;flex-shrink:0;
        background:linear-gradient(135deg,#93c5fd,#3b82f6);color:#fff;display:grid;place-items:center;font-size:.72rem;font-weight:700;}
    .doc-item .nm{font-size:.85rem;color:var(--ink);font-weight:500;}
    .doc-empty{color:var(--muted);font-size:.85rem;padding:1rem .2rem;}
</style>
@endpush

@section('content')
    <h1 class="page-title">Beranda</h1>

    {{-- 4 kartu antrian --}}
    <div class="qgrid">
        @php
            $qi = [
                'user'=>'<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>',
                'activity'=>'<path d="M22 12h-4l-3 9L9 3l-3 9H2"/>',
                'chart'=>'<path d="M3 3v18h18"/><rect x="7" y="12" width="3" height="6"/><rect x="12" y="8" width="3" height="10"/><rect x="17" y="5" width="3" height="13"/>',
                'pill'=>'<path d="M10.5 20.5 3.5 13.5a5 5 0 0 1 7-7l7 7a5 5 0 0 1-7 7z"/><path d="m8.5 8.5 7 7"/>',
            ];
        @endphp
        @foreach ($queues as $q)
            <div class="qcard">
                <div>
                    <div class="lbl">{{ $q['label'] }}</div>
                    <div class="val">{{ $q['value'] }} <span style="font-size:.9rem;font-weight:600;color:var(--muted);">Antrian</span></div>
                </div>
                <div class="ic" style="background:{{ $q['bg'] }};color:{{ $q['c'] }};">
                    <svg width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">{!! $qi[$q['ic']] !!}</svg>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Grafik + list dokter --}}
    <div class="row2">
        {{-- Grafik pengunjung (SVG, tanpa library) --}}
        <div class="panel">
            <h3>Jumlah Pengunjung</h3>
            @php
                $vals = array_map(fn($v)=>(int)$v['value'], $visitors);
                $max  = max(max($vals), 10); // hindari bagi 0
                $w = 640; $h = 240; $pad = 28;
                $n = count($visitors);
                $stepX = $n > 1 ? ($w - $pad*2) / ($n - 1) : 0;
                $pts = [];
                foreach ($visitors as $i => $v) {
                    $x = $pad + $i * $stepX;
                    $y = $h - $pad - ($v['value'] / $max) * ($h - $pad*2);
                    $pts[] = [round($x,1), round($y,1)];
                }
                $line = implode(' ', array_map(fn($p)=>$p[0].','.$p[1], $pts));
                $area = "{$pad}," . ($h-$pad) . " " . $line . " " . ($w-$pad) . "," . ($h-$pad);
            @endphp
            <svg class="chart" viewBox="0 0 {{ $w }} {{ $h+26 }}" preserveAspectRatio="none">
                <defs>
                    <linearGradient id="areaGrad" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="#3b82f6" stop-opacity=".28"/>
                        <stop offset="100%" stop-color="#3b82f6" stop-opacity="0"/>
                    </linearGradient>
                </defs>
                {{-- grid + label Y --}}
                @for ($g = 0; $g <= 4; $g++)
                    @php $gy = $pad + $g * (($h-$pad*2)/4); $gv = round($max - $g*($max/4)); @endphp
                    <line class="grid-line" x1="{{ $pad }}" y1="{{ $gy }}" x2="{{ $w-$pad }}" y2="{{ $gy }}"/>
                    <text class="lbl-y" x="2" y="{{ $gy+3 }}">{{ $gv }}</text>
                @endfor
                <polygon class="area" points="{{ $area }}"/>
                <polyline class="line" points="{{ $line }}"/>
                @foreach ($pts as $i => $p)
                    <circle class="dot" cx="{{ $p[0] }}" cy="{{ $p[1] }}" r="2.5"/>
                    @if ($i % 2 === 0)
                        <text class="lbl-x" x="{{ $p[0] }}" y="{{ $h+16 }}" text-anchor="middle">{{ \Carbon\Carbon::parse($visitors[$i]['date'])->format('d/m') }}</text>
                    @endif
                @endforeach
            </svg>
        </div>

        {{-- List dokter aktif --}}
        <div class="panel">
            <h3>List Dokter Sedang Aktif</h3>
            <div class="doc-search">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                <input type="text" id="docSearch" placeholder="Cari dokter…" onkeyup="filterDocs()">
            </div>
            <div class="doc-list" id="docList">
                @forelse ($activeDoctors as $d)
                    @php $ini = collect(preg_split('/\s+/', preg_replace('/\b(dr|drg|Sp|Prof|Dr)\.?/i','',$d->paramedic_name)))->filter()->take(2)->map(fn($w)=>mb_substr($w,0,1))->implode(''); @endphp
                    <div class="doc-item">
                        <div class="av">{{ strtoupper($ini) ?: 'DR' }}</div>
                        <div class="nm">{{ $d->paramedic_name }}</div>
                    </div>
                @empty
                    <div class="doc-empty">Belum ada data dokter aktif.</div>
                @endforelse
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function filterDocs(){
            var q=document.getElementById('docSearch').value.toLowerCase();
            document.querySelectorAll('#docList .doc-item').forEach(function(r){
                r.style.display = r.textContent.toLowerCase().includes(q) ? '' : 'none';
            });
        }
    </script>
    @endpush
@endsection
