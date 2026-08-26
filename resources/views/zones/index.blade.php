@extends('layouts.app')
@section('title', 'Zona Klinik')

@push('head')
<style>
    .toolbar{display:flex;align-items:center;gap:1rem;margin-bottom:1.2rem;flex-wrap:wrap;}
    .count-badge{font-size:.82rem;color:var(--muted);}
    .badge{display:inline-block;padding:.2rem .55rem;border-radius:6px;font-size:.76rem;font-weight:600;background:#eff4ff;color:var(--brand);}
    .act{width:34px;height:34px;border-radius:8px;border:none;cursor:pointer;display:grid;place-items:center;transition:.15s;}
    .act.edit{background:#eff4ff;color:var(--brand);} .act.edit:hover{background:var(--brand);color:#fff;}
    .act.del{background:#fdecec;color:#dc2626;} .act.del:hover{background:#dc2626;color:#fff;}
    .empty{text-align:center;padding:3rem;color:var(--muted);}
    .ok{background:#eafaf0;border:1px solid #b7ebc9;color:#16794a;padding:.7rem 1rem;border-radius:9px;margin-bottom:1rem;font-size:.88rem;}
    .err-flash{background:#fdecec;border:1px solid #f5b5b5;color:#b42318;padding:.7rem 1rem;border-radius:9px;margin-bottom:1rem;font-size:.88rem;}
    .btn-primary{display:inline-flex;align-items:center;gap:.4rem;background:var(--brand);color:#fff;border:none;padding:.6rem 1rem;border-radius:9px;font-size:.88rem;font-weight:600;cursor:pointer;text-decoration:none;}
    .btn-primary:hover{background:#1d4ed8;}

    /* Modal */
    .modal{position:fixed;inset:0;background:rgba(15,23,42,.45);backdrop-filter:blur(3px);display:none;align-items:center;justify-content:center;z-index:100;padding:1.5rem;}
    .modal.open{display:flex;}
    .modal-box{background:#fff;border-radius:16px;width:100%;max-width:560px;max-height:92vh;overflow-y:auto;box-shadow:0 30px 70px rgba(0,0,0,.3);}
    .modal-head{padding:1.3rem 1.5rem;border-bottom:1px solid var(--line);display:flex;align-items:center;gap:.7rem;}
    .modal-head .ic{width:40px;height:40px;border-radius:11px;background:#eff4ff;color:var(--brand);display:grid;place-items:center;flex-shrink:0;}
    .modal-head h3{font-size:1.05rem;font-weight:800;color:var(--ink);}
    .modal-head p{font-size:.78rem;color:var(--muted);}
    .modal-body{padding:1.4rem 1.5rem;}
    .fg{margin-bottom:1rem;}
    .fg label{display:block;font-size:.8rem;font-weight:600;color:#556;margin-bottom:.4rem;}
    .fg input,.fg textarea{width:100%;padding:.65rem .85rem;border:1.5px solid var(--line);border-radius:9px;font-size:.9rem;outline:none;transition:.15s;background:#fff;font-family:inherit;}
    .fg input:focus,.fg textarea:focus{border-color:var(--brand);box-shadow:0 0 0 3px #2563eb1c;}
    .fg textarea{resize:vertical;min-height:110px;font-family:'Courier New',monospace;font-size:.85rem;}
    .fg-row{display:flex;gap:.8rem;}
    .fg-row .fg{flex:1;}
    .hint{font-size:.72rem;color:var(--muted);margin-top:.3rem;}
    .warn{background:#fff7ed;border:1px solid #fed7aa;color:#9a3412;padding:.65rem .85rem;border-radius:9px;
        font-size:.78rem;line-height:1.5;margin:-.3rem 0 1rem;}
    .warn code{background:#ffedd5;padding:.05rem .35rem;border-radius:5px;font-size:.85em;}

    /* Pasangan Ruang - baris dinamis */
    .pairs{display:flex;flex-direction:column;gap:.5rem;}
    .pr-row{display:flex;align-items:center;gap:.5rem;}
    .pr-in{flex:1;padding:.55rem .7rem;border:1.5px solid var(--line);border-radius:8px;font-size:.85rem;outline:none;transition:.15s;background:#fff;}
    .pr-in:focus{border-color:var(--brand);box-shadow:0 0 0 3px #2563eb1c;}
    .pr-plus{color:var(--muted);font-weight:700;flex-shrink:0;}
    .pr-del{width:32px;height:32px;flex-shrink:0;border:none;border-radius:8px;background:#fdecec;color:#dc2626;
        cursor:pointer;display:grid;place-items:center;font-size:.85rem;transition:.15s;}
    .pr-del:hover{background:#dc2626;color:#fff;}
    .pr-empty{font-size:.78rem;color:var(--muted);padding:.6rem 0;}
    .pr-add{margin-top:.6rem;border:1.5px dashed var(--line);background:#fafbfd;color:var(--brand);
        border-radius:8px;padding:.5rem .9rem;font-size:.82rem;font-weight:600;cursor:pointer;width:100%;transition:.15s;}
    .pr-add:hover{border-color:var(--brand);background:#eff4ff;}
    .modal-foot{padding:1.1rem 1.5rem;border-top:1px solid var(--line);display:flex;justify-content:flex-end;gap:.6rem;background:#fafbfd;}
    .btn-save{background:var(--brand);color:#fff;border:none;padding:.6rem 1.3rem;border-radius:9px;font-weight:600;cursor:pointer;}
    .btn-save:hover{background:#1d4ed8;}
    .btn-cancel{background:#fff;color:#556;border:1px solid var(--line);padding:.6rem 1.1rem;border-radius:9px;font-weight:600;cursor:pointer;}
</style>
@endpush

@section('content')
    <h1 class="page-title">Zona Klinik</h1>
    <p style="color:var(--muted);font-size:.88rem;margin-top:-.6rem;margin-bottom:1.2rem;">
        Nama zona &amp; pasangan ruang yang tampil di kartu "Zona Klinik" pada menu display, dan judul layar saat Main Display dibuka per-zona.
    </p>

    @if (session('ok'))<div class="ok">{{ session('ok') }}</div>@endif
    @if ($errors->any())<div class="err-flash">{{ $errors->first() }}</div>@endif

    <div class="card" style="padding:1.2rem 1.2rem 0;">
        <div class="toolbar">
            <span class="count-badge">{{ $zones->count() }} zona</span>
            <button type="button" class="btn-primary" style="margin-left:auto;" onclick="openAdd()">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
                Tambah Zona
            </button>
        </div>

        <table class="data">
            <thead>
                <tr>
                    <th style="width:90px;">Kode</th>
                    <th>Nama Zona</th>
                    <th style="width:140px;">Pasangan Ruang</th>
                    <th style="width:96px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($zones as $z)
                        <tr>
                            <td><span class="badge">{{ $z->code }}</span></td>
                            <td style="font-weight:600;color:var(--ink);">{{ $z->name }}</td>
                            <td style="color:#556;">
                                @if (empty($z->rooms))
                                    <span style="color:var(--muted);">otomatis dari master</span>
                                @else
                                    {{ count($z->rooms) }} pasang
                                @endif
                            </td>
                            <td>
                                <div style="display:flex;gap:.4rem;">
                                    @php $row = ['id'=>$z->id,'code'=>$z->code,'name'=>$z->name,'sort'=>$z->sort,'rooms_text'=>$z->rooms_text]; @endphp
                                    <button type="button" class="act edit" title="Edit" onclick="openEdit({{ \Illuminate\Support\Js::from($row) }})">
                                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4z"/></svg>
                                    </button>
                                    <form method="post" action="{{ url('zones/'.$z->id) }}" onsubmit="return confirm('Hapus zona {{ $z->code }} - {{ $z->name }}?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="act del" title="Hapus">
                                            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="empty">Belum ada zona. Klik "Tambah Zona".</td></tr>
                    @endforelse
                </tbody>
            </table>
    </div>

    {{-- Modal Tambah/Edit --}}
    <div class="modal" id="zoneModal" onclick="if(event.target===this)closeModal()">
        <div class="modal-box">
            <div class="modal-head">
                <div class="ic"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg></div>
                <div><h3 id="mTitle">Tambah Zona</h3><p>Kode zona = 2 digit awal kode ruang</p></div>
            </div>
            <form method="post" id="zoneForm" onsubmit="syncPairs()">
                @csrf
                <input type="hidden" name="_method" id="mMethod" value="POST">
                <div class="modal-body">
                    <div class="fg-row">
                        <div class="fg" style="max-width:120px;">
                            <label>Kode Zona</label>
                            <input type="text" name="code" id="mCode" placeholder="mis. 11" maxlength="10" required oninput="checkCodeChanged()">
                        </div>
                        <div class="fg">
                            <label>Nama Zona</label>
                            <input type="text" name="name" id="mName" placeholder="mis. Dental, Women & Children clinic" required>
                        </div>
                    </div>
                    <div class="warn" id="codeChangedWarn" style="display:none;">
                        ⚠ Layar TV yang masih memakai kode lama (URL <code>?floor=</code>) akan berhenti cocok begitu disimpan - butuh dibuka ulang manual di TV tersebut.
                    </div>

                    <div class="fg">
                        <label>Pasangan Ruang per Client Display (opsional)</label>
                        <div class="pairs" id="pairsList"></div>
                        <div class="pr-empty" id="pairsEmpty">Belum ada pasangan - ruang akan dipasangkan otomatis (2-2) dari data master.</div>
                        <button type="button" class="pr-add" onclick="addPairRow('', '')">+ Tambah Pasangan Ruang</button>
                        <textarea name="rooms_text" id="mRoomsText" style="display:none;"></textarea>
                        <div class="hint">Satu pasangan = satu layar Client Display. Isi kolom kedua hanya kalau 2 ruang berdampingan berbagi satu layar (mis. Ruang 1102 &amp; 1103 dipanggil di layar yang sama).</div>
                    </div>

                    <div class="fg" style="max-width:140px;">
                        <label>Urutan Tampil</label>
                        <input type="number" name="sort" id="mSort" placeholder="0">
                        <div class="hint">Menentukan urutan kartu zona ini di menu display (angka lebih kecil tampil lebih dulu). Boleh dikosongkan.</div>
                    </div>
                </div>
                <div class="modal-foot">
                    <button type="button" class="btn-cancel" onclick="closeModal()">Batal</button>
                    <button type="submit" class="btn-save">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
        var STORE_URL = "{{ route('zones.store') }}";
        var BASE = "{{ url('zones') }}";
        var originalCode = null; // null = mode Tambah (zona baru, belum ada TV yang pakai kodenya)

        function openAdd(){
            document.getElementById('mTitle').textContent = 'Tambah Zona';
            document.getElementById('zoneForm').action = STORE_URL;
            document.getElementById('mMethod').value = 'POST';
            resetForm();
            originalCode = null;
            document.getElementById('zoneModal').classList.add('open');
        }
        function openEdit(z){
            document.getElementById('mTitle').textContent = 'Edit Zona';
            document.getElementById('zoneForm').action = BASE + '/' + z.id;
            document.getElementById('mMethod').value = 'PUT';
            resetForm();
            document.getElementById('mCode').value = z.code || '';
            document.getElementById('mName').value = z.name || '';
            document.getElementById('mSort').value = z.sort || 0;
            renderPairsFromText(z.rooms_text || '');
            originalCode = z.code || '';
            document.getElementById('zoneModal').classList.add('open');
        }
        function closeModal(){ document.getElementById('zoneModal').classList.remove('open'); }
        function resetForm(){
            document.getElementById('mCode').value='';
            document.getElementById('mName').value='';
            document.getElementById('mSort').value='';
            document.getElementById('codeChangedWarn').style.display='none';
            document.getElementById('pairsList').innerHTML='';
            document.getElementById('mRoomsText').value='';
            togglePairsEmpty();
        }

        // ---- Pasangan Ruang: baris dinamis, disinkronkan ke textarea
        // tersembunyi (rooms_text) yang tetap dipakai backend apa adanya. ----
        function escAttr(s){ return String(s==null?'':s).replace(/"/g,'&quot;'); }
        function pairRowEl(r1, r2){
            var div = document.createElement('div');
            div.className = 'pr-row';
            div.innerHTML =
                '<input type="text" class="pr-in" placeholder="mis. 1101" value="'+escAttr(r1)+'" oninput="syncPairs()">'
                + '<span class="pr-plus">+</span>'
                + '<input type="text" class="pr-in" placeholder="opsional, mis. 1102" value="'+escAttr(r2)+'" oninput="syncPairs()">'
                + '<button type="button" class="pr-del" onclick="removePairRow(this)" title="Hapus pasangan">✕</button>';
            return div;
        }
        function addPairRow(r1, r2){
            document.getElementById('pairsList').appendChild(pairRowEl(r1 || '', r2 || ''));
            togglePairsEmpty();
            syncPairs();
        }
        function removePairRow(btn){
            btn.closest('.pr-row').remove();
            togglePairsEmpty();
            syncPairs();
        }
        function togglePairsEmpty(){
            var has = document.getElementById('pairsList').children.length > 0;
            document.getElementById('pairsEmpty').style.display = has ? 'none' : 'block';
        }
        // Isi ulang baris dari teks "1101\n1102,1103" tersimpan (dipakai openEdit()).
        function renderPairsFromText(text){
            var list = document.getElementById('pairsList');
            list.innerHTML = '';
            (text || '').split('\n').forEach(function(line){
                line = line.trim();
                if (! line) { return; }
                var parts = line.split(',').map(function(p){ return p.trim(); }).filter(function(p){ return p !== ''; });
                if (parts.length) { list.appendChild(pairRowEl(parts[0], parts[1] || '')); }
            });
            togglePairsEmpty();
            syncPairs();
        }
        // Baca semua baris pasangan -> tulis ke textarea rooms_text yang
        // benar-benar dikirim ke server (format sama persis seperti sebelumnya).
        function syncPairs(){
            var lines = [];
            document.querySelectorAll('#pairsList .pr-row').forEach(function(row){
                var ins = row.querySelectorAll('.pr-in');
                var r1 = ins[0].value.trim(), r2 = ins[1].value.trim();
                if (r1 && r2) { lines.push(r1 + ',' + r2); }
                else if (r1) { lines.push(r1); }
            });
            document.getElementById('mRoomsText').value = lines.join('\n');
        }
        // Peringatan cuma muncul saat EDIT (originalCode terisi) & kodenya
        // benar-benar diubah dari nilai semula - zona baru tak perlu peringatan
        // ini karena belum ada layar TV yang bergantung pada kodenya.
        function checkCodeChanged(){
            var changed = originalCode !== null && document.getElementById('mCode').value !== originalCode;
            document.getElementById('codeChangedWarn').style.display = changed ? 'block' : 'none';
        }
        document.addEventListener('keydown', function(e){ if(e.key==='Escape') closeModal(); });
    </script>
    @endpush
@endsection
