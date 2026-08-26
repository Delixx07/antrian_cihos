@extends('layouts.app')
@section('title', 'Daftar Klinik')

@push('head')
<style>
    .toolbar{display:flex;align-items:center;gap:1rem;margin-bottom:1.2rem;flex-wrap:wrap;}
    .search{position:relative;flex:1;max-width:420px;}
    .search svg{position:absolute;left:.8rem;top:50%;transform:translateY(-50%);color:var(--muted);width:16px;height:16px;}
    .count-badge{margin-left:auto;font-size:.82rem;color:var(--muted);}
    .table-scroll{max-height:560px;overflow:auto;}
    .table-scroll table.data{min-width:960px;}
    .table-scroll table.data th{position:sticky;top:0;z-index:1;}
    .badge{display:inline-block;padding:.2rem .55rem;border-radius:6px;font-size:.76rem;font-weight:600;background:#eff4ff;color:var(--brand);}
    .rc{color:#556;font-variant-numeric:tabular-nums;} .rc.none{color:#c7ced9;}
    .src{display:inline-block;margin-left:.4rem;font-size:.62rem;font-weight:700;padding:.1rem .4rem;border-radius:5px;vertical-align:middle;}
    .src-med{background:#eafaf0;color:#16a34a;} .src-loc{background:#fff5e6;color:#d97706;}
    .act{width:34px;height:34px;border-radius:8px;border:none;cursor:pointer;display:grid;place-items:center;
        background:#eff4ff;color:var(--brand);transition:.15s;}
    .act:hover{background:var(--brand);color:#fff;}
    .empty{text-align:center;padding:3rem;color:var(--muted);}
    .ok{background:#eafaf0;border:1px solid #b7ebc9;color:#16794a;padding:.7rem 1rem;border-radius:9px;margin-bottom:1rem;font-size:.88rem;}

    /* Modal */
    .modal{position:fixed;inset:0;background:rgba(15,23,42,.45);backdrop-filter:blur(3px);
        display:none;align-items:center;justify-content:center;z-index:100;padding:1.5rem;}
    .modal.open{display:flex;}
    .modal-box{background:#fff;border-radius:16px;width:100%;max-width:520px;max-height:90vh;overflow-y:auto;
        box-shadow:0 30px 70px rgba(0,0,0,.3);animation:pop .25s ease-out;}
    @keyframes pop{from{opacity:0;transform:translateY(12px) scale(.98);}to{opacity:1;transform:none;}}
    .modal-head{padding:1.3rem 1.5rem;border-bottom:1px solid var(--line);display:flex;align-items:center;gap:.7rem;}
    .modal-head .ic{width:40px;height:40px;border-radius:11px;background:#eff4ff;color:var(--brand);display:grid;place-items:center;flex-shrink:0;}
    .modal-head h3{font-size:1.05rem;font-weight:800;color:var(--ink);}
    .modal-head p{font-size:.78rem;color:var(--muted);}
    .modal-body{padding:1.4rem 1.5rem;}
    .fg{margin-bottom:1rem;}
    .fg label{display:block;font-size:.8rem;font-weight:600;color:#556;margin-bottom:.4rem;}
    .fg input{width:100%;padding:.65rem .85rem;border:1.5px solid var(--line);border-radius:9px;font-size:.9rem;outline:none;transition:.15s;}
    .fg input:focus{border-color:var(--brand);box-shadow:0 0 0 3px #2563eb1c;}
    .fg input[readonly]{background:#fff8e6;border-color:#f2d98a;color:#8a6d1f;font-weight:600;}
    .fg-2{display:grid;grid-template-columns:1fr 1fr;gap:1rem;}
    .modal-foot{padding:1.1rem 1.5rem;border-top:1px solid var(--line);display:flex;justify-content:flex-end;gap:.6rem;background:#fafbfd;}
    .btn-save{background:var(--brand);color:#fff;border:none;padding:.6rem 1.3rem;border-radius:9px;font-weight:600;cursor:pointer;}
    .btn-save:hover{background:#1d4ed8;}
    .btn-cancel{background:#fff;color:#556;border:1px solid var(--line);padding:.6rem 1.1rem;border-radius:9px;font-weight:600;cursor:pointer;}
</style>
@endpush

@section('content')
    <h1 class="page-title">Daftar Klinik</h1>

    @if (session('ok'))<div class="ok">{{ session('ok') }}</div>@endif

    <div class="card" style="padding:1.2rem 1.2rem 0;">
        <div class="toolbar">
            <form method="get" class="search">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                <input class="input" type="text" name="q" value="{{ $q }}" placeholder="Cari kode / nama klinik…" onchange="this.form.submit()">
            </form>
            <span class="count-badge">{{ count($clinics) }} klinik</span>
        </div>

        <div class="table-scroll">
            <table class="data">
                <thead>
                    <tr>
                        <th>Kode Klinik</th>
                        <th>Nama Klinik</th>
                        <th>Zona</th>
                        <th>Room 1</th><th>Room 2</th><th>Room 3</th><th>Room 4</th><th>Room 5</th>
                        <th style="width:70px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($clinics as $c)
                        @php
                            $s  = $settings[$c->service_unit_code] ?? null;
                            $in = $info[$c->service_unit_code] ?? ['source'=>'Lokal','zone'=>null,'rooms'=>[null,null,null,null,null]];
                        @endphp
                        <tr>
                            <td><span class="badge">{{ $c->service_unit_code }}</span></td>
                            <td style="font-weight:600;color:var(--ink);">{{ $c->service_unit_name }}</td>
                            <td class="rc {{ $in['zone'] ? '' : 'none' }}">
                                {{ $in['zone'] ?: '-' }}
                                @if ($in['zone'])
                                    <span class="src src-{{ $in['source'] === 'MEDINFRAS' ? 'med' : 'loc' }}">{{ $in['source'] }}</span>
                                @endif
                            </td>
                            @for ($i = 0; $i < 5; $i++)
                                @php $rc = $in['rooms'][$i] ?? null; @endphp
                                <td class="rc {{ $rc ? '' : 'none' }}">{{ $rc ?: '-' }}</td>
                            @endfor
                            @php
                                $rowData = [
                                    'code' => $c->service_unit_code,
                                    'name' => $c->service_unit_name,
                                    'zone_code' => $s?->zone_code,
                                    'zone_name' => $s?->zone_name,
                                    'room_code_1' => $s?->room_code_1,
                                    'room_code_2' => $s?->room_code_2,
                                    'room_code_3' => $s?->room_code_3,
                                    'room_code_4' => $s?->room_code_4,
                                    'room_code_5' => $s?->room_code_5,
                                ];
                            @endphp
                            <td>
                                <button type="button" class="act" title="Edit" onclick="openEdit({{ \Illuminate\Support\Js::from($rowData) }})">
                                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4z"/></svg>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="empty">Tidak ada klinik ditemukan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Modal Edit Klinik --}}
    <div class="modal" id="editModal" onclick="if(event.target===this)closeEdit()">
        <div class="modal-box">
            <div class="modal-head">
                <div class="ic"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="4" y="3" width="16" height="18" rx="1"/><path d="M9 8h.01M15 8h.01M9 12h.01M15 12h.01M9 16h6"/></svg></div>
                <div><h3>Edit Klinik</h3><p>Atur zona &amp; kode ruangan klinik</p></div>
            </div>
            <form method="post" id="editForm">
                @csrf @method('PUT')
                <div class="modal-body">
                    <div class="fg">
                        <label>Klinik</label>
                        <input type="text" id="f_name" readonly>
                    </div>
                    <div class="fg-2">
                        <div class="fg"><label>Kode Zona</label><input type="text" name="zone_code" id="f_zone_code" placeholder="mis. Z1"></div>
                        <div class="fg"><label>Nama Zona</label><input type="text" name="zone_name" id="f_zone_name" placeholder="mis. Zona 1"></div>
                    </div>
                    <div class="fg-2">
                        <div class="fg"><label>Room Code 1</label><input type="text" name="room_code_1" id="f_r1"></div>
                        <div class="fg"><label>Room Code 2</label><input type="text" name="room_code_2" id="f_r2"></div>
                    </div>
                    <div class="fg-2">
                        <div class="fg"><label>Room Code 3</label><input type="text" name="room_code_3" id="f_r3"></div>
                        <div class="fg"><label>Room Code 4</label><input type="text" name="room_code_4" id="f_r4"></div>
                    </div>
                    <div class="fg"><label>Room Code 5</label><input type="text" name="room_code_5" id="f_r5"></div>
                </div>
                <div class="modal-foot">
                    <button type="button" class="btn-cancel" onclick="closeEdit()">Batal</button>
                    <button type="submit" class="btn-save">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
        var baseUrl = "{{ url('klinik') }}";
        function openEdit(c){
            document.getElementById('editForm').action = baseUrl + '/' + encodeURIComponent(c.code);
            document.getElementById('f_name').value = c.name;
            document.getElementById('f_zone_code').value = c.zone_code || '';
            document.getElementById('f_zone_name').value = c.zone_name || '';
            for (var i=1;i<=5;i++){ document.getElementById('f_r'+i).value = c['room_code_'+i] || ''; }
            document.getElementById('editModal').classList.add('open');
        }
        function closeEdit(){ document.getElementById('editModal').classList.remove('open'); }
        document.addEventListener('keydown', function(e){ if(e.key==='Escape') closeEdit(); });
    </script>
    @endpush
@endsection
