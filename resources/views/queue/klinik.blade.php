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
                            <form method="post" action="{{ route('queue.selesai', $current) }}">@csrf @method('PUT')
                                <button class="b-done" type="submit">✔ Selesai</button></form>
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
                    <div class="kq-inforow"><span>Nomor Berikutnya</span><span class="chip blue">{{ $berikutnya->no_antrian ?? '-' }}</span></div>
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
                        <div class="kq-row {{ $a->is_booking ? 'locked' : '' }}">
                            <span class="tk">{{ $a->no_antrian }}</span>
                            <div class="who">
                                <div class="nm">{{ $a->pasien_nama }}</div>
                                <div class="rm">No. RM {{ $a->pasien_nomrn ?: '-' }}</div>
                            </div>
                            @if ($a->is_booking)
                                <span class="kq-lock" title="Pasien sudah punya jadwal, tapi belum check-in di pendaftaran">⏳ Belum Check-in</span>
                            @else
                                <form method="post" action="{{ route('queue.panggil-satu', $a) }}">@csrf @method('PUT')
                                    <button type="submit" class="kq-btn call">▶ Panggil</button></form>
                            @endif
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
                                <div class="rm">No. RM {{ $a->pasien_nomrn ?: '-' }}</div>
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

    @endif {{-- /$paramedicId --}}
</div>

@push('scripts')
<script>
    // Jangan reload saat pengumuman suara masih berbunyi (nanti terpotong).
    // Muat ulang tiap 8 dtk supaya pasien yang baru registrasi cepat muncul.
    //
    // Dulu ini langsung location.reload() tiap 8 dtk - begitu session sempat
    // "nyangkut" sebentar (mis. baris session digilas GC lottery, atau DB
    // sempat hiccup), reload berikutnya kena redirect middleware ke /login dan
    // petugas merasa "logout sendiri" tanpa peringatan. Sekarang kita cek dulu
    // lewat fetch(redirect:'manual') - reload NYATA hanya dijalankan kalau
    // servernya benar masih menganggap kita login; sebuah redirect harus
    // terkonfirmasi beberapa kali berturut-turut dulu sebelum kita percaya itu
    // logout sungguhan, baru diarahkan ke /login.
    var _authFails = 0;
    var LOGIN_URL = @json(route('login'));

    function showReconnecting(show){
        var el = document.getElementById('kqReconnect');
        if (show && !el) {
            el = document.createElement('div');
            el.id = 'kqReconnect';
            el.className = 'kq-flash err';
            el.textContent = '⚠ Koneksi ke server terputus sesaat, mencoba lagi…';
            document.querySelector('.kq').prepend(el);
        } else if (!show && el) {
            el.remove();
        }
    }

    function safeRefresh(){
        if (window.__sayBusy) return;

        fetch(window.location.href, { credentials: 'same-origin', redirect: 'manual', cache: 'no-store' })
            .then(function (res) {
                if (res.type === 'opaqueredirect') {
                    // Server mengarahkan (redirect) - kemungkinan session habis.
                    // Minta konfirmasi beberapa kali sebelum benar2 dianggap logout.
                    _authFails++;
                    showReconnecting(true);
                    if (_authFails >= 3) {
                        location.href = LOGIN_URL;
                    }
                    return;
                }
                if (!res.ok) {
                    // 5xx/dll - gangguan sementara, jangan reload ke halaman error.
                    showReconnecting(true);
                    return;
                }
                _authFails = 0;
                showReconnecting(false);
                location.reload();
            })
            .catch(function () {
                // Gangguan jaringan sesaat - coba lagi tick berikutnya, jangan paksa logout.
                showReconnecting(true);
            });
    }
    setInterval(safeRefresh, 8000);
</script>
@endpush
@endsection
