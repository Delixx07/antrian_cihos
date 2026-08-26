@extends('layouts.app')
@section('title', 'Counter Farmasi')
@section('pagehead', 'Farmasi '.($jenis==='racik'?'Racik':'Non-Racik'))
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
                            <span class="badge">{{ $current->farmasi_jenis==='racik'?'Resep Racik':'Resep Non-Racik' }}</span>
                        </div>
                        <div class="kq-acts">
                            <form method="post" action="{{ route('farmasi.ulang', $current) }}">@csrf @method('PUT')
                                <button class="b-recall" type="submit">⟲ Recall</button></form>
                            <form method="post" action="{{ route('farmasi.selesai', $current) }}">@csrf @method('PUT')
                                <button class="b-done" type="submit">✔ Selesai</button></form>
                        </div>
                    @else
                        <div class="kq-idle">
                            <div class="kq-idle-ic">💊</div>
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

        {{-- ── Kolom kanan: Antrian Menunggu + Sedang Dilayani + Riwayat ── --}}
        <div>
            <div class="kq-panel">
                <div class="kq-panel-h"><h3>Antrian Menunggu</h3><span class="cnt">{{ $menunggu->count() }}</span>
                    <span class="kq-live" style="margin-left:auto;"><span class="dot"></span> Live</span></div>
                @if ($menunggu->count())
                    <div class="kq-hrow"><span class="h-tk">No.</span><span class="h-who">Pasien / Dokter</span>
                        <span>Jenis</span><span>Tunggu</span><span class="h-end">Aksi</span></div>
                @endif
                <div class="kq-list">
                    @forelse ($menunggu as $a)
                        <div class="kq-row">
                            <span class="tk">{{ $a->no_antrian }}</span>
                            <div class="who">
                                <div class="nm">{{ $a->pasien_nama }}</div>
                                <div class="rm">{{ $a->poli_dokter_nama ?: '-' }}</div>
                            </div>
                            <span class="sr resep">{{ $a->farmasi_jenis==='racik'?'Racik':'Non-Racik' }}</span>
                            <span class="waitchip">{{ $a->menitTunggu() }}m</span>
                            <form method="post" action="{{ route('farmasi.panggil-satu', $a) }}">@csrf @method('PUT')
                                <button type="submit" class="kq-btn call">▶ Panggil</button></form>
                        </div>
                    @empty
                        <div class="kq-empty">Tidak ada antrian menunggu.</div>
                    @endforelse
                </div>
            </div>

            <div class="kq-panel">
                <div class="kq-panel-h"><h3>Sedang Dilayani</h3><span class="cnt gray">{{ $dipanggil->count() }}</span></div>
                @if ($dipanggil->count())
                    <div class="kq-hrow"><span class="h-tk">No.</span><span class="h-who">Pasien / Dokter</span>
                        <span>Counter</span><span class="h-end">Aksi</span></div>
                @endif
                <div class="kq-list">
                    @forelse ($dipanggil as $a)
                        <div class="kq-row">
                            <span class="tk">{{ $a->no_antrian }}</span>
                            <div class="who">
                                <div class="nm">{{ $a->pasien_nama }}</div>
                                <div class="rm">{{ $a->poli_dokter_nama ?: '-' }}</div>
                            </div>
                            <span class="waitchip">{{ $a->counter }}</span>
                            @if ($a->counter === $counter)
                                <form method="post" action="{{ route('farmasi.ulang', $a) }}">@csrf @method('PUT')
                                    <button type="submit" class="kq-btn recall">⟲ Recall</button></form>
                                <form method="post" action="{{ route('farmasi.selesai', $a) }}">@csrf @method('PUT')
                                    <button type="submit" class="kq-btn done">✔ Selesai</button></form>
                            @else
                                <span class="kq-otherctr" title="Sedang ditangani counter lain">Counter lain</span>
                            @endif
                        </div>
                    @empty
                        <div class="kq-empty">Belum ada yang dilayani.</div>
                    @endforelse
                </div>
            </div>

            {{-- Riwayat: pasien selesai (bisa dipanggil ulang) --}}
            <div class="kq-panel">
                <div class="kq-panel-h"><h3>Sudah Ditangani</h3><span class="cnt gray">{{ $selesai->count() }}</span></div>
                @if ($selesai->count())
                    <div class="kq-hrow"><span class="h-tk">No.</span><span class="h-who">Pasien / Dokter</span>
                        <span>Jenis</span><span class="h-end">Aksi</span></div>
                @endif
                <div class="kq-list">
                    @forelse ($selesai as $a)
                        <div class="kq-row">
                            <span class="tk">{{ $a->no_antrian }}</span>
                            <div class="who">
                                <div class="nm">{{ $a->pasien_nama }}</div>
                                <div class="rm">{{ $a->poli_dokter_nama ?: '-' }}</div>
                            </div>
                            <span class="sr clear">Selesai</span>
                            <form method="post" action="{{ route('farmasi.panggil-ulang', $a) }}"
                                  data-confirm="Panggil ulang resep <b>{{ $a->no_antrian }}</b> ({{ $a->pasien_nama }}) kembali ke Farmasi?"
                                  data-cf-title="Panggil Ulang Resep?" data-cf-icon="⟲"
                                  data-cf-yescolor="#f59e0b" data-cf-yeslabel="Ya, Panggil Ulang">@csrf @method('PUT')
                                <button type="submit" class="kq-btn recall">⟲ Panggil Ulang</button></form>
                        </div>
                    @empty
                        <div class="kq-empty">Belum ada resep selesai.</div>
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
