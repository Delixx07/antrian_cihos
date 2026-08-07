@extends('layouts.app')
@section('title', 'Media Layar Tunggu')

@push('head')
<style>
    .ok{background:#eafaf0;border:1px solid #b7ebc9;color:#16794a;padding:.7rem 1rem;border-radius:9px;margin-bottom:1rem;font-size:.88rem;}
    .err-flash{background:#fdecec;border:1px solid #f5b5b5;color:#b42318;padding:.7rem 1rem;border-radius:9px;margin-bottom:1rem;font-size:.88rem;}
    .up-card{background:#fff;border:1px solid var(--line);border-radius:14px;padding:1.2rem 1.4rem;margin-bottom:1.4rem;}
    .up-form{display:flex;gap:.8rem;flex-wrap:wrap;align-items:flex-end;}
    .fg{display:flex;flex-direction:column;gap:.35rem;}
    .fg label{font-size:.8rem;font-weight:600;color:#556;}
    .fg input{padding:.6rem .8rem;border:1.5px solid var(--line);border-radius:9px;font-size:.9rem;outline:none;}
    .fg input:focus{border-color:var(--brand);box-shadow:0 0 0 3px #2563eb1c;}
    .btn-primary{display:inline-flex;align-items:center;gap:.4rem;background:var(--brand);color:#fff;border:none;padding:.65rem 1.1rem;border-radius:9px;font-size:.88rem;font-weight:600;cursor:pointer;}
    .btn-primary:hover{background:#1d4ed8;}
    .hint{font-size:.72rem;color:var(--muted);}
    .grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:1.1rem;}
    /* Video card */
    .v-card{background:#fff;border:1px solid var(--line);border-radius:14px;overflow:hidden;}
    .v-card.active{border-color:#16a34a;box-shadow:0 0 0 3px #16a34a1c;}
    .v-card video{width:100%;height:160px;object-fit:cover;background:#0b1220;display:block;}
    .v-body{padding:.8rem .95rem;}
    .v-body .ttl{font-weight:700;color:var(--ink);font-size:.9rem;margin-bottom:.5rem;}
    .v-row{display:flex;align-items:center;justify-content:space-between;gap:.5rem;}
    .acts{display:flex;gap:.4rem;}
    .v-del{background:#fdecec;color:#dc2626;border:none;width:30px;height:30px;border-radius:8px;cursor:pointer;display:grid;place-items:center;}
    .v-del:hover{background:#dc2626;color:#fff;}
    /* Banner card */
    .b-card{background:#fff;border:1px solid var(--line);border-radius:14px;overflow:hidden;position:relative;}
    .b-card img{width:100%;height:150px;object-fit:cover;background:#f1f5f9;display:block;}
    .b-card .cap{padding:.7rem .9rem;display:flex;align-items:center;justify-content:space-between;gap:.5rem;}
    .b-card .cap b{font-size:.88rem;color:var(--ink);}
    .b-del{background:#fdecec;color:#dc2626;border:none;width:32px;height:32px;border-radius:8px;cursor:pointer;display:grid;place-items:center;}
    .b-del:hover{background:#dc2626;color:#fff;}
    .empty{grid-column:1/-1;text-align:center;padding:3rem;color:var(--muted);}
    /* Tabs */
    .tabs{display:flex;gap:.4rem;margin-bottom:1.4rem;border-bottom:1px solid var(--line);}
    .tab{border:none;background:none;padding:.8rem 1.3rem;font-size:.95rem;font-weight:700;color:var(--muted);cursor:pointer;
        border-bottom:3px solid transparent;margin-bottom:-1px;display:inline-flex;align-items:center;gap:.5rem;}
    .tab:hover{color:var(--brand);}
    .tab.on{color:var(--brand);border-bottom-color:var(--brand);}
    .tab .cnt{background:#eef2ff;color:#4338ca;font-size:.72rem;font-weight:700;padding:.1rem .5rem;border-radius:999px;}
    .pane{display:none;} .pane.on{display:block;}
    /* Clinic picker */
    .cp{margin-top:.4rem;}
    .cp-mode{display:flex;align-items:center;gap:.6rem;padding:.5rem .1rem;font-size:.86rem;color:#445;cursor:pointer;}
    .cp-mode input{accent-color:var(--brand);width:16px;height:16px;}
    .cp-list{margin-top:.6rem;border:1px solid var(--line);border-radius:11px;padding:.8rem;background:#fafbfd;}
    .cp-list.off{display:none;}
    .cp-tools{display:flex;gap:.5rem;margin-bottom:.6rem;}
    .cp-mini{border:1px solid var(--line);background:#fff;color:#556;border-radius:7px;padding:.3rem .7rem;font-size:.76rem;font-weight:600;cursor:pointer;}
    .cp-mini:hover{border-color:var(--brand);color:var(--brand);}
    .cp-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:.3rem .8rem;max-height:220px;overflow:auto;}
    .cp-item{display:flex;align-items:center;gap:.5rem;font-size:.83rem;color:#334;padding:.3rem .2rem;cursor:pointer;border-radius:6px;}
    .cp-item:hover{background:#eff4ff;}
    .cp-item input{accent-color:var(--brand);width:15px;height:15px;flex-shrink:0;}
    .v-target,.b-target{font-size:.74rem;color:var(--muted);display:flex;align-items:center;gap:.35rem;flex-wrap:wrap;}
    .v-target{margin:.4rem 0 .2rem;} .b-target{padding:0 .9rem .7rem;}
    .b-toggle{padding:0 .9rem .8rem;border-top:1px solid var(--line);padding-top:.7rem;}
    .tg{background:#eef2ff;color:#4338ca;border-radius:6px;padding:.1rem .45rem;font-weight:600;}
    .tg.all{background:#dcfce7;color:#15803d;}
    .btn-edit-cl{border:1px solid var(--line);background:#fff;color:#556;border-radius:7px;padding:.25rem .6rem;font-size:.74rem;font-weight:600;cursor:pointer;}
    .btn-edit-cl:hover{border-color:var(--brand);color:var(--brand);}
    /* Modal edit target */
    .cl-modal{position:fixed;inset:0;background:rgba(15,23,42,.5);backdrop-filter:blur(4px);display:none;align-items:center;justify-content:center;z-index:100;padding:1.5rem;}
    .cl-modal.open{display:flex;}
    .cl-box{background:#fff;border-radius:16px;width:100%;max-width:560px;max-height:85vh;overflow:auto;box-shadow:0 30px 80px rgba(0,0,0,.35);}
    .cl-box .h{padding:1.2rem 1.4rem;border-bottom:1px solid var(--line);font-weight:800;color:var(--ink);}
    .cl-box .b{padding:1.2rem 1.4rem;}
    .cl-box .f{padding:1rem 1.4rem;border-top:1px solid var(--line);display:flex;justify-content:flex-end;gap:.6rem;}
    .cl-box .f button{border:none;border-radius:9px;padding:.6rem 1.2rem;font-weight:700;font-size:.86rem;cursor:pointer;}
    .cl-cancel{background:#f1f5f9;color:#475569;} .cl-save{background:var(--brand);color:#fff;}
</style>
@include('partials.toggle-checkbox-css')
@endpush

@section('content')
    <h1 class="page-title">Media Layar Tunggu</h1>
    <p style="color:var(--muted);font-size:.88rem;margin-top:-.6rem;margin-bottom:1.2rem;">Kelola video &amp; banner promo yang tampil di layar tunggu.</p>

    @if (session('ok'))<div class="ok">{{ session('ok') }}</div>@endif
    @if ($errors->any())<div class="err-flash">{{ $errors->first() }}</div>@endif

    <div class="tabs">
        <button class="tab on" data-pane="video" onclick="showTab('video')">Video <span class="cnt">{{ $videos->count() }}</span></button>
        <button class="tab" data-pane="banner" onclick="showTab('banner')">Banner <span class="cnt">{{ $banners->count() }}</span></button>
    </div>

    {{-- ═══════════════ TAB VIDEO ═══════════════ --}}
    <div class="pane on" id="pane-video">
        <div class="up-card">
            <form method="post" action="{{ route('videos.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="up-form">
                    <div class="fg" style="flex:1;min-width:180px;">
                        <label>Judul Video</label>
                        <input type="text" name="judul" placeholder="mis. Promo Juli 2026" required>
                    </div>
                    <div class="fg" style="flex:1;min-width:180px;">
                        <label>File Video (MP4/WEBM, maks 256MB)</label>
                        <input type="file" name="video" accept="video/mp4,video/webm" required>
                    </div>
                </div>
                <div class="fg" style="margin-top:.9rem;">
                    <label>Tampilkan di Klinik</label>
                    @include('partials.clinic-picker', ['clinics' => $clinics, 'selected' => [], 'uid' => 'newv'])
                </div>
                <button type="submit" class="btn-primary" style="margin-top:.9rem;">＋ Upload Video</button>
            </form>
            <div class="hint" style="margin-top:.5rem;">Upload video besar bisa memakan waktu. Jangan tutup halaman saat proses upload.</div>
        </div>

        <div class="grid">
            @forelse ($videos as $v)
                <div class="v-card {{ $v->is_active ? 'active' : '' }}">
                    <video src="{{ $v->url() }}" preload="metadata" muted controls></video>
                    <div class="v-body">
                        <div class="ttl">{{ $v->judul }}</div>
                        @php $codes = $videoTargets[$v->id] ?? []; @endphp
                        <div class="v-target">
                            @if (empty($codes))<span class="tg all">Semua Klinik</span>
                            @else
                                @foreach ($clinics->whereIn('service_unit_code', $codes)->take(3) as $c)<span class="tg">{{ $c->service_unit_name }}</span>@endforeach
                                @if (count($codes) > 3)<span class="tg">+{{ count($codes) - 3 }}</span>@endif
                            @endif
                            <button type="button" class="btn-edit-cl" onclick='openClinicModal("video", {{ $v->id }}, @json($v->judul), @json(array_values($codes)))'>Ubah</button>
                        </div>
                        <div class="v-row">
                            @include('partials.toggle-checkbox', ['id'=>'vid-'.$v->id, 'checked'=>$v->is_active, 'action'=>route('videos.toggle', $v), 'label'=>$v->is_active?'Aktif (diputar)':'Nonaktif'])
                            <div class="acts">
                                <form method="post" action="{{ route('videos.destroy', $v) }}" onsubmit="return confirm('Hapus video {{ $v->judul }}?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="v-del" title="Hapus">
                                        <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/></svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="empty">Belum ada video. Upload di atas.</div>
            @endforelse
        </div>
    </div>

    {{-- ═══════════════ TAB BANNER ═══════════════ --}}
    <div class="pane" id="pane-banner">
        <div class="up-card">
            <form method="post" action="{{ route('banners.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="up-form">
                    <div class="fg" style="flex:1;min-width:180px;">
                        <label>Nama Banner</label>
                        <input type="text" name="nama" placeholder="mis. Promo Juli" required>
                    </div>
                    <div class="fg" style="flex:1;min-width:180px;">
                        <label>Gambar (JPG/PNG/WEBP, maks 8MB)</label>
                        <input type="file" name="image" accept="image/*" required>
                    </div>
                </div>
                <div class="fg" style="margin-top:.9rem;">
                    <label>Tampilkan di Klinik</label>
                    @include('partials.clinic-picker', ['clinics' => $clinics, 'selected' => [], 'uid' => 'newb'])
                </div>
                <button type="submit" class="btn-primary" style="margin-top:.9rem;">＋ Upload Banner</button>
            </form>
        </div>

        <div class="grid">
            @forelse ($banners as $b)
                <div class="b-card">
                    <img src="{{ $b->url() }}?v={{ $b->updated_at?->timestamp }}" alt="{{ $b->nama }}">
                    <div class="cap">
                        <b>{{ $b->nama }}</b>
                        <form method="post" action="{{ route('banners.destroy', $b) }}" onsubmit="return confirm('Hapus banner {{ $b->nama }}?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="b-del" title="Hapus">
                                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/></svg>
                            </button>
                        </form>
                    </div>
                    @php $codes = $bannerTargets[$b->id] ?? []; @endphp
                    <div class="b-target">
                        @if (empty($codes))<span class="tg all">Semua Klinik</span>
                        @else
                            @foreach ($clinics->whereIn('service_unit_code', $codes)->take(2) as $c)<span class="tg">{{ $c->service_unit_name }}</span>@endforeach
                            @if (count($codes) > 2)<span class="tg">+{{ count($codes) - 2 }}</span>@endif
                        @endif
                        <button type="button" class="btn-edit-cl" onclick='openClinicModal("banner", {{ $b->id }}, @json($b->nama), @json(array_values($codes)))'>Ubah</button>
                    </div>
                    <div class="b-toggle">
                        @include('partials.toggle-checkbox', [
                            'id'      => 'ban-'.$b->id,
                            'checked' => $b->is_active,
                            'action'  => route('banners.toggle', $b),
                            'label'   => $b->is_active ? 'Aktif (tampil)' : 'Nonaktif',
                        ])
                    </div>
                </div>
            @empty
                <div class="empty">Belum ada banner. Upload gambar di atas.</div>
            @endforelse
        </div>
    </div>

    {{-- Modal edit target klinik (dipakai video & banner) --}}
    <div class="cl-modal" id="clModal" onclick="if(event.target===this)closeClinicModal()">
        <div class="cl-box">
            <div class="h" id="clTitle">Tampilkan di Klinik</div>
            <form method="post" id="clForm">
                @csrf @method('PUT')
                <div class="b">
                    @include('partials.clinic-picker', ['clinics' => $clinics, 'selected' => [], 'uid' => 'edit'])
                </div>
                <div class="f">
                    <button type="button" class="cl-cancel" onclick="closeClinicModal()">Batal</button>
                    <button type="submit" class="cl-save">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
        var VIDEO_BASE = @json(url('/video'));
        var BANNER_BASE = @json(url('/banner'));

        function showTab(name){
            document.querySelectorAll('.tab').forEach(t=>t.classList.toggle('on', t.dataset.pane===name));
            document.querySelectorAll('.pane').forEach(p=>p.classList.toggle('on', p.id==='pane-'+name));
            try{ localStorage.setItem('mediaTab', name); }catch(e){}
        }
        (function(){ try{ var t=localStorage.getItem('mediaTab'); if(t) showTab(t); }catch(e){} })();

        function openClinicModal(type, id, nama, codes){
            var f=document.getElementById('clForm');
            f.action = (type==='video'?VIDEO_BASE:BANNER_BASE) + '/' + id + '/klinik';
            document.getElementById('clTitle').textContent = 'Tampilkan di Klinik — ' + nama;
            var pick = codes.length > 0;
            f.querySelector('input[name=clinic_mode][value=all]').checked = !pick;
            f.querySelector('input[name=clinic_mode][value=pick]').checked = pick;
            f.querySelector('.cp-list').classList.toggle('off', !pick);
            f.querySelectorAll('input[name="clinics[]"]').forEach(function(cb){ cb.checked = codes.indexOf(cb.value) >= 0; });
            document.getElementById('clModal').classList.add('open');
        }
        function closeClinicModal(){ document.getElementById('clModal').classList.remove('open'); }
        function cpToggle(radio){ radio.closest('.cp').querySelector('.cp-list').classList.toggle('off', radio.value !== 'pick'); }
        function cpAll(btn, on){ btn.closest('.cp-list').querySelectorAll('input[name="clinics[]"]').forEach(function(cb){ cb.checked = on; }); }
        document.addEventListener('keydown', function(e){ if(e.key==='Escape') closeClinicModal(); });
    </script>
    @endpush
@endsection
