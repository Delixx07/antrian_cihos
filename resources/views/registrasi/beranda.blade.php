@extends('layouts.app')
@section('title', 'Counter Registrasi')
@section('pagehead', 'Counter Registrasi')
@section('pagesub', strtoupper($counter).' · '.now()->translatedFormat('l, d F Y'))

@push('head')
    @include('partials.queue-console-css')
    <style>.kq{--kq-g1:#2563eb;--kq-g2:#1e40af;--kq-shadow:rgba(37,99,235,.28);--kq-brand:#1d4ed8;}</style>
@endpush

@php
    // RG walk-in murni belum tentu punya poli/dokter.
    $tujuan = fn ($r) => $r->poli_nama || $r->paramedic_name
        ? trim(($r->poli_nama ?: '-').' · '.($r->paramedic_name ?: '-'))
        : '-';
@endphp

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
                            <div class="num">{{ $current->rg_no }}</div>
                            <div class="pt">{{ $tujuan($current) }}</div>
                        </div>
                        <div class="kq-acts">
                            <form method="post" action="{{ route('registrasi.ulang', $current) }}">@csrf @method('PUT')
                                <button class="b-recall" type="submit">⟲ Recall</button></form>
                            <form method="post" action="{{ route('registrasi.selesai', $current) }}">@csrf @method('PUT')
                                <button class="b-done" type="submit">✔ Selesai</button></form>
                        </div>
                    @else
                        <div class="kq-idle">
                            <div class="kq-idle-ic">📋</div>
                            <div class="kq-idle-t">Tidak ada pasien dilayani</div>
                            <div class="kq-idle-s">Panggil pasien dari antrian di samping.</div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="kq-panel">
                <div class="kq-panel-h"><h3>Ringkasan</h3></div>
                <div class="kq-panel-b" style="padding-top:.4rem;padding-bottom:.4rem;">
                    <div class="kq-inforow"><span>RG Berikutnya</span><span class="chip blue">{{ $berikutnya->rg_no ?? '-' }}</span></div>
                    <div class="kq-inforow"><span>Sisa Antrian</span><span class="chip amber">{{ $sisa }}</span></div>
                </div>
            </div>
        </div>

        {{-- ── Kolom kanan: Antrian Menunggu + Sedang Dipanggil + Riwayat ── --}}
        <div>
            <div class="kq-panel">
                <div class="kq-panel-h"><h3>Antrian Menunggu</h3><span class="cnt">{{ $menunggu->count() }}</span>
                    <span class="kq-live" style="margin-left:auto;"><span class="dot"></span> Live</span></div>
                <div class="kq-list">
                    @forelse ($menunggu as $r)
                        <div class="kq-row">
                            <span class="tk">{{ $r->rg_no }}</span>
                            <div class="who">
                                <div class="nm">{{ $tujuan($r) }}</div>
                            </div>
                            <form method="post" action="{{ route('registrasi.panggil', $r) }}">@csrf @method('PUT')
                                <button type="submit" class="kq-btn call">▶ Panggil</button></form>
                        </div>
                    @empty
                        <div class="kq-empty">Tidak ada antrian menunggu.</div>
                    @endforelse
                </div>
            </div>

            <div class="kq-panel">
                <div class="kq-panel-h"><h3>Sedang Dipanggil</h3><span class="cnt gray">{{ $dipanggil->count() }}</span></div>
                <div class="kq-list">
                    @forelse ($dipanggil as $r)
                        <div class="kq-row">
                            <span class="tk">{{ $r->rg_no }}</span>
                            <div class="who">
                                <div class="nm">{{ $tujuan($r) }}</div>
                            </div>
                            <span class="waitchip">{{ $r->counter }}</span>
                            @if ($r->counter === $counter)
                                <form method="post" action="{{ route('registrasi.ulang', $r) }}">@csrf @method('PUT')
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

            {{-- Riwayat: RG yang sudah ditandai selesai (bisa dipanggil ulang) --}}
            <div class="kq-panel">
                <div class="kq-panel-h"><h3>Sudah Ditangani</h3><span class="cnt gray">{{ $selesai->count() }}</span></div>
                <div class="kq-list">
                    @forelse ($selesai as $r)
                        <div class="kq-row">
                            <span class="tk">{{ $r->rg_no }}</span>
                            <div class="who">
                                <div class="nm">{{ $tujuan($r) }}</div>
                            </div>
                            <span class="sr clear">Selesai</span>
                            <form method="post" action="{{ route('registrasi.panggil', $r) }}"
                                  data-confirm="Panggil ulang <b>{{ $r->rg_no }}</b> kembali ke Registrasi?"
                                  data-cf-title="Panggil Ulang RG?" data-cf-icon="⟲"
                                  data-cf-yescolor="#f59e0b" data-cf-yeslabel="Ya, Panggil Ulang">@csrf @method('PUT')
                                <button type="submit" class="kq-btn recall">⟲ Panggil Ulang</button></form>
                        </div>
                    @empty
                        <div class="kq-empty">Belum ada RG selesai.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Jangan reload saat pengumuman suara masih berbunyi (nanti terpotong).
setInterval(function(){ if(!window.__sayBusy) location.reload(); }, 15000);
</script>
@endpush
@endsection
