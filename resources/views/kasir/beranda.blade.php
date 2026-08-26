@extends('layouts.app')
@section('title', 'Counter Kasir')
@section('pagehead', $roleLabel)
@section('pagesub', strtoupper($counter).' · '.now()->translatedFormat('l, d F Y'))

@push('head')
    @include('partials.queue-console-css')
    <style>.kq{--kq-g1:#2563eb;--kq-g2:#1e40af;--kq-shadow:rgba(37,99,235,.28);--kq-brand:#1d4ed8;}</style>
@endpush

@section('content')
<div class="kq">
    @if (session('ok'))<div class="kq-flash ok">✓ {{ session('ok') }}</div>@endif
    @if (session('error'))<div class="kq-flash err">⚠ {{ session('error') }}</div>@endif

    <div class="kq-grid">
        {{-- ── Kolom kiri: Sedang Dilayani + Ringkasan ── --}}
        <div>
            <div class="kq-panel">
                <div class="kq-panel-h"><h3>Sedang Dilayani</h3></div>
                <div class="kq-panel-b">
                    @if ($current)
                        <div class="kq-callbox">
                            <div class="num">{{ $current->no_antrian }}</div>
                            <div class="pt">{{ $current->pasien_nama }}</div>
                            <span class="badge">{{ $current->statusLabel() }}</span>
                        </div>
                        <div class="kq-acts">
                            <form method="post" action="{{ route('kasir.ulang', $current) }}">@csrf @method('PUT')
                                <button class="b-recall" type="submit">⟲ Recall</button></form>
                            <form onsubmit="return false;">
                                <button class="b-done" type="button" onclick="openSelesai({{ $current->id }})">✔ Selesai</button></form>
                        </div>
                    @else
                        <div class="kq-idle">
                            <div class="kq-idle-ic">🧾</div>
                            <div class="kq-idle-t">Tidak ada pasien dilayani</div>
                            <div class="kq-idle-s">Panggil pasien dari antrian di samping.</div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="kq-panel">
                <div class="kq-panel-h"><h3>Ringkasan</h3></div>
                <div class="kq-panel-b" style="padding-top:.4rem;padding-bottom:.4rem;">
                    <div class="kq-inforow"><span>Nomor Berikutnya</span><span class="chip blue">{{ $berikutnya->no_antrian ?? '-' }}</span></div>
                    <div class="kq-inforow"><span>Sisa Antrian</span><span class="chip amber">{{ $sisa }}</span></div>
                </div>
            </div>
        </div>

        {{-- ── Kolom kanan: Antrian Menunggu + Sedang Dipanggil ── --}}
        <div>
            <div class="kq-panel">
                <div class="kq-panel-h"><h3>Antrian Menunggu</h3><span class="cnt">{{ $menunggu->count() }}</span>
                    <span class="kq-live" style="margin-left:auto;"><span class="dot"></span> Live</span></div>
                @if ($menunggu->count())
                    <div class="kq-hrow"><span class="h-tk">No.</span><span class="h-who">Pasien / Klinik · Dokter</span>
                        <span>Status</span><span>Tunggu</span><span class="h-end">Aksi</span></div>
                @endif
                <div class="kq-list">
                    @forelse ($menunggu as $a)
                        @php $pending = $a->adaResep() && ! $a->resep_clear; @endphp
                        <div class="kq-row {{ $pending ? 'locked' : '' }}">
                            <span class="tk">{{ $a->no_antrian }}</span>
                            <div class="who">
                                <div class="nm">{{ $a->pasien_nama }}</div>
                                <div class="rm">{{ $a->poli_nama ?: '-' }} · {{ $a->poli_dokter_nama ?: '-' }}</div>
                            </div>
                            <span class="sr {{ $a->statusWarna() }}">{{ $a->statusLabel() }}</span>
                            <span class="waitchip">{{ $a->menitTunggu() }}m</span>
                            @if ($pending)
                                <span class="kq-lock" title="Pasien harus selesai di Farmasi dulu">⏳ Menunggu Farmasi</span>
                            @else
                                <form method="post" action="{{ route('kasir.panggil', $a) }}">@csrf @method('PUT')
                                    <button type="submit" class="kq-btn call">▶ Panggil</button></form>
                            @endif
                        </div>
                    @empty
                        <div class="kq-empty">Tidak ada antrian menunggu.</div>
                    @endforelse
                </div>
            </div>

            <div class="kq-panel">
                <div class="kq-panel-h"><h3>Sedang Dipanggil</h3><span class="cnt gray">{{ $dipanggil->count() }}</span></div>
                @if ($dipanggil->count())
                    <div class="kq-hrow"><span class="h-tk">No.</span><span class="h-who">Pasien / Dokter</span>
                        <span>Status</span><span>Counter</span><span class="h-end">Aksi</span></div>
                @endif
                <div class="kq-list">
                    @forelse ($dipanggil as $a)
                        <div class="kq-row">
                            <span class="tk">{{ $a->no_antrian }}</span>
                            <div class="who">
                                <div class="nm">{{ $a->pasien_nama }}</div>
                                <div class="rm">{{ $a->poli_dokter_nama ?: '-' }}</div>
                            </div>
                            <span class="sr {{ $a->statusWarna() }}">{{ $a->statusLabel() }}</span>
                            <span class="waitchip">{{ $a->counter }}</span>
                            @if ($a->counter === $counter)
                                <form method="post" action="{{ route('kasir.ulang', $a) }}">@csrf @method('PUT')
                                    <button type="submit" class="kq-btn recall">⟲ Recall</button></form>
                            @else
                                <span class="kq-otherctr" title="Sedang ditangani counter lain">Counter lain</span>
                            @endif
                        </div>
                    @empty
                        <div class="kq-empty">Belum ada yang dipanggil.</div>
                    @endforelse
                </div>
            </div>

            {{-- Riwayat: pasien selesai (bisa dipanggil ulang) --}}
            <div class="kq-panel">
                <div class="kq-panel-h"><h3>Sudah Ditangani</h3><span class="cnt gray">{{ $selesai->count() }}</span></div>
                @if ($selesai->count())
                    <div class="kq-hrow"><span class="h-tk">No.</span><span class="h-who">Pasien / Dokter</span>
                        <span>Status</span><span class="h-end">Aksi</span></div>
                @endif
                <div class="kq-list">
                    @forelse ($selesai as $a)
                        <div class="kq-row">
                            <span class="tk">{{ $a->no_antrian }}</span>
                            <div class="who">
                                <div class="nm">{{ $a->pasien_nama }}</div>
                                <div class="rm">{{ $a->poli_dokter_nama ?: '-' }}</div>
                            </div>
                            <span class="sr {{ $a->statusWarna() }}">{{ $a->statusLabel() }}</span>
                            <form method="post" action="{{ route('kasir.panggil-ulang', $a) }}"
                                  data-confirm="Panggil ulang <b>{{ $a->no_antrian }}</b> ({{ $a->pasien_nama }}) kembali ke Kasir?"
                                  data-cf-title="Panggil Ulang Pasien?" data-cf-icon="⟲"
                                  data-cf-yescolor="#f59e0b" data-cf-yeslabel="Ya, Panggil Ulang">@csrf @method('PUT')
                                <button type="submit" class="kq-btn recall">⟲ Panggil Ulang</button></form>
                        </div>
                    @empty
                        <div class="kq-empty">Belum ada pasien selesai.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- ═══ Modal Selesai - kasir memilih status resep pasien ═══ --}}
    <div class="modal" id="selesaiModal" onclick="if(event.target===this)closeSelesai()">
        <div class="modal-box">
            <div class="modal-head">
                <h3>Selesai - Pilih Status Resep</h3>
                <p>Setelah ini pasien tanpa resep dianggap selesai; pasien ber-resep diteruskan ke Farmasi.</p>
                <div class="pt"><span class="tk" id="mdTicket">-</span><span class="nm" id="mdName">-</span></div>
            </div>
            <div class="modal-body">
                <form method="post" id="mdForm" action="#">@csrf @method('PUT')
                    <input type="hidden" name="status_resep" id="mdStatus" value="">
                    <button type="button" class="opt" onclick="submitSelesai('non_resep')">
                        <span class="ic none">✓</span>
                        <span class="tx"><b>Tanpa Resep</b><span>Pasien selesai, tidak ke Farmasi</span></span>
                        <span class="arr">→</span>
                    </button>
                    <button type="button" class="opt" onclick="submitSelesai('racik')">
                        <span class="ic racik">💊</span>
                        <span class="tx"><b>Resep Racik</b><span>Obat racikan - diteruskan ke Farmasi Racik</span></span>
                        <span class="arr">→</span>
                    </button>
                    <button type="button" class="opt" onclick="submitSelesai('non_racik')">
                        <span class="ic nonracik">💊</span>
                        <span class="tx"><b>Resep Non-Racik</b><span>Obat jadi - diteruskan ke Farmasi Non-Racik</span></span>
                        <span class="arr">→</span>
                    </button>
                </form>
            </div>
            <div class="modal-foot"><button type="button" class="btn-cancel" onclick="closeSelesai()">Batal</button></div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    var PATIENTS = {
        @if($current)"{{ $current->id }}": { ticket: @json($current->no_antrian), name: @json($current->pasien_nama) },@endif
    };
    var SELESAI_URL = @json(url('/kasir').'/__ID__/selesai');
    var _selId = null, _stopReload = false;

    function openSelesai(id){
        _selId = id;
        var p = PATIENTS[id] || {ticket:'-', name:'-'};
        document.getElementById('mdTicket').textContent = p.ticket;
        document.getElementById('mdName').textContent = p.name;
        document.getElementById('selesaiModal').classList.add('open');
        _stopReload = true;
    }
    function closeSelesai(){ document.getElementById('selesaiModal').classList.remove('open'); _stopReload=false; }
    function submitSelesai(status){
        if(!_selId) return;
        var f = document.getElementById('mdForm');
        f.action = SELESAI_URL.replace('__ID__', _selId);
        document.getElementById('mdStatus').value = status;
        f.submit();
    }
    document.addEventListener('keydown', function(e){ if(e.key==='Escape') closeSelesai(); });

    // Jangan reload saat pengumuman suara masih berbunyi (nanti terpotong), atau
    // saat modal Selesai terbuka (supaya pilihan tidak hilang tertimpa reload).
    setInterval(function(){ if(!window.__sayBusy && !_stopReload) location.reload(); }, 15000);
</script>
@endpush
@endsection
