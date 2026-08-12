@extends('layouts.app')
@section('title', 'Antrian (Admin)')

@push('head')
<style>
    .doc-card{display:flex;align-items:center;gap:.9rem;background:#fff;border:1px solid var(--line);border-radius:14px;padding:1rem 1.2rem;margin-bottom:1.2rem;}
    .doc-card .av{width:52px;height:52px;border-radius:14px;background:linear-gradient(135deg,var(--navy),var(--brand));color:#fff;display:grid;place-items:center;font-weight:800;font-size:1.1rem;flex-shrink:0;}
    .doc-card .nm b{font-size:1.05rem;color:var(--ink);display:block;}
    .doc-card .nm span{font-size:.82rem;color:var(--muted);}
    .doc-card .meta{margin-left:auto;text-align:right;}
    .doc-card .meta .big{font-size:1.6rem;font-weight:800;color:var(--brand);line-height:1;}
    .doc-card .meta .lbl{font-size:.72rem;color:var(--muted);text-transform:uppercase;letter-spacing:.04em;}
    .filters{display:flex;align-items:center;gap:.7rem;flex-wrap:wrap;margin-bottom:1.1rem;}
    .filters input[type=date]{padding:.65rem .9rem;border:1px solid var(--line);border-radius:11px;font-size:.88rem;outline:none;background:#fff;font-variant-numeric:tabular-nums;}
    .filters input[type=date]:focus{border-color:var(--brand);box-shadow:0 0 0 3px #2563eb26;}
    /* Combobox dokter — gaya konsisten dgn appointment (trigger + dropdown) */
    .doc-combo{position:relative;width:420px;max-width:100%;}
    .doc-trigger{width:100%;display:flex;align-items:center;justify-content:space-between;gap:.6rem;
        border:1px solid var(--line);background:#fff;color:var(--ink);border-radius:11px;padding:.65rem .9rem;
        font-size:.88rem;cursor:pointer;text-align:left;transition:.15s;}
    .doc-trigger:hover{border-color:#cbd5e1;}
    .doc-trigger.open{border-color:var(--brand);box-shadow:0 0 0 3px #2563eb26;}
    .doc-trigger .lb{overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
    .doc-trigger .lb.ph{color:var(--muted);}
    .doc-trigger .chev{color:var(--muted);flex-shrink:0;transition:transform .18s;}
    .doc-trigger.open .chev{transform:rotate(180deg);}
    .doc-pop{position:absolute;left:0;right:0;margin-top:.4rem;z-index:60;border-radius:13px;overflow:hidden;
        background:#fff;border:1px solid var(--line);box-shadow:0 16px 40px rgba(15,35,80,.16);display:none;}
    .doc-pop.open{display:block;animation:cbxpop .14s ease-out;}
    @keyframes cbxpop{from{opacity:0;transform:translateY(-4px);}to{opacity:1;transform:none;}}
    .doc-search{padding:.6rem;border-bottom:1px solid var(--line);}
    .doc-search input{width:100%;border:1px solid var(--line);border-radius:9px;padding:.55rem .8rem;font-size:.86rem;outline:none;}
    .doc-search input:focus{border-color:var(--brand);box-shadow:0 0 0 3px #2563eb1f;}
    .doc-list{max-height:280px;overflow-y:auto;padding:.3rem;scrollbar-width:thin;}
    .doc-opt{display:flex;flex-direction:column;padding:.55rem .8rem;border-radius:9px;cursor:pointer;transition:.12s;}
    .doc-opt:hover,.doc-opt.active{background:#eff4ff;}
    .doc-opt .on{font-size:.9rem;font-weight:600;color:var(--ink);}
    .doc-opt .oc{font-size:.75rem;color:var(--muted);margin-top:.1rem;}
    .doc-opt.sel{background:#e0ebff;}
    .doc-none{padding:1.1rem;text-align:center;color:var(--muted);font-size:.84rem;}
    .refresh-note{margin-left:auto;font-size:.76rem;color:var(--muted);display:flex;align-items:center;gap:.4rem;}
    .dot{width:8px;height:8px;border-radius:50%;background:#16a34a;display:inline-block;animation:pulse 1.6s infinite;}
    @keyframes pulse{0%,100%{opacity:1;}50%{opacity:.3;}}
    .table-scroll{max-height:600px;overflow:auto;}
    .table-scroll table.data th{position:sticky;top:0;z-index:1;}
    .ticket{font-weight:800;color:var(--navy);font-variant-numeric:tabular-nums;font-size:.95rem;}
    .qn{display:inline-grid;place-items:center;width:30px;height:30px;border-radius:8px;background:#eff4ff;color:var(--brand);font-weight:700;font-size:.82rem;}
    .st{display:inline-flex;align-items:center;gap:.35rem;padding:.2rem .6rem;border-radius:999px;font-size:.74rem;font-weight:600;}
    .st.checkin{background:#eafaf0;color:#16a34a;} .st.wait{background:#fff5e6;color:#d97706;} .st.other{background:#f1f5f9;color:#556;}
    .st i{width:7px;height:7px;border-radius:50%;background:currentColor;}
    .empty{text-align:center;padding:3rem;color:var(--muted);}
    .warn{background:#fff5e6;border:1px solid #f2d98a;color:#8a6d1f;padding:.7rem 1rem;border-radius:9px;margin-bottom:1rem;font-size:.88rem;}
    .info{background:#eff4ff;border:1px solid #c7d7f5;color:#2246a6;padding:.9rem 1.1rem;border-radius:9px;font-size:.88rem;}
</style>
@endpush

@section('content')
    <h1 class="page-title">Antrian — Semua Dokter</h1>

    @php $selDoc = $doctors->firstWhere('paramedic_id', (int) $paramedicId); @endphp
    <div class="filters">
        <form method="get" style="display:flex;gap:.7rem;flex-wrap:wrap;align-items:center;" id="filterForm">
            <input type="hidden" name="paramedic_id" id="docId" value="{{ $paramedicId }}">
            {{-- Combobox dokter (trigger + dropdown pencarian) — gaya appointment --}}
            <div class="doc-combo" id="docCombo">
                <button type="button" class="doc-trigger" id="docTrigger">
                    <span class="lb {{ $selDoc ? '' : 'ph' }}" id="docLabel">
                        {{ $selDoc ? $selDoc->paramedic_name.($selDoc->paramedic_code ? ' ('.$selDoc->paramedic_code.')' : '') : 'Pilih dokter…' }}
                    </span>
                    <svg class="chev" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 9l6 6 6-6"/></svg>
                </button>
                <div class="doc-pop" id="docPop">
                    <div class="doc-search">
                        <input type="text" id="docSearch" autocomplete="off" placeholder="Cari nama / kode dokter…">
                    </div>
                    <div class="doc-list" id="docList"></div>
                </div>
            </div>
            <input type="date" name="date" value="{{ $date }}" onchange="document.getElementById('filterForm').submit()">
        </form>
        @if ($paramedicId)<span class="refresh-note"><span class="dot"></span> Auto-refresh 20 dtk</span>@endif
    </div>

    @php
        $docJson = $doctors->map(fn ($d) => [
            'id'   => $d->paramedic_id,
            'name' => $d->paramedic_name,
            'code' => $d->paramedic_code,
        ])->values();
    @endphp
    @push('scripts')
    <script>
        {{-- @json meng-escape <, >, & sehingga nama dokter dari master tidak
             bisa memutus tag <script> (mis. nama berisi "</script>"). --}}
        var DOCTORS = @json($docJson);

        (function(){
            var combo=document.getElementById('docCombo'), trigger=document.getElementById('docTrigger'),
                pop=document.getElementById('docPop'), search=document.getElementById('docSearch'),
                list=document.getElementById('docList'), hid=document.getElementById('docId');
            var active=-1, filtered=DOCTORS, curId=hid.value;

            function esc(s){ return String(s==null?'':s).replace(/[&<>"]/g,function(c){return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'})[c];}); }

            function render(arr){
                filtered=arr; active=-1;
                if(!arr.length){ list.innerHTML='<div class="doc-none">Dokter tidak ditemukan.</div>'; return; }
                list.innerHTML = arr.map(function(d,i){
                    var sel = String(d.id)===String(curId) ? ' sel' : '';
                    return '<div class="doc-opt'+sel+'" data-i="'+i+'">'+
                           '<span class="on">'+esc(d.name)+'</span>'+
                           (d.code?'<span class="oc">'+esc(d.code)+'</span>':'')+'</div>';
                }).join('');
            }
            function open(){
                render(DOCTORS); pop.classList.add('open'); trigger.classList.add('open');
                search.value=''; setTimeout(function(){ search.focus(); },30);
            }
            function close(){ pop.classList.remove('open'); trigger.classList.remove('open'); }
            function choose(d){ hid.value=d.id; document.getElementById('filterForm').submit(); }

            trigger.addEventListener('click', function(){ pop.classList.contains('open') ? close() : open(); });
            search.addEventListener('input', function(){
                var q=this.value.toLowerCase().trim();
                render(!q ? DOCTORS : DOCTORS.filter(function(d){
                    return (d.name+' '+(d.code||'')).toLowerCase().includes(q);
                }));
            });
            search.addEventListener('keydown', function(e){
                if(e.key==='ArrowDown'){ e.preventDefault(); active=Math.min(active+1,filtered.length-1); paint(); }
                else if(e.key==='ArrowUp'){ e.preventDefault(); active=Math.max(active-1,0); paint(); }
                else if(e.key==='Enter'){ e.preventDefault(); if(active>=0&&filtered[active]) choose(filtered[active]); }
                else if(e.key==='Escape'){ close(); }
            });
            function paint(){
                list.querySelectorAll('.doc-opt').forEach(function(el){
                    el.classList.toggle('active', +el.dataset.i===active);
                    if(+el.dataset.i===active) el.scrollIntoView({block:'nearest'});
                });
            }
            list.addEventListener('click', function(e){
                var opt=e.target.closest('.doc-opt'); if(!opt) return;
                choose(filtered[+opt.dataset.i]);
            });
            document.addEventListener('click', function(e){ if(!combo.contains(e.target)) close(); });
        })();
    </script>
    @endpush

    @if (! $paramedicId)
        <div class="info">Pilih dokter di atas untuk melihat antriannya (data live dari registrasi RS).</div>
    @else
        @php
            $dname = $doctor?->paramedic_name ?? ('Dokter #'.$paramedicId);
            $ini = collect(preg_split('/\s+/', preg_replace('/\b(dr|drg|Sp|Prof|Dr)\.?/i','',$dname)))->filter()->take(2)->map(fn($w)=>mb_substr($w,0,1))->implode('');
        @endphp
        <div class="doc-card">
            <div class="av">{{ strtoupper($ini) ?: 'DR' }}</div>
            <div class="nm">
                <b>{{ $dname }}</b>
                <span>{{ $doctor?->specialty_name ?: 'Umum' }} · {{ \Illuminate\Support\Carbon::parse($date)->translatedFormat('l, d F Y') }}</span>
            </div>
            <div class="meta"><div class="big">{{ $total }}</div><div class="lbl">Pasien Antri</div></div>
        </div>

        @if (! $apiOk)<div class="warn">{{ $apiMessage ?: 'Data antrian tidak tersedia saat ini.' }}</div>@endif

        <div class="card" style="padding:0;overflow:hidden;">
            <div class="table-scroll">
                <table class="data">
                    <thead>
                        <tr>
                            <th style="width:110px;">No. Antrian</th><th style="width:70px;">Antri</th>
                            <th>Nama Pasien</th><th>No. RM</th><th>Ruangan</th><th>Jam Daftar</th><th style="width:130px;">Status</th>
                        </tr>
                    </thead>
                    <tbody>@include('queue._rows', ['rows' => $rows])</tbody>
                </table>
            </div>
        </div>

        @push('scripts')
        <script>setTimeout(function(){ location.reload(); }, 20000);</script>
        @endpush
    @endif
@endsection
