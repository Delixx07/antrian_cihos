@extends('layouts.app')
@section('title', 'Daftar Dokter')

@push('head')
{{-- Cropper.js untuk crop foto 1:1 sebelum upload --}}
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js"></script>
<style>
    .toolbar{display:flex;align-items:center;gap:1rem;margin-bottom:1.2rem;flex-wrap:wrap;}
    .search{position:relative;flex:1;max-width:420px;}
    .search svg{position:absolute;left:.8rem;top:50%;transform:translateY(-50%);color:var(--muted);width:16px;height:16px;}
    .count-badge{margin-left:auto;font-size:.82rem;color:var(--muted);}
    .table-scroll{max-height:600px;overflow:auto;}
    .table-scroll table.data th{position:sticky;top:0;z-index:1;}
    .badge{display:inline-block;padding:.2rem .55rem;border-radius:6px;font-size:.76rem;font-weight:600;background:#eff4ff;color:var(--brand);}
    .badge.spec{background:#f3edff;color:#7c3aed;}
    .pill{display:inline-flex;align-items:center;gap:.35rem;padding:.2rem .6rem;border-radius:999px;font-size:.75rem;font-weight:600;}
    .pill.on{background:#eafaf0;color:#16a34a;} .pill.off{background:#f3f4f6;color:#8a94a6;}
    .pill i{width:7px;height:7px;border-radius:50%;background:currentColor;}
    /* Thumbnail foto */
    .thumb{width:44px;height:44px;border-radius:10px;object-fit:cover;background:#f1f5f9;border:1px solid var(--line);}
    .thumb-ph{width:44px;height:44px;border-radius:10px;display:grid;place-items:center;
        background:linear-gradient(135deg,#dbeafe,#93c5fd);color:#1e3a8a;font-weight:700;font-size:.8rem;}
    .act{width:34px;height:34px;border-radius:8px;border:none;cursor:pointer;display:grid;place-items:center;
        background:#eff4ff;color:var(--brand);transition:.15s;}
    .act:hover{background:var(--brand);color:#fff;}
    .empty{text-align:center;padding:3rem;color:var(--muted);}
    .ok{background:#eafaf0;border:1px solid #b7ebc9;color:#16794a;padding:.7rem 1rem;border-radius:9px;margin-bottom:1rem;font-size:.88rem;}
    .err-flash{background:#fdecec;border:1px solid #f5b5b5;color:#b42318;padding:.7rem 1rem;border-radius:9px;margin-bottom:1rem;font-size:.88rem;}

    /* Modal upload (upgrade) */
    .modal{position:fixed;inset:0;background:rgba(15,23,42,.45);backdrop-filter:blur(3px);display:none;
        align-items:center;justify-content:center;z-index:100;padding:1.5rem;}
    .modal.open{display:flex;}
    .modal-box{background:#fff;border-radius:16px;width:100%;max-width:460px;box-shadow:0 30px 70px rgba(0,0,0,.3);animation:pop .25s ease-out;}
    @keyframes pop{from{opacity:0;transform:translateY(12px) scale(.98);}to{opacity:1;transform:none;}}
    .modal-head{padding:1.3rem 1.5rem;border-bottom:1px solid var(--line);display:flex;align-items:center;gap:.7rem;}
    .modal-head .ic{width:40px;height:40px;border-radius:11px;background:#eff4ff;color:var(--brand);display:grid;place-items:center;flex-shrink:0;}
    .modal-head h3{font-size:1.05rem;font-weight:800;color:var(--ink);}
    .modal-head p{font-size:.78rem;color:var(--muted);}
    .modal-body{padding:1.5rem;text-align:center;}
    .modal-body .dname{font-weight:700;color:var(--ink);font-size:.95rem;}
    .modal-body .dnik{font-size:.78rem;color:var(--muted);margin-top:.15rem;margin-bottom:1.2rem;}

    /* ===== Dropzone (gaya baru) ===== */
    .uploader{width:280px;max-width:100%;margin:0 auto;border-radius:12px;padding:10px;gap:6px;
        background:rgba(0,110,255,.04);display:flex;flex-direction:column;}
    .uploader .up-head{flex:1;border:2px dashed royalblue;border-radius:10px;min-height:150px;
        display:flex;flex-direction:column;align-items:center;justify-content:center;gap:.3rem;cursor:pointer;transition:.15s;}
    .uploader .up-head:hover{background:rgba(0,110,255,.06);}
    .uploader .up-head svg{height:56px;color:royalblue;}
    .uploader .up-head p{color:#334;font-size:.85rem;}
    .uploader .up-foot{background:rgba(0,110,255,.08);height:42px;padding:8px;border-radius:10px;cursor:pointer;
        display:flex;align-items:center;gap:.5rem;color:#334;font-size:.82rem;}
    .uploader .up-foot .fico{height:26px;width:26px;flex-shrink:0;fill:royalblue;background:rgba(70,66,66,.1);
        border-radius:50%;padding:4px;box-shadow:0 2px 12px rgba(0,0,0,.15);}
    .uploader .up-foot .fname{flex:1;text-align:center;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}

    /* ===== Area crop ===== */
    .crop-wrap{display:none;}
    .crop-wrap.show{display:block;}
    .crop-stage{width:100%;max-width:340px;margin:0 auto;background:#0b1220;border-radius:12px;overflow:hidden;}
    .crop-stage img{display:block;max-width:100%;}
    .crop-hint{font-size:.76rem;color:var(--muted);margin-top:.7rem;}
    .crop-actions{display:flex;justify-content:center;gap:.5rem;margin-top:.8rem;}
    .crop-actions button{background:#eef2fb;border:1px solid var(--line);color:#445;padding:.4rem .7rem;
        border-radius:8px;cursor:pointer;font-size:.8rem;display:inline-flex;align-items:center;gap:.3rem;}
    .crop-actions button:hover{background:#e0e8f7;}
    .final-preview{width:120px;height:120px;border-radius:14px;margin:.8rem auto 0;object-fit:cover;
        border:1px solid var(--line);display:none;}
    .modal-foot{padding:1.1rem 1.5rem;border-top:1px solid var(--line);display:flex;justify-content:flex-end;gap:.6rem;background:#fafbfd;}
    .btn-save{background:var(--brand);color:#fff;border:none;padding:.6rem 1.3rem;border-radius:9px;font-weight:600;cursor:pointer;}
    .btn-save:hover{background:#1d4ed8;} .btn-save:disabled{opacity:.5;cursor:not-allowed;}
    .btn-cancel{background:#fff;color:#556;border:1px solid var(--line);padding:.6rem 1.1rem;border-radius:9px;font-weight:600;cursor:pointer;}
</style>
@endpush

@section('content')
    <h1 class="page-title">Daftar Dokter</h1>

    @if (session('ok'))<div class="ok">{{ session('ok') }}</div>@endif
    @if (session('error'))<div class="err-flash">{{ session('error') }}</div>@endif
    @if ($errors->any())<div class="err-flash">{{ $errors->first() }}</div>@endif

    <div class="card" style="padding:1.2rem 1.2rem 0;">
        <div class="toolbar">
            <form method="get" class="search">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                <input class="input" type="text" name="q" value="{{ $q }}" placeholder="Cari nama / kode / spesialisasi…" onchange="this.form.submit()">
            </form>
            <span class="count-badge">{{ count($doctors) }} dokter</span>
        </div>

        <div class="table-scroll">
            <table class="data">
                <thead>
                    <tr>
                        <th style="width:70px;">Foto</th>
                        <th>Kode Dokter</th>
                        <th>Nama Dokter</th>
                        <th>Spesialisasi</th>
                        <th style="width:110px;">Status</th>
                        <th style="width:70px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($doctors as $d)
                        @php
                            $p = $photos[$d->paramedic_id] ?? null;
                            $ini = collect(preg_split('/\s+/', preg_replace('/\b(dr|drg|Sp|Prof|Dr)\.?/i','',$d->paramedic_name)))->filter()->take(2)->map(fn($w)=>mb_substr($w,0,1))->implode('');
                        @endphp
                        <tr>
                            <td>
                                @if ($p)
                                    <img class="thumb" src="{{ $p->url() }}?v={{ $p->updated_at?->timestamp }}" alt="foto">
                                @else
                                    <div class="thumb-ph">{{ strtoupper($ini) ?: 'DR' }}</div>
                                @endif
                            </td>
                            <td><span class="badge">{{ $d->paramedic_code ?: '—' }}</span></td>
                            <td style="font-weight:600;color:var(--ink);">{{ $d->paramedic_name }}</td>
                            <td><span class="badge spec">{{ $d->specialty_name ?: 'General' }}</span></td>
                            <td>
                                @if ($d->is_available)
                                    <span class="pill on"><i></i> Aktif</span>
                                @else
                                    <span class="pill off"><i></i> Nonaktif</span>
                                @endif
                            </td>
                            <td>
                                @php $row = ['id'=>$d->paramedic_id,'name'=>$d->paramedic_name,'code'=>$d->paramedic_code,'photo'=>$p?->url()]; @endphp
                                <button type="button" class="act" title="Edit Foto" onclick="openPhoto({{ \Illuminate\Support\Js::from($row) }})">
                                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="empty">Tidak ada dokter ditemukan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Modal Edit Foto (upgrade) --}}
    <div class="modal" id="photoModal" onclick="if(event.target===this)closePhoto()">
        <div class="modal-box">
            <div class="modal-head">
                <div class="ic"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg></div>
                <div><h3>Edit Foto Dokter</h3><p>Nama file otomatis memakai NIK</p></div>
            </div>
            <form method="post" id="photoForm" enctype="multipart/form-data">
                @csrf @method('PUT')
                <div class="modal-body">
                    <div class="dname" id="pDName"></div>
                    <div class="dnik" id="pDCode"></div>

                    {{-- Dropzone (tahap pilih file) --}}
                    <div class="uploader" id="uploader">
                        <label class="up-head" id="dropZone" for="pFile">
                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M7 10V9C7 6.23858 9.23858 4 12 4C14.7614 4 17 6.23858 17 9V10C19.2091 10 21 11.7909 21 14C21 15.4806 20.1956 16.8084 19 17.5M7 10C4.79086 10 3 11.7909 3 14C3 15.4806 3.8044 16.8084 5 17.5M7 10C7.43285 10 7.84965 10.0688 8.24006 10.1959M12 12V21M12 12L15 15M12 12L9 15" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            <p>Browse file untuk diupload</p>
                            <p style="font-size:.72rem;opacity:.7">JPG, PNG, WEBP — maks 4MB</p>
                        </label>
                        <label class="up-foot" for="pFile">
                            <svg class="fico" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg"><path d="M15.331 6H8.5v20h15V14.154h-8.169z"/><path d="M18.153 6h-.009v5.342H23.5v-.002z"/></svg>
                            <span class="fname" id="fname">Belum ada file</span>
                        </label>
                    </div>

                    {{-- Area crop (tahap crop, muncul setelah pilih file) --}}
                    <div class="crop-wrap" id="cropWrap">
                        <div class="crop-stage"><img id="cropImg" src="" alt="crop"></div>
                        <div class="crop-actions">
                            <button type="button" onclick="cropZoom(0.1)" title="Perbesar">＋ Zoom</button>
                            <button type="button" onclick="cropZoom(-0.1)" title="Perkecil">－ Zoom</button>
                            <button type="button" onclick="cropRotate()" title="Putar">⟳ Putar</button>
                            <button type="button" onclick="cropReset()" title="Ulang">✕ Ganti foto</button>
                        </div>
                        <p class="crop-hint">Geser & atur area — foto akan disimpan {{ 400 }}×{{ 400 }} px (kotak).</p>
                    </div>

                    <input type="file" name="_src" id="pFile" accept="image/*" hidden onchange="onPick(this)">
                    {{-- File hasil crop dikirim di sini (diisi JS). --}}
                    <input type="hidden" name="cropped" id="cropped">
                </div>
                <div class="modal-foot">
                    <button type="button" class="btn-cancel" onclick="closePhoto()">Batal</button>
                    <button type="submit" class="btn-save" id="pSave" disabled>Simpan</button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
        var base = "{{ url('dokter') }}";
        var OUT = 400; // ukuran output (px, kotak 1:1)
        var cropper = null;

        function openPhoto(d){
            document.getElementById('photoForm').action = base + '/' + d.id + '/foto';
            document.getElementById('pDName').textContent = d.name;
            document.getElementById('pDCode').textContent = 'Kode: ' + (d.code || '-');
            resetUpload();
            document.getElementById('photoModal').classList.add('open');
        }
        function closePhoto(){ document.getElementById('photoModal').classList.remove('open'); destroyCropper(); }

        function resetUpload(){
            destroyCropper();
            document.getElementById('pFile').value = '';
            document.getElementById('cropped').value = '';
            document.getElementById('fname').textContent = 'Belum ada file';
            document.getElementById('cropWrap').classList.remove('show');
            document.getElementById('uploader').style.display = 'flex';
            document.getElementById('pSave').disabled = true;
        }
        function cropReset(){ resetUpload(); }

        function onPick(inp){
            var f = inp.files[0]; if(!f) return;
            if(f.size > 4*1024*1024){ alert('Ukuran maksimal 4MB.'); return; }
            document.getElementById('fname').textContent = f.name;
            var url = URL.createObjectURL(f);
            var img = document.getElementById('cropImg');
            img.src = url;
            document.getElementById('uploader').style.display = 'none';
            document.getElementById('cropWrap').classList.add('show');
            destroyCropper();
            cropper = new Cropper(img, {
                aspectRatio: 1, viewMode: 1, dragMode: 'move', autoCropArea: 1,
                background: false, guides: true, responsive: true,
            });
            document.getElementById('pSave').disabled = false;
        }

        function cropZoom(v){ if(cropper) cropper.zoom(v); }
        function cropRotate(){ if(cropper) cropper.rotate(90); }
        function destroyCropper(){ if(cropper){ cropper.destroy(); cropper = null; } }

        // Sebelum submit: ambil hasil crop → dataURL 400x400 → taruh di hidden 'cropped'.
        document.getElementById('photoForm').addEventListener('submit', function(e){
            if(!cropper){ e.preventDefault(); alert('Pilih & atur foto dulu.'); return; }
            var canvas = cropper.getCroppedCanvas({ width: OUT, height: OUT, imageSmoothingQuality: 'high' });
            document.getElementById('cropped').value = canvas.toDataURL('image/jpeg', 0.9);
        });

        // drag & drop ke dropzone
        var dz = document.getElementById('dropZone');
        ['dragover','dragenter'].forEach(function(ev){ dz.addEventListener(ev,function(e){e.preventDefault();dz.style.background='rgba(0,110,255,.1)';});});
        ['dragleave','drop'].forEach(function(ev){ dz.addEventListener(ev,function(e){e.preventDefault();dz.style.background='';});});
        dz.addEventListener('drop', function(e){
            var f = e.dataTransfer.files[0]; if(!f) return;
            var inp=document.getElementById('pFile'); inp.files=e.dataTransfer.files; onPick(inp);
        });
        document.addEventListener('keydown', function(e){ if(e.key==='Escape') closePhoto(); });
    </script>
    @endpush
@endsection
