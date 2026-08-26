@extends('layouts.app')
@section('title', 'Monitor Antrian')
@section('pagehead', 'Monitor Antrian per Klinik')
@section('pagesub', now()->translatedFormat('l, d F Y'))

@push('head')
    @include('partials.queue-console-css')
    <style>
        .sv-bar{display:flex;align-items:center;justify-content:flex-end;gap:.9rem;margin-bottom:1.3rem;}
        /* Maksimal 2 kolom (lebih lebar per kartu) - klinik selanjutnya turun ke baris berikutnya. */
        .sv-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:1.25rem;align-items:stretch;}
        @media(max-width:860px){.sv-grid{grid-template-columns:1fr;}}
        .sv-ganti{background:#fff;color:var(--kq-brand);border:1.5px solid var(--kq-brand);border-radius:10px;
            padding:.55rem 1.1rem;font-weight:700;font-size:.85rem;cursor:pointer;display:inline-flex;
            align-items:center;gap:.4rem;transition:background-color .15s,transform .1s;}
        .sv-ganti:hover{background:var(--kq-brand-tint);}
        .sv-ganti:active{transform:scale(.97);}

        /* Semua kartu klinik tinggi sama walau datanya beda (bahkan kosong) - grid tetap
           rapi/konsisten. Body tabel tinggi TETAP (pas 5 baris) & scroll vertikal kalau
           lebih - BUKAN pagination lagi, dan tetap setinggi itu walau datanya kosong. */
        .sv-panel{display:flex;flex-direction:column;}
        .sv-panel-b{flex-shrink:0;overflow-x:auto;overflow-y:auto;height:272px;}
        .sv-panel table.data{width:100%;}
        .sv-empty-row td{text-align:center;color:var(--muted);padding:2.2rem 1rem;}

        /* Header sortable - interaktif: hover ngasih highlight, kolom aktif
           ke-tint warna brand, panah searah asc/desc (bukan ↕ statis). */
        .sv-panel table.data thead th{position:sticky;top:0;z-index:1;background:#fff;
            cursor:pointer;user-select:none;white-space:nowrap;transition:background-color .15s,color .15s;}
        .sv-panel table.data thead th:hover{background:#eff4ff;color:var(--kq-brand);}
        .sv-panel table.data th .arr{display:inline-flex;vertical-align:-1px;margin-left:.4em;
            opacity:.3;transition:opacity .15s,transform .25s;}
        .sv-panel table.data th .arr svg{width:10px;height:10px;display:block;}
        .sv-panel table.data th.sorted{background:#eff4ff;color:var(--kq-brand);}
        .sv-panel table.data th.sorted .arr{opacity:1;}
        .sv-panel table.data th.sorted[data-dir="desc"] .arr{transform:rotate(180deg);}

        /* Badge kecil di header kartu - nunjukin filter dokter yang lagi aktif
           (dipilih dari modal Ganti Dokter), TANPA kontrol apa pun di sini. */
        .sv-docbadge{display:inline-flex;align-items:center;gap:.3rem;background:#eff4ff;color:var(--kq-brand);
            font-size:.72rem;font-weight:700;padding:.25rem .65rem;border-radius:999px;margin-left:.6rem;}

        /* Chip status berwarna - .sr di partials/queue-console-css.blade.php cuma ke-style
           di dalam .kq-row (kartu lama); di sini pakai <table> biasa jadi perlu style sendiri. */
        .sv-table .sr{font-size:.72rem;font-weight:700;padding:.22rem .6rem;border-radius:7px;white-space:nowrap;}
        .sv-table .sr.resep{background:#fff7e6;color:#b45309;}
        .sv-table .sr.clear{background:#f0fdf4;color:#15803d;}

        .sv-prompt{padding:3rem 2rem;text-align:center;color:var(--muted);}
        .sv-prompt p{margin-bottom:1rem;font-size:.9rem;}

        /* ---------- Modal Ganti Klinik / Ganti Dokter ---------- */
        .modal{position:fixed;inset:0;background:rgba(15,23,42,.45);backdrop-filter:blur(3px);display:none;
            align-items:center;justify-content:center;z-index:100;padding:1.5rem;
            opacity:0;transition:opacity .2s ease;}
        .modal.open{display:flex;opacity:1;}
        .modal-box{background:#fff;border-radius:16px;width:100%;max-width:520px;max-height:88vh;
            display:flex;flex-direction:column;box-shadow:0 30px 70px rgba(0,0,0,.3);
            transform:translateY(14px) scale(.97);opacity:0;transition:transform .25s cubic-bezier(.2,.9,.3,1.2),opacity .2s ease;}
        .modal.open .modal-box{transform:none;opacity:1;}
        .modal-head{flex-shrink:0;padding:1.3rem 1.5rem;border-bottom:1px solid var(--line);display:flex;align-items:center;gap:.7rem;}
        .modal-head .ic{width:40px;height:40px;border-radius:11px;background:#eff4ff;color:var(--brand);display:grid;place-items:center;flex-shrink:0;}
        .modal-head h3{font-size:1.05rem;font-weight:800;color:var(--ink);}
        .modal-head p{font-size:.78rem;color:var(--muted);}
        .modal-close{margin-left:auto;width:32px;height:32px;border-radius:8px;border:none;background:#f1f5f9;
            color:#556;cursor:pointer;font-size:1.1rem;line-height:1;flex-shrink:0;transition:background .15s;}
        .modal-close:hover{background:#e2e8f0;}
        .modal-list{flex:1;overflow-y:auto;}
        .pk-row{display:flex;align-items:center;gap:.9rem;padding:.85rem 1.5rem;border-bottom:1px solid var(--line);
            cursor:pointer;transition:background .12s;}
        .pk-row:last-child{border-bottom:none;}
        .pk-row:hover{background:#fafbfd;}
        .pk-row input{width:17px;height:17px;accent-color:var(--brand);cursor:pointer;flex-shrink:0;}
        .pk-row span{font-weight:600;font-size:.85rem;color:var(--ink);}
        .pk-row.muted{cursor:default;color:var(--muted);font-size:.85rem;}
        .pk-group-h{padding:.8rem 1.5rem .4rem;font-size:.7rem;font-weight:800;letter-spacing:.07em;
            text-transform:uppercase;color:var(--muted);background:#fafbfd;border-top:1px solid var(--line);
            display:flex;align-items:center;justify-content:space-between;gap:.6rem;}
        .modal-list>.pk-group-h:first-child{border-top:none;}
        .pk-group-tools{display:flex;gap:.5rem;}
        .pk-group-tools button{border:none;background:none;color:var(--brand);font-size:.68rem;font-weight:800;
            letter-spacing:.03em;text-transform:none;cursor:pointer;padding:.15rem .3rem;border-radius:5px;transition:background .12s;}
        .pk-group-tools button:hover{background:var(--kq-brand-tint,#eff4ff);}
        .modal-foot{flex-shrink:0;padding:1.1rem 1.5rem;border-top:1px solid var(--line);display:flex;
            justify-content:flex-end;gap:.6rem;background:#fafbfd;}
        .btn-save{background:var(--brand);color:#fff;border:none;padding:.6rem 1.3rem;border-radius:9px;font-weight:600;cursor:pointer;}
        .btn-save:hover{background:#1d4ed8;}
        .btn-cancel{background:#fff;color:#556;border:1px solid var(--line);padding:.6rem 1.1rem;border-radius:9px;font-weight:600;cursor:pointer;}
        .err-flash{background:#fdecec;border:1px solid #f5b5b5;color:#b42318;padding:.7rem 1rem;border-radius:9px;margin:1rem 1.5rem 0;font-size:.85rem;}
    </style>
@endpush

@section('content')
<div class="kq">
    <div class="sv-bar">
        <span class="kq-live"><span class="dot"></span> Auto-refresh 20 dtk</span>
        <button type="button" class="sv-ganti" onclick="openModal('dokterModal')">⇄ Ganti Dokter</button>
        <button type="button" class="sv-ganti" onclick="openModal('klinikModal')">⇄ Ganti Klinik</button>
    </div>

    @php $arrIcon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M18 15l-6-6-6 6"/></svg>'; @endphp

    <div class="sv-grid">
        @forelse ($clinics as $c)
            @php
                $code = $c->service_unit_code;
                $list = $rows->get($code, collect());
                $filteredIds = $dokterFilter[$code] ?? [];
                $filteredNames = $filteredIds
                    ? $allRows->get($code, collect())->whereIn('paramedic_id', $filteredIds)
                        ->unique('paramedic_id')->pluck('poli_dokter_nama')->filter()->values()
                    : collect();
            @endphp
            <div class="kq-panel sv-panel">
                <div class="kq-panel-h">
                    <h3>{{ $c->service_unit_name }}</h3>
                    @if ($filteredNames->isNotEmpty())
                        <span class="sv-docbadge" title="{{ $filteredNames->implode(', ') }}">
                            👤 {{ $filteredNames->count() > 1 ? $filteredNames->count().' dokter' : $filteredNames->first() }}
                        </span>
                    @endif
                    <span class="cnt">{{ $list->count() }}</span>
                </div>
                <div class="sv-panel-b">
                    <table class="data sv-table">
                        <thead>
                            <tr>
                                <th data-type="text">No Antrian<span class="arr">{!! $arrIcon !!}</span></th>
                                <th data-type="text">Nama Pasien<span class="arr">{!! $arrIcon !!}</span></th>
                                <th data-type="text">Nama Dokter<span class="arr">{!! $arrIcon !!}</span></th>
                                <th data-type="text">Room<span class="arr">{!! $arrIcon !!}</span></th>
                                <th data-type="text">Status Panggil<span class="arr">{!! $arrIcon !!}</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($list as $a)
                                <tr>
                                    <td>{{ $a->no_antrian }}</td>
                                    <td>{{ $a->pasien_nama }}</td>
                                    <td>{{ $a->poli_dokter_nama ?: '-' }}</td>
                                    <td>{{ $a->room_code ?: '-' }}</td>
                                    <td>
                                        @if ($a->isDipanggil('klinik'))
                                            <span class="sr clear">Dipanggil Dokter</span>
                                        @else
                                            <span class="sr resep">Menunggu di Klinik</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr class="sv-empty-row"><td colspan="5">Tidak ada antrian.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
            <div class="kq-panel sv-panel" style="grid-column:1/-1;">
                <div class="sv-prompt">
                    <p>Belum ada klinik dipilih.</p>
                    <button type="button" class="sv-ganti" style="display:inline-flex;" onclick="openModal('klinikModal')">⇄ Pilih Klinik</button>
                </div>
            </div>
        @endforelse
    </div>
</div>

{{-- Modal: Ganti Klinik --}}
<div class="modal {{ $errors->has('klinik') ? 'open' : '' }}" id="klinikModal" onclick="if(event.target===this)closeModal('klinikModal')">
    <div class="modal-box">
        <div class="modal-head">
            <div class="ic"><svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="4" y="3" width="16" height="18" rx="1"/><path d="M9 8h.01M15 8h.01M9 12h.01M15 12h.01M9 16h6"/></svg></div>
            <div><h3>Pilih Klinik</h3><p>Klinik yang ditampilkan di dashboard</p></div>
            <button type="button" class="modal-close" onclick="closeModal('klinikModal')">×</button>
        </div>
        @if ($errors->has('klinik'))<div class="err-flash">{{ $errors->first('klinik') }}</div>@endif
        <form method="post" action="{{ route('spv.set-klinik') }}" style="display:flex;flex-direction:column;min-height:0;">
            @csrf
            <div class="modal-list">
                @forelse ($allClinics as $c)
                    <label class="pk-row">
                        <input type="checkbox" name="klinik[]" value="{{ $c->service_unit_code }}"
                            {{ in_array($c->service_unit_code, $selectedCodes, true) ? 'checked' : '' }}>
                        <span>{{ strtoupper($c->service_unit_name) }}</span>
                    </label>
                @empty
                    <div class="pk-row muted">Belum ada data klinik.</div>
                @endforelse
            </div>
            <div class="modal-foot">
                <button type="button" class="btn-cancel" onclick="closeModal('klinikModal')">Batal</button>
                <button type="submit" class="btn-save">Tampilkan</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal: Ganti Dokter --}}
<div class="modal" id="dokterModal" onclick="if(event.target===this)closeModal('dokterModal')">
    <div class="modal-box">
        <div class="modal-head">
            <div class="ic"><svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg></div>
            <div><h3>Pilih Dokter</h3><p>Bisa pilih lebih dari satu per klinik - kosongkan semua utk tampilkan semua dokter</p></div>
            <button type="button" class="modal-close" onclick="closeModal('dokterModal')">×</button>
        </div>
        <form method="post" action="{{ route('spv.set-dokter') }}" style="display:flex;flex-direction:column;min-height:0;">
            @csrf
            <div class="modal-list">
                @forelse ($clinics as $c)
                    @php
                        $code = $c->service_unit_code;
                        $doctors = $doctorsByClinic->get($code, collect());
                        $selIds = $dokterFilter[$code] ?? [];
                    @endphp
                    <div class="pk-group-h">
                        <span>{{ strtoupper($c->service_unit_name) }}</span>
                        @if ($doctors->count() > 1)
                            <span class="pk-group-tools">
                                <button type="button" onclick="pkCheckAll('{{ $code }}', true)">Pilih Semua</button>
                                <button type="button" onclick="pkCheckAll('{{ $code }}', false)">Kosongkan</button>
                            </span>
                        @endif
                    </div>
                    @forelse ($doctors as $d)
                        <label class="pk-row">
                            <input type="checkbox" data-clinic="{{ $code }}" name="dokter[{{ $code }}][]" value="{{ $d->paramedic_id }}" {{ in_array($d->paramedic_id, $selIds, true) ? 'checked' : '' }}>
                            <span>{{ $d->poli_dokter_nama }}</span>
                        </label>
                    @empty
                        <div class="pk-row muted">Belum ada dokter dengan antrian hari ini.</div>
                    @endforelse
                @empty
                    <div class="pk-row muted">Belum ada klinik dipilih.</div>
                @endforelse
            </div>
            <div class="modal-foot">
                <button type="button" class="btn-cancel" onclick="closeModal('dokterModal')">Batal</button>
                <button type="submit" class="btn-save">Tampilkan</button>
            </div>
        </form>
    </div>
</div>

    @push('scripts')
    <script>
        function openModal(id){ document.getElementById(id).classList.add('open'); }
        function closeModal(id){ document.getElementById(id).classList.remove('open'); }
        // "Pilih Semua" / "Kosongkan" per klinik di modal Ganti Dokter (multi-pilih).
        function pkCheckAll(klinikCode, check){
            document.querySelectorAll('input[data-clinic="' + CSS.escape(klinikCode) + '"]').forEach(function (cb) {
                cb.checked = check;
            });
        }
        document.addEventListener('keydown', function (e) {
            if (e.key !== 'Escape') return;
            document.querySelectorAll('.modal.open').forEach(function (m) { m.classList.remove('open'); });
        });

        // Tiap kartu klinik: sort (klik header) saja - filter dokter & paging
        // sudah dipindah ke server (lihat SpvController::setDokter()), dan
        // body tabel sekarang scroll sendiri (CSS .sv-panel-b), bukan dipaging.
        document.querySelectorAll('.sv-panel').forEach(function (panel) {
            var table = panel.querySelector('.sv-table');
            if (!table) return; // panel kosong (tidak ada data) - tidak perlu apa-apa.

            var tbody = table.querySelector('tbody');
            var rows  = Array.from(tbody.querySelectorAll('tr'));
            if (! rows.length || rows[0].classList.contains('sv-empty-row')) return;

            table.querySelectorAll('th').forEach(function (th, colIndex) {
                th.addEventListener('click', function () {
                    var asc = th.dataset.dir !== 'asc';
                    table.querySelectorAll('th').forEach(function (h) { h.classList.remove('sorted'); h.dataset.dir = ''; });
                    th.classList.add('sorted');
                    th.dataset.dir = asc ? 'asc' : 'desc';

                    rows.sort(function (a, b) {
                        var av = a.children[colIndex].textContent.trim();
                        var bv = b.children[colIndex].textContent.trim();
                        return asc
                            ? av.localeCompare(bv, undefined, { numeric: true })
                            : bv.localeCompare(av, undefined, { numeric: true });
                    });
                    rows.forEach(function (r) { tbody.appendChild(r); });
                });
            });
        });

        // Sama seperti konsol klinik: cek dulu sesi masih valid (fetch redirect:'manual')
        // sebelum reload sungguhan, supaya hiccup DB/session sesaat tak melempar ke /login.
        var _authFails = 0;
        var LOGIN_URL = @json(route('login'));

        function safeRefresh(){
            fetch(window.location.href, { credentials: 'same-origin', redirect: 'manual', cache: 'no-store' })
                .then(function (res) {
                    if (res.type === 'opaqueredirect') {
                        _authFails++;
                        if (_authFails >= 3) { location.href = LOGIN_URL; }
                        return;
                    }
                    if (!res.ok) return;
                    _authFails = 0;
                    location.reload();
                })
                .catch(function () {});
        }
        setInterval(safeRefresh, 20000);
    </script>
    @endpush
@endsection
