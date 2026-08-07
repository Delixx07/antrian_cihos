@extends('layouts.app')
@section('title', 'Antrian')

@push('head')
<style>
    .toolbar{display:flex;align-items:center;gap:1rem;margin-bottom:1.2rem;flex-wrap:wrap;}
    .doc-card{display:flex;align-items:center;gap:.9rem;background:#fff;border:1px solid var(--line);border-radius:14px;padding:1rem 1.2rem;margin-bottom:1.2rem;}
    .doc-card .av{width:52px;height:52px;border-radius:14px;background:linear-gradient(135deg,var(--navy),var(--brand));color:#fff;display:grid;place-items:center;font-weight:800;font-size:1.1rem;flex-shrink:0;}
    .doc-card .nm b{font-size:1.05rem;color:var(--ink);display:block;}
    .doc-card .nm span{font-size:.82rem;color:var(--muted);}
    .doc-card .meta{margin-left:auto;text-align:right;}
    .doc-card .meta .big{font-size:1.6rem;font-weight:800;color:var(--brand);line-height:1;}
    .doc-card .meta .lbl{font-size:.72rem;color:var(--muted);text-transform:uppercase;letter-spacing:.04em;}

    .filters{display:flex;align-items:center;gap:.7rem;flex-wrap:wrap;margin-bottom:1.1rem;}
    .filters select,.filters input[type=date]{padding:.55rem .8rem;border:1.5px solid var(--line);border-radius:9px;font-size:.88rem;outline:none;background:#fff;}
    .filters select:focus,.filters input:focus{border-color:var(--brand);box-shadow:0 0 0 3px #2563eb1c;}
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
    <h1 class="page-title">Antrian</h1>

    @if ($isAdmin)
        {{-- Admin/SPV: pilih dokter --}}
        <div class="filters">
            <form method="get" style="display:flex;gap:.7rem;flex-wrap:wrap;align-items:center;" id="filterForm">
                <select name="paramedic_id" onchange="document.getElementById('filterForm').submit()">
                    <option value="">— Pilih Dokter —</option>
                    @foreach ($doctors as $d)
                        <option value="{{ $d->paramedic_id }}" {{ (int)$paramedicId === (int)$d->paramedic_id ? 'selected' : '' }}>
                            {{ $d->paramedic_name }} @if($d->paramedic_code) ({{ $d->paramedic_code }}) @endif
                        </option>
                    @endforeach
                </select>
                <input type="date" name="date" value="{{ $date }}" onchange="document.getElementById('filterForm').submit()">
            </form>
            <span class="refresh-note"><span class="dot"></span> Auto-refresh 20 dtk</span>
        </div>
    @endif

    @if (! $paramedicId)
        <div class="info">
            @if ($isAdmin)
                Pilih dokter di atas untuk melihat antriannya.
            @else
                Akun Anda belum terhubung ke seorang dokter. Hubungi administrator.
            @endif
        </div>
    @else
        {{-- Kartu dokter --}}
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
            <div class="meta">
                <div class="big" id="totalCount">{{ $total }}</div>
                <div class="lbl">Pasien Antri</div>
            </div>
        </div>

        @unless ($isAdmin)
            <div class="filters">
                <span class="refresh-note"><span class="dot"></span> Auto-refresh 20 dtk</span>
            </div>
        @endunless

        @if (! $apiOk)
            <div class="warn">{{ $apiMessage ?: 'Data antrian tidak tersedia saat ini.' }}</div>
        @endif

        <div class="card" style="padding:0;overflow:hidden;">
            <div class="table-scroll">
                <table class="data">
                    <thead>
                        <tr>
                            <th style="width:110px;">No. Antrian</th>
                            <th style="width:70px;">Antri</th>
                            <th>Nama Pasien</th>
                            <th>No. RM</th>
                            <th>Ruangan</th>
                            <th>Jam Daftar</th>
                            <th style="width:130px;">Status</th>
                        </tr>
                    </thead>
                    <tbody id="queueBody">
                        @include('queue._rows', ['rows' => $rows])
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @push('scripts')
    <script>
        var QUEUE_JSON = "{{ route('queue.json') }}";
        var Q_PARAMS = "paramedic_id={{ $paramedicId }}&date={{ $date }}";

        function statusClass(s){
            var x = String(s||'').toLowerCase();
            if(x.indexOf('check')!==-1 || x.indexOf('hadir')!==-1) return 'checkin';
            if(x.indexOf('wait')!==-1 || x.indexOf('tunggu')!==-1) return 'wait';
            return 'other';
        }
        function esc(s){ return String(s==null?'':s).replace(/[&<>"']/g,function(c){return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c];}); }

        function renderRows(rows){
            if(!rows || !rows.length){
                return '<tr><td colspan="7" class="empty">Belum ada pasien pada antrian hari ini.</td></tr>';
            }
            return rows.map(function(r){
                return '<tr>'
                    + '<td><span class="ticket">'+esc(r.ticket)+'</span></td>'
                    + '<td><span class="qn">'+esc(r.queue)+'</span></td>'
                    + '<td style="font-weight:600;color:var(--ink);">'+esc(r.patient)+'</td>'
                    + '<td style="color:#556;">'+esc(r.medicalNo)+'</td>'
                    + '<td style="color:#556;">'+esc(r.room)+(r.roomName && r.roomName!==r.room ? ' · '+esc(r.roomName):'')+'</td>'
                    + '<td class="tabular-nums" style="color:#556;">'+esc(r.timeRegis)+'</td>'
                    + '<td><span class="st '+statusClass(r.status)+'"><i></i> '+esc(r.status)+'</span></td>'
                    + '</tr>';
            }).join('');
        }

        function refreshQueue(){
            @if($paramedicId)
            fetch(QUEUE_JSON + '?' + Q_PARAMS, {headers:{'X-Requested-With':'XMLHttpRequest'}})
                .then(r=>r.json())
                .then(function(j){
                    if(j && j.ok){
                        document.getElementById('queueBody').innerHTML = renderRows(j.rows);
                        var tc=document.getElementById('totalCount'); if(tc) tc.textContent = j.total;
                    }
                })
                .catch(function(){});
            @endif
        }
        @if($paramedicId)
        setInterval(refreshQueue, 20000); // auto-refresh tiap 20 detik
        @endif
    </script>
    @endpush
@endsection
