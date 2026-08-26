@extends('layouts.app')
@section('title', 'Manajemen User')

@push('head')
<style>
    .toolbar{display:flex;align-items:center;gap:1rem;margin-bottom:1.2rem;flex-wrap:wrap;}
    .search{position:relative;flex:1;max-width:420px;}
    .search svg{position:absolute;left:.8rem;top:50%;transform:translateY(-50%);color:var(--muted);width:16px;height:16px;}
    .count-badge{font-size:.82rem;color:var(--muted);}
    .table-scroll{max-height:600px;overflow:auto;}
    .table-scroll table.data th{position:sticky;top:0;z-index:1;}
    .badge{display:inline-block;padding:.2rem .55rem;border-radius:6px;font-size:.76rem;font-weight:600;background:#eff4ff;color:var(--brand);}
    .badge.role{background:#f3edff;color:#7c3aed;}
    .pill{display:inline-flex;align-items:center;gap:.35rem;padding:.2rem .6rem;border-radius:999px;font-size:.75rem;font-weight:600;}
    .pill.on{background:#eafaf0;color:#16a34a;} .pill.off{background:#fdecec;color:#dc2626;}
    .pill i{width:7px;height:7px;border-radius:50%;background:currentColor;}
    .act{width:34px;height:34px;border-radius:8px;border:none;cursor:pointer;display:grid;place-items:center;transition:.15s;}
    .act.edit{background:#eff4ff;color:var(--brand);} .act.edit:hover{background:var(--brand);color:#fff;}
    .act.del{background:#fdecec;color:#dc2626;} .act.del:hover{background:#dc2626;color:#fff;}
    .empty{text-align:center;padding:3rem;color:var(--muted);}
    .pg{display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;padding:1rem 0;}
    .pg-info{font-size:.8rem;color:var(--muted);}
    .pg-btns{display:flex;gap:.5rem;}
    .pg-btn{border:1px solid var(--line);background:#fff;color:var(--brand);border-radius:8px;padding:.45rem .9rem;
        font-size:.82rem;font-weight:600;text-decoration:none;cursor:pointer;transition:.15s;}
    .pg-btn:hover{border-color:var(--brand);background:#eff4ff;}
    .pg-btn.off{color:var(--muted);cursor:default;pointer-events:none;opacity:.5;}
    .ok{background:#eafaf0;border:1px solid #b7ebc9;color:#16794a;padding:.7rem 1rem;border-radius:9px;margin-bottom:1rem;font-size:.88rem;}
    .err-flash{background:#fdecec;border:1px solid #f5b5b5;color:#b42318;padding:.7rem 1rem;border-radius:9px;margin-bottom:1rem;font-size:.88rem;}
    .btn-primary{display:inline-flex;align-items:center;gap:.4rem;background:var(--brand);color:#fff;border:none;padding:.6rem 1rem;border-radius:9px;font-size:.88rem;font-weight:600;cursor:pointer;text-decoration:none;}
    .btn-primary:hover{background:#1d4ed8;}

    /* Modal */
    .modal{position:fixed;inset:0;background:rgba(15,23,42,.45);backdrop-filter:blur(3px);display:none;align-items:center;justify-content:center;z-index:100;padding:1.5rem;}
    .modal.open{display:flex;}
    .modal-box{background:#fff;border-radius:16px;width:100%;max-width:560px;max-height:92vh;overflow-y:auto;box-shadow:0 30px 70px rgba(0,0,0,.3);animation:pop .25s ease-out;}
    @keyframes pop{from{opacity:0;transform:translateY(12px) scale(.98);}to{opacity:1;transform:none;}}
    .modal-head{padding:1.3rem 1.5rem;border-bottom:1px solid var(--line);display:flex;align-items:center;gap:.7rem;}
    .modal-head .ic{width:40px;height:40px;border-radius:11px;background:#eff4ff;color:var(--brand);display:grid;place-items:center;flex-shrink:0;}
    .modal-head h3{font-size:1.05rem;font-weight:800;color:var(--ink);}
    .modal-head p{font-size:.78rem;color:var(--muted);}
    .modal-body{padding:1.4rem 1.5rem;}
    .fg{margin-bottom:1rem;position:relative;}
    .fg label{display:block;font-size:.8rem;font-weight:600;color:#556;margin-bottom:.4rem;}
    .fg input,.fg select{width:100%;padding:.65rem .85rem;border:1.5px solid var(--line);border-radius:9px;font-size:.9rem;outline:none;transition:.15s;background:#fff;}
    .fg input:focus,.fg select:focus{border-color:var(--brand);box-shadow:0 0 0 3px #2563eb1c;}
    .fg input[readonly]{background:#f7f9fc;color:#556;}
    .hint{font-size:.72rem;color:var(--muted);margin-top:.3rem;}
    .modal-foot{padding:1.1rem 1.5rem;border-top:1px solid var(--line);display:flex;justify-content:flex-end;gap:.6rem;background:#fafbfd;}
    .btn-save{background:var(--brand);color:#fff;border:none;padding:.6rem 1.3rem;border-radius:9px;font-weight:600;cursor:pointer;}
    .btn-save:hover{background:#1d4ed8;}
    .btn-cancel{background:#fff;color:#556;border:1px solid var(--line);padding:.6rem 1.1rem;border-radius:9px;font-weight:600;cursor:pointer;}

    /* Autocomplete dropdown (direktori / dokter) */
    .ac{position:absolute;left:0;right:0;top:calc(100% + 4px);background:#fff;border:1px solid var(--line);border-radius:10px;
        box-shadow:0 12px 30px rgba(0,0,0,.14);max-height:240px;overflow:auto;z-index:5;display:none;}
    .ac.open{display:block;}
    .ac-item{padding:.6rem .85rem;cursor:pointer;font-size:.85rem;border-bottom:1px solid #f2f4f8;}
    .ac-item:last-child{border-bottom:none;}
    .ac-item:hover{background:#f5f8ff;}
    .ac-item b{color:var(--ink);} .ac-item span{color:var(--muted);font-size:.76rem;}
    .ac-item.added{opacity:.5;pointer-events:none;}
    .toggle{display:flex;align-items:center;gap:.6rem;}
    .toggle input{width:auto;}
</style>
@endpush

@section('content')
    <h1 class="page-title">Manajemen User</h1>

    @if (session('ok'))<div class="ok">{{ session('ok') }}</div>@endif
    @if (session('error'))<div class="err-flash">{{ session('error') }}</div>@endif
    @if ($errors->any())<div class="err-flash">{{ $errors->first() }}</div>@endif

    <div class="card" style="padding:1.2rem 1.2rem 0;">
        <div class="toolbar">
            <form method="get" class="search">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                <input class="input" type="text" name="q" value="{{ $q }}" placeholder="Cari username / nama…" onchange="this.form.submit()">
            </form>
            <span class="count-badge">{{ $users->total() }} user</span>
            <button type="button" class="btn-primary" style="margin-left:auto;" onclick="openAdd()">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
                Tambah User
            </button>
        </div>

        <div class="table-scroll">
            <table class="data">
                <thead>
                    <tr>
                        <th>Nama User</th>
                        <th>Username</th>
                        <th>Hak Akses</th>
                        <th>Dokter / Counter</th>
                        <th style="width:100px;">Status</th>
                        <th style="width:96px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $u)
                        <tr>
                            <td style="font-weight:600;color:var(--ink);">{{ $u->name ?: '-' }}</td>
                            <td><span class="badge">{{ $u->username }}</span></td>
                            <td><span class="badge role">{{ $roles[$u->role] ?? $u->role }}</span></td>
                            <td style="color:#556;">{{ $u->paramedic_name ?: ($u->counter ?: '-') }}</td>
                            <td>
                                @if ($u->is_blocked)
                                    <span class="pill off"><i></i> Diblokir</span>
                                @else
                                    <span class="pill on"><i></i> Aktif</span>
                                @endif
                            </td>
                            <td>
                                <div style="display:flex;gap:.4rem;">
                                    @php $row = ['id'=>$u->id,'name'=>$u->name,'username'=>$u->username,'role'=>$u->role,'paramedic_id'=>$u->paramedic_id,'paramedic_name'=>$u->paramedic_name,'counter'=>$u->counter,'room_code'=>$u->room_code,'zona'=>$u->zona,'is_blocked'=>(bool)$u->is_blocked]; @endphp
                                    <button type="button" class="act edit" title="Edit" onclick="openEdit({{ \Illuminate\Support\Js::from($row) }})">
                                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4z"/></svg>
                                    </button>
                                    <form method="post" action="{{ url('user/'.$u->id) }}" onsubmit="return confirm('Hapus user {{ $u->username }}?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="act del" title="Hapus">
                                            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="empty">Belum ada user. Klik "Tambah User".</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($users->hasPages())
            <div class="pg">
                <span class="pg-info">Halaman {{ $users->currentPage() }} dari {{ $users->lastPage() }} - menampilkan {{ $users->firstItem() }}–{{ $users->lastItem() }} dari {{ $users->total() }} user</span>
                <div class="pg-btns">
                    @if ($users->previousPageUrl())
                        <a href="{{ $users->previousPageUrl() }}" class="pg-btn">‹ Sebelumnya</a>
                    @else
                        <span class="pg-btn off">‹ Sebelumnya</span>
                    @endif
                    @if ($users->nextPageUrl())
                        <a href="{{ $users->nextPageUrl() }}" class="pg-btn">Berikutnya ›</a>
                    @else
                        <span class="pg-btn off">Berikutnya ›</span>
                    @endif
                </div>
            </div>
        @endif
    </div>

    {{-- Modal Tambah/Edit --}}
    <div class="modal" id="userModal" onclick="if(event.target===this)closeModal()">
        <div class="modal-box">
            <div class="modal-head">
                <div class="ic"><svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/></svg></div>
                <div><h3 id="mTitle">Tambah User</h3><p>Login diverifikasi ke direktori RS (dbuser)</p></div>
            </div>
            <form method="post" id="userForm">
                @csrf
                <input type="hidden" name="_method" id="mMethod" value="POST">
                <div class="modal-body">
                    {{-- Cari user dari direktori RS (dbuser) - hanya saat tambah --}}
                    <div class="fg" id="fgDirectory">
                        <label>Cari User (Direktori RS)</label>
                        <input type="text" id="dirSearch" autocomplete="off" placeholder="Ketik nama / username / NIK…" oninput="searchDir(this.value)">
                        <div class="ac" id="dirAc"></div>
                        <div class="hint">Pilih pegawai dari direktori RS. Username &amp; nama terisi otomatis.</div>
                    </div>

                    <div class="fg">
                        <label>Nama User</label>
                        <input type="text" name="name" id="mName" placeholder="Nama tampilan">
                    </div>
                    <div class="fg">
                        <label>Username</label>
                        <input type="text" name="username" id="mUsername" readonly placeholder="dari direktori RS">
                        <div class="hint">Username tak bisa diganti setelah dibuat.</div>
                    </div>
                    <div class="fg">
                        <label>Password (opsional)</label>
                        <input type="password" name="password" id="mPassword" autocomplete="new-password" placeholder="Kosongkan = login pakai password RS (dbuser)">
                        <div class="hint" id="pwHint">Isi hanya untuk akun lokal (mis. super admin / akun mesin). Kosongkan bila login lewat direktori RS.</div>
                    </div>

                    <div class="fg">
                        <label>Hak Akses</label>
                        <select name="role" id="mRole" onchange="onRole()">
                            @foreach ($roles as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Dokter - hanya untuk role Klinik --}}
                    <div class="fg" id="fgDoctor" style="display:none;">
                        <label>Dokter</label>
                        <input type="text" id="docSearch" autocomplete="off" placeholder="Cari nama dokter…" oninput="searchDoc(this.value)">
                        <div class="ac" id="docAc"></div>
                        <input type="hidden" name="paramedic_id" id="mParamedicId">
                        <div class="hint" id="docPicked" style="display:none;"></div>
                    </div>

                    {{-- Counter - untuk Farmasi/Kasir/Loket/Radiologi (opsional) --}}
                    <div class="fg" id="fgCounter" style="display:none;">
                        <label>Counter / Loket (opsional)</label>
                        <input type="text" name="counter" id="mCounter" placeholder="mis. Counter 1, Farmasi Racik">
                    </div>

                    <div class="fg">
                        <label class="toggle"><input type="checkbox" name="is_blocked" id="mBlocked" value="1"> Blokir User</label>
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
        var DIR_URL = "{{ route('users.search-directory') }}";
        var DOC_URL = "{{ route('users.search-doctors') }}";
        var STORE_URL = "{{ route('users.store') }}";
        var BASE = "{{ url('user') }}";
        var _dirTimer=null, _docTimer=null;

        function openAdd(){
            document.getElementById('mTitle').textContent = 'Tambah User';
            document.getElementById('userForm').action = STORE_URL;
            document.getElementById('mMethod').value = 'POST';
            document.getElementById('fgDirectory').style.display = '';
            document.getElementById('mUsername').readOnly = true;
            resetForm();
            document.getElementById('userModal').classList.add('open');
        }
        function openEdit(u){
            document.getElementById('mTitle').textContent = 'Edit User';
            document.getElementById('userForm').action = BASE + '/' + u.id;
            document.getElementById('mMethod').value = 'PUT';
            document.getElementById('fgDirectory').style.display = 'none'; // username tetap
            resetForm();
            document.getElementById('pwHint').textContent = 'Kosongkan bila tak ingin mengubah password. Isi untuk set/ubah password lokal.';
            document.getElementById('mName').value = u.name || '';
            document.getElementById('mUsername').value = u.username || '';
            document.getElementById('mRole').value = u.role || 'administrator';
            document.getElementById('mParamedicId').value = u.paramedic_id || '';
            document.getElementById('mCounter').value = u.counter || '';
            document.getElementById('mBlocked').checked = !!u.is_blocked;
            if(u.paramedic_name){
                var p=document.getElementById('docPicked'); p.style.display=''; p.textContent='Dokter: '+u.paramedic_name;
                document.getElementById('docSearch').value = u.paramedic_name;
            }
            onRole();
            document.getElementById('userModal').classList.add('open');
        }
        function closeModal(){ document.getElementById('userModal').classList.remove('open'); }
        function resetForm(){
            document.getElementById('mName').value='';
            document.getElementById('mUsername').value='';
            document.getElementById('mPassword').value='';
            document.getElementById('dirSearch').value='';
            document.getElementById('mRole').value='administrator';
            document.getElementById('mParamedicId').value='';
            document.getElementById('docSearch').value='';
            document.getElementById('mCounter').value='';
            document.getElementById('mBlocked').checked=false;
            document.getElementById('docPicked').style.display='none';
            document.getElementById('dirAc').classList.remove('open');
            document.getElementById('docAc').classList.remove('open');
            onRole();
        }

        // Role → tampilkan field yang relevan.
        function onRole(){
            var r = document.getElementById('mRole').value;
            document.getElementById('fgDoctor').style.display = (r === 'klinik') ? '' : 'none';
            var counterRoles = ['farmasi','kasir_administrasi','kasir_farmasi','admisi_rajal_lab','admisi_igd','admisi_radiologi'];
            document.getElementById('fgCounter').style.display = counterRoles.indexOf(r) !== -1 ? '' : 'none';
        }

        // Cari direktori RS.
        function searchDir(q){
            clearTimeout(_dirTimer);
            var ac = document.getElementById('dirAc');
            if(q.trim().length < 2){ ac.classList.remove('open'); return; }
            _dirTimer = setTimeout(function(){
                fetch(DIR_URL + '?q=' + encodeURIComponent(q.trim()))
                    .then(r=>r.json())
                    .then(function(list){
                        if(!Array.isArray(list) || !list.length){ ac.innerHTML='<div class="ac-item" style="color:var(--muted)">Tidak ada hasil.</div>'; ac.classList.add('open'); return; }
                        ac.innerHTML = list.map(function(u){
                            var added = u.added ? ' added' : '';
                            var note = u.added ? ' <span>(sudah ada)</span>' : (u.nik ? ' <span>NIK '+esc(u.nik)+'</span>' : '');
                            return '<div class="ac-item'+added+'" onclick=\'pickDir('+JSON.stringify(u).replace(/'/g,"&#39;")+')\'><b>'+esc(u.name)+'</b> <span>'+esc(u.username)+'</span>'+note+'</div>';
                        }).join('');
                        ac.classList.add('open');
                    }).catch(function(){ ac.classList.remove('open'); });
            }, 250);
        }
        function pickDir(u){
            document.getElementById('mUsername').value = u.username;
            document.getElementById('mName').value = u.name || u.username;
            document.getElementById('dirSearch').value = u.name + ' (' + u.username + ')';
            document.getElementById('dirAc').classList.remove('open');
        }

        // Cari dokter (role Klinik).
        function searchDoc(q){
            clearTimeout(_docTimer);
            var ac = document.getElementById('docAc');
            _docTimer = setTimeout(function(){
                fetch(DOC_URL + '?q=' + encodeURIComponent(q.trim()))
                    .then(r=>r.json())
                    .then(function(list){
                        if(!Array.isArray(list) || !list.length){ ac.classList.remove('open'); return; }
                        ac.innerHTML = list.map(function(d){
                            return '<div class="ac-item" onclick=\'pickDoc('+JSON.stringify(d).replace(/'/g,"&#39;")+')\'><b>'+esc(d.name)+'</b> <span>'+esc(d.code||'')+' · '+esc(d.specialty)+'</span></div>';
                        }).join('');
                        ac.classList.add('open');
                    }).catch(function(){ ac.classList.remove('open'); });
            }, 250);
        }
        function pickDoc(d){
            document.getElementById('mParamedicId').value = d.id;
            document.getElementById('docSearch').value = d.name;
            var p=document.getElementById('docPicked'); p.style.display=''; p.textContent='Dokter: '+d.name+' ('+(d.code||'')+')';
            document.getElementById('docAc').classList.remove('open');
        }

        function esc(s){ return String(s==null?'':s).replace(/[&<>"']/g,function(c){return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c];}); }
        document.addEventListener('keydown', function(e){ if(e.key==='Escape') closeModal(); });
    </script>
    @endpush
@endsection
