@extends('layouts.app')
@section('title', 'Banner')

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
    .grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:1.1rem;}
    .b-card{background:#fff;border:1px solid var(--line);border-radius:14px;overflow:hidden;position:relative;}
    .b-card img{width:100%;height:150px;object-fit:cover;background:#f1f5f9;display:block;}
    .b-card .cap{padding:.7rem .9rem;display:flex;align-items:center;justify-content:space-between;gap:.5rem;}
    .b-card .cap b{font-size:.88rem;color:var(--ink);}
    .b-del{background:#fdecec;color:#dc2626;border:none;width:32px;height:32px;border-radius:8px;cursor:pointer;display:grid;place-items:center;}
    .b-del:hover{background:#dc2626;color:#fff;}
    .empty{grid-column:1/-1;text-align:center;padding:3rem;color:var(--muted);}
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
    .b-target{padding:0 .9rem .7rem;display:flex;align-items:center;gap:.35rem;flex-wrap:wrap;font-size:.74rem;color:var(--muted);}
    .b-target .tg{background:#eef2ff;color:#4338ca;border-radius:6px;padding:.1rem .45rem;font-weight:600;}
    .b-target .tg.all{background:#dcfce7;color:#15803d;}
    .btn-edit-cl{border:1px solid var(--line);background:#fff;color:#556;border-radius:7px;padding:.25rem .6rem;font-size:.74rem;font-weight:600;cursor:pointer;}
    .btn-edit-cl:hover{border-color:var(--brand);color:var(--brand);}
    .cl-modal{position:fixed;inset:0;background:rgba(15,23,42,.5);backdrop-filter:blur(4px);display:none;align-items:center;justify-content:center;z-index:100;padding:1.5rem;}
    .cl-modal.open{display:flex;}
    .cl-box{background:#fff;border-radius:16px;width:100%;max-width:560px;max-height:85vh;overflow:auto;box-shadow:0 30px 80px rgba(0,0,0,.35);}
    .cl-box .h{padding:1.2rem 1.4rem;border-bottom:1px solid var(--line);font-weight:800;color:var(--ink);}
    .cl-box .b{padding:1.2rem 1.4rem;}
    .cl-box .f{padding:1rem 1.4rem;border-top:1px solid var(--line);display:flex;justify-content:flex-end;gap:.6rem;}
    .cl-box .f button{border:none;border-radius:9px;padding:.6rem 1.2rem;font-weight:700;font-size:.86rem;cursor:pointer;}
    .cl-cancel{background:#f1f5f9;color:#475569;} .cl-save{background:var(--brand);color:#fff;}
</style>
@endpush

@section('content')
    <h1 class="page-title">Banner</h1>
    <p style="color:var(--muted);font-size:.88rem;margin-top:-.6rem;margin-bottom:1.2rem;">Gambar promo yang tampil bergantian di layar tunggu.</p>

    @if (session('ok'))<div class="ok">{{ session('ok') }}</div>@endif
    @if ($errors->any())<div class="err-flash">{{ $errors->first() }}</div>@endif

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
                @include('partials.clinic-picker', ['clinics' => $clinics, 'selected' => [], 'uid' => 'new'])
            </div>
            <button type="submit" class="btn-primary" style="margin-top:.9rem;">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
                Upload
            </button>
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
                @php $codes = $targets[$b->id] ?? []; @endphp
                <div class="b-target">
                    @if (empty($codes))
                        <span class="tg all">Semua Klinik</span>
                    @else
                        @foreach ($clinics->whereIn('service_unit_code', $codes)->take(2) as $c)
                            <span class="tg">{{ $c->service_unit_name }}</span>
                        @endforeach
                        @if (count($codes) > 2)<span class="tg">+{{ count($codes) - 2 }}</span>@endif
                    @endif
                    <button type="button" class="btn-edit-cl"
                        onclick='openClinicModal({{ $b->id }}, @json($b->nama), @json(array_values($codes)))'>Ubah</button>
                </div>
            </div>
        @empty
            <div class="empty">Belum ada banner. Upload gambar di atas.</div>
        @endforelse
    </div>

    {{-- Modal edit target klinik --}}
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
        var CL_BASE = @json(url('/banner'));
        function openClinicModal(id, nama, codes){
            var f=document.getElementById('clForm');
            f.action = CL_BASE + '/' + id + '/klinik';
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
