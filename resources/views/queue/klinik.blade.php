@extends('layouts.app')
@section('title', 'Antrian Klinik')
@section('pagehead', 'Antrian Klinik')
@section('pagesub', ($doctor?->paramedic_name ?? 'Dokter').' · '.now()->translatedFormat('l, d F Y'))


@push('head')
    @include('partials.queue-console-css')
@endpush

@section('content')
@php $dname = $doctor?->paramedic_name ?? ('Dokter #'.$paramedicId); @endphp

<div class="kq">
    @if (! $paramedicId)
        <div class="kq-flash err">Akun Anda belum terhubung ke seorang dokter. Hubungi administrator.</div>
    @else

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
                        </div>
                        <div class="kq-acts">
                            <form method="post" action="{{ route('queue.ulang', $current) }}">@csrf @method('PUT')
                                <button class="b-recall" type="submit">⟲ Recall</button></form>
                            <form onsubmit="return false;">
                                <button class="b-done" type="button" onclick="openSelesai({{ $current->id }})">✔ Selesai</button></form>
                        </div>
                    @else
                        <div class="kq-idle">
                            <div class="kq-idle-ic">🩺</div>
                            <div class="kq-idle-t">Tidak ada pasien dilayani</div>
                            <div class="kq-idle-s">Panggil pasien dari antrian di samping.</div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="kq-panel">
                <div class="kq-panel-h"><h3>Ringkasan</h3></div>
                <div class="kq-panel-b" style="padding-top:.4rem;padding-bottom:.4rem;">
                    <div class="kq-inforow"><span>Nomor Berikutnya</span><span class="chip blue">{{ $berikutnya->no_antrian ?? '—' }}</span></div>
                    <div class="kq-inforow"><span>Sisa Antrian</span><span class="chip amber">{{ $sisa }}</span></div>
                </div>
            </div>
        </div>

        {{-- ── Kolom kanan: Antrian Menunggu + Riwayat ── --}}
        <div>
            <div class="kq-panel">
                <div class="kq-panel-h"><h3>Antrian Menunggu</h3><span class="cnt">{{ $menunggu->count() }}</span>
                    {{-- Tarik ulang data dari appointment bila pasien baru belum muncul. --}}
                    <form method="post" action="{{ route('queue.sync') }}" style="margin-left:auto;">
                        @csrf
                        <button type="submit" class="kq-sync" title="Tarik pasien terbaru dari appointment">
                            ⟳ Sinkron
                        </button>
                    </form>
                    <span class="kq-live" style="margin-left:.6rem;"><span class="dot"></span> Live</span></div>
                <div class="kq-list">
                    @forelse ($menunggu as $a)
                        <div class="kq-row">
                            <span class="tk">{{ $a->no_antrian }}</span>
                            <div class="who">
                                <div class="nm">{{ $a->pasien_nama }}</div>
                                <div class="rm">No. RM {{ $a->pasien_nomrn ?: '—' }}</div>
                            </div>
                            <form method="post" action="{{ route('queue.panggil-satu', $a) }}">@csrf @method('PUT')
                                <button type="submit" class="kq-btn call">▶ Panggil</button></form>
                        </div>
                    @empty
                        <div class="kq-empty">Tidak ada antrian menunggu.</div>
                    @endforelse
                </div>
            </div>

            <div class="kq-panel">
                <div class="kq-panel-h"><h3>Riwayat Kunjungan</h3><span class="cnt gray">{{ $selesai->count() }}</span></div>
                <div class="kq-list">
                    @forelse ($selesai as $a)
                        <div class="kq-row">
                            <span class="tk">{{ $a->no_antrian }}</span>
                            <div class="who">
                                <div class="nm">{{ $a->pasien_nama }}</div>
                                <div class="rm">No. RM {{ $a->pasien_nomrn ?: '—' }}</div>
                            </div>
                            <span class="sr {{ $a->statusWarna() }}">{{ $a->statusLabel() }}</span>
                            <form method="post" action="{{ route('queue.ulang', $a) }}"
                                  data-confirm="Pasien <b>{{ $a->no_antrian }}</b> ({{ $a->pasien_nama }}) akan dipanggil ulang kembali ke klinik."
                                  data-cf-title="Panggil Ulang Pasien?"
                                  data-cf-icon="⟲" data-cf-icbg="#fef3c7" data-cf-icfg="#d97706"
                                  data-cf-yescolor="#f59e0b" data-cf-yeslabel="Ya, Panggil Ulang">@csrf @method('PUT')
                                <button type="submit" class="kq-btn recall">⟲ Panggil Ulang</button></form>
                        </div>
                    @empty
                        <div class="kq-empty">Belum ada riwayat kunjungan.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- ═══ Modal Selesai (generik) ═══ --}}
    <div class="modal" id="selesaiModal" onclick="if(event.target===this)closeSelesai()">
        <div class="modal-box">
            <div class="modal-head">
                <h3>Selesai Konsultasi</h3>
                <p>Pilih status resep pasien. Setelah ini pasien diteruskan ke Administrasi (Kasir).</p>
                <div class="pt"><span class="tk" id="mdTicket">—</span><span class="nm" id="mdName">—</span></div>
            </div>
            <div class="modal-body">
                <form method="post" id="mdForm" action="#">@csrf @method('PUT')
                    <input type="hidden" name="status_resep" id="mdStatus" value="">
                    <button type="button" class="opt" onclick="submitSelesai('non_resep')">
                        <span class="ic none">✓</span>
                        <span class="tx"><b>Tanpa Resep</b><span>Pasien tidak mendapat resep obat</span></span>
                        <span class="arr">→</span>
                    </button>
                    <button type="button" class="opt" onclick="submitSelesai('racik')">
                        <span class="ic racik">💊</span>
                        <span class="tx"><b>Resep Racik</b><span>Obat racikan — dilayani Farmasi Racik</span></span>
                        <span class="arr">→</span>
                    </button>
                    <button type="button" class="opt" onclick="submitSelesai('non_racik')">
                        <span class="ic nonracik">💊</span>
                        <span class="tx"><b>Resep Non-Racik</b><span>Obat jadi — dilayani Farmasi Non-Racik</span></span>
                        <span class="arr">→</span>
                    </button>
                </form>
            </div>
            <div class="modal-foot"><button type="button" class="btn-cancel" onclick="closeSelesai()">Batal</button></div>
        </div>
    </div>
    @endif {{-- /$paramedicId --}}
</div>

@push('scripts')
<script>
    var PATIENTS = {
        @if($paramedicId)@if($current)"{{ $current->id }}": { ticket: @json($current->no_antrian), name: @json($current->pasien_nama) },@endif @endif
    };
    var SELESAI_URL = @json(url('/antrian').'/__ID__/selesai');
    var _selId = null, _stopReload = false;

    function openSelesai(id){
        _selId = id;
        var p = PATIENTS[id] || {ticket:'—', name:'—'};
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
    // Jangan reload saat pengumuman suara masih berbunyi (nanti terpotong).
    // Muat ulang tiap 8 dtk supaya pasien yang baru registrasi cepat muncul.
    // Ditahan bila modal terbuka (_stopReload) atau pengumuman masih berbunyi.
    setInterval(function(){ if(!_stopReload && !window.__sayBusy) location.reload(); }, 8000);
</script>
@endpush
@endsection
