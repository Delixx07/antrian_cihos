{{-- CSS bersama konsol antrian (klinik / kasir / farmasi). Dipakai via @include. --}}
<style>
    .kq{--kq-card:#fff;--kq-line:#e6ecf4;--kq-ink:#0f2747;--kq-muted:#64748b;
        --kq-brand:#0056b3;--kq-navy:#003f87;--kq-g1:#0056b3;--kq-g2:#003f87;}
    .kq-head{display:flex;align-items:center;justify-content:space-between;gap:1rem;margin-bottom:1.3rem;flex-wrap:wrap;}
    .kq-head .hi b{font-size:1.15rem;font-weight:800;color:var(--kq-ink);}
    .kq-head .hi span{display:block;font-size:.85rem;color:var(--kq-muted);margin-top:.15rem;}
    .kq-head .hi a{color:var(--kq-brand);text-decoration:none;font-size:.8rem;font-weight:600;}
    .kq-head .hi a:hover{text-decoration:underline;}
    .kq-live{font-size:.75rem;color:#16a34a;display:inline-flex;align-items:center;gap:.4rem;font-weight:600;
        background:#f0fdf4;border:1px solid #bbf7d0;padding:.35rem .75rem;border-radius:999px;}
    .kq-live .dot{width:7px;height:7px;border-radius:50%;background:#16a34a;animation:kqpulse 1.6s infinite;}
    /* Tombol tarik-ulang data dari appointment (manual). */
    .kq-sync{font-size:.75rem;font-weight:700;color:#0b2f6b;background:#eef3fb;
        border:1px solid #cfdcf0;border-radius:8px;padding:.3rem .7rem;cursor:pointer;
        display:inline-flex;align-items:center;gap:.3rem;transition:background .15s;}
    .kq-sync:hover{background:#dfe9f8;}
    .kq-sync:active{transform:translateY(1px);}
    @keyframes kqpulse{0%,100%{opacity:1;}50%{opacity:.25;}}

    .kq-flash{padding:.8rem 1.1rem;border-radius:11px;margin-bottom:1rem;font-size:.88rem;font-weight:500;}
    .kq-flash.ok{background:#ecfdf5;border:1px solid #a7f3d0;color:#047857;}

    /* ===== Layout 2 kolom: kiri panel panggil (sedikit lebih lebar), kanan list ===== */
    .kq-grid{display:grid;grid-template-columns:340px 1fr;gap:1.5rem;align-items:start;}
    @media(max-width:940px){.kq-grid{grid-template-columns:1fr;}}

    /* Panel (kartu putih) — bayangan lembut biar tak terasa kosong/datar */
    .kq-panel{background:var(--kq-card);border:1px solid var(--kq-line);border-radius:16px;overflow:hidden;
        margin-bottom:1.5rem;box-shadow:0 1px 3px rgba(15,39,71,.05),0 8px 24px rgba(15,39,71,.04);}
    .kq-panel-h{padding:1.05rem 1.3rem;border-bottom:1px solid var(--kq-line);display:flex;align-items:center;gap:.55rem;
        background:linear-gradient(180deg,#fbfcfe,#f6f9fd);}
    .kq-panel-h h3{font-size:.98rem;font-weight:800;color:var(--kq-navy);margin:0;letter-spacing:-.01em;}
    .kq-panel-h .cnt{background:#e6f0fb;color:var(--kq-navy);font-size:.74rem;font-weight:700;padding:.15rem .6rem;border-radius:999px;}
    .kq-panel-h .cnt.gray{background:#f1f5f9;color:#64748b;}
    .kq-panel-b{padding:1.2rem 1.3rem;}

    /* Kotak nomor panggilan (di panel kiri) — navy konsisten, lebih menonjol */
    .kq-callbox{border:2px solid var(--kq-navy);border-radius:14px;padding:1.7rem 1rem;text-align:center;
        background:linear-gradient(180deg,#f4f8ff,#e8f0fc);margin-bottom:1.1rem;
        box-shadow:inset 0 1px 0 #fff, 0 6px 18px rgba(0,63,135,.10);}
    .kq-callbox .num{font-size:3.1rem;font-weight:800;color:var(--kq-navy);line-height:1;font-variant-numeric:tabular-nums;letter-spacing:-.02em;}
    .kq-callbox .pt{font-size:.9rem;color:var(--kq-muted);margin-top:.5rem;font-weight:600;}
    .kq-callbox .badge{display:inline-block;margin-top:.6rem;background:#eef2ff;color:#4338ca;border-radius:7px;padding:.2rem .6rem;font-size:.74rem;font-weight:700;}
    .kq-callbox.empty{border-style:dashed;border-color:#cdd7e6;background:#f8fafc;}
    .kq-callbox.empty .num{font-size:1.05rem;color:#94a3b8;font-weight:700;}
    /* Empty-state profesional (tanpa kotak dashed besar) */
    .kq-idle{text-align:center;padding:1.8rem 1rem 1rem;}
    .kq-idle-ic{font-size:2rem;opacity:.55;}
    .kq-idle-t{font-size:.95rem;font-weight:700;color:#64748b;margin-top:.5rem;}
    .kq-idle-s{font-size:.8rem;color:#94a3b8;margin-top:.25rem;}

    /* Tombol besar di panel kiri */
    .kq-acts{display:flex;gap:.55rem;flex-wrap:wrap;}
    .kq-acts form{flex:1;}
    /* Tombol besar panel kiri — sapuan kiri→kanan (fill), warna per-aksi.
       Pola: --bg (dasar), --fg (teks dasar), --fill (warna isi hover), --fgh (teks hover). */
    .kq-acts button{--fg:#212121;--fgh:#fff;width:100%;position:relative;overflow:hidden;z-index:1;border:1.5px solid transparent;
        border-radius:11px;padding:.7rem .9rem;font-weight:700;font-size:.86rem;cursor:pointer;color:var(--fg);
        background:var(--bg,#e8e8e8);display:inline-flex;align-items:center;justify-content:center;gap:.4rem;transition:color .25s;}
    .kq-acts button::before{content:"";position:absolute;top:0;left:0;height:100%;width:0;border-radius:11px;
        background:var(--fill,#212121);z-index:-1;transition:width .25s;}
    .kq-acts button:hover{color:var(--fgh);}
    .kq-acts button:hover::before{width:100%;}
    .kq-acts .b-recall{--bg:#fff;--fg:#d97706;--fill:#f59e0b;--fgh:#fff;border-color:#f59e0b;}
    .kq-acts .b-done{--bg:#e9f9ef;--fg:#15803d;--fill:#16a34a;--fgh:#fff;border-color:#bbe7cb;}
    .kq-hint{font-size:.74rem;color:var(--kq-muted);margin-top:.7rem;line-height:1.5;}

    /* Baris info (panel Informasi) */
    .kq-inforow{display:flex;align-items:center;justify-content:space-between;padding:.65rem 0;border-bottom:1px solid #f1f5f9;font-size:.9rem;color:#475569;}
    .kq-inforow:last-child{border-bottom:none;}
    .kq-inforow .chip{padding:.25rem .7rem;border-radius:8px;font-weight:800;font-size:.85rem;}
    .kq-inforow .chip.blue{background:#e6f0fb;color:var(--kq-navy);} .kq-inforow .chip.amber{background:#fef3c7;color:#b45309;}
    .kq-flash.err{background:#fff7ed;border:1px solid #fed7aa;color:#c2410c;}

    /* Kartu pasien aktif (hero). Warna gradient di-set per-role lewat --kq-g1/--kq-g2. */
    .kq-active{background:linear-gradient(135deg,var(--kq-g1,#2563eb),var(--kq-g2,#1e40af));border-radius:20px;
        padding:1.6rem 1.8rem;color:#fff;display:flex;align-items:center;gap:1.6rem;margin-bottom:1.5rem;
        box-shadow:0 12px 30px var(--kq-shadow,rgba(37,99,235,.28));flex-wrap:wrap;}
    .kq-active .tk{font-size:2.8rem;font-weight:800;line-height:1;letter-spacing:-.02em;font-variant-numeric:tabular-nums;}
    .kq-active .meta{flex:1;min-width:180px;}
    .kq-active .meta .nm{font-size:1.25rem;font-weight:700;}
    .kq-active .meta .sub{font-size:.85rem;opacity:.88;margin-top:.2rem;}
    .kq-active .meta .badge{display:inline-block;margin-top:.5rem;background:rgba(255,255,255,.2);border-radius:7px;
        padding:.2rem .6rem;font-size:.74rem;font-weight:700;}
    .kq-active .acts{display:flex;gap:.7rem;flex-wrap:wrap;}
    .kq-active .acts button{border:none;border-radius:11px;padding:.7rem 1.3rem;font-weight:700;font-size:.9rem;cursor:pointer;
        display:inline-flex;align-items:center;gap:.45rem;transition:.15s;}
    .kq-active .b-recall{background:rgba(255,255,255,.16);color:#fff;}
    .kq-active .b-recall:hover{background:rgba(255,255,255,.28);}
    .kq-active .b-done{background:#fff;color:var(--kq-g2,#1e40af);}
    .kq-active .b-done:hover{transform:translateY(-1px);box-shadow:0 6px 16px rgba(0,0,0,.18);}
    .kq-active.empty{background:#f8fafc;border:2px dashed #d5dded;color:var(--kq-muted);box-shadow:none;
        flex-direction:column;align-items:center;text-align:center;padding:2rem 1.5rem;}
    .kq-active.empty .ttl{font-size:1.15rem;font-weight:800;color:#94a3b8;}
    .kq-active.empty .sub{font-size:.86rem;color:#a3b0c2;margin-top:.35rem;}

    .kq-stats{display:grid;grid-template-columns:repeat(2,1fr);gap:1rem;margin-bottom:1.5rem;}
    .kq-stat{background:var(--kq-card);border:1px solid var(--kq-line);border-radius:14px;padding:1rem 1.2rem;}
    .kq-stat .lb{font-size:.76rem;color:var(--kq-muted);font-weight:600;text-transform:uppercase;letter-spacing:.03em;}
    .kq-stat .vl{font-size:1.6rem;font-weight:800;color:var(--kq-ink);margin-top:.2rem;}
    .kq-stat .vl.brand{color:var(--kq-brand);}

    .kq-sec{background:var(--kq-card);border:1px solid var(--kq-line);border-radius:16px;overflow:hidden;margin-bottom:1.4rem;}
    .kq-sec-h{display:flex;align-items:center;gap:.6rem;padding:1.1rem 1.3rem;border-bottom:1px solid var(--kq-line);}
    .kq-sec-h h3{font-size:.98rem;font-weight:800;color:var(--kq-ink);margin:0;}
    .kq-sec-h .cnt{background:#e6f0fb;color:var(--kq-navy);font-size:.74rem;font-weight:700;padding:.15rem .6rem;border-radius:999px;}
    .kq-sec-h .cnt.gray{background:#f1f5f9;color:#64748b;}

    .kq-list{display:flex;flex-direction:column;}
    /* Baris header kolom (keterangan) */
    .kq-hrow{display:flex;align-items:center;gap:1rem;padding:.55rem 1.3rem;background:#fafbfd;
        border-bottom:1px solid var(--kq-line);font-size:.7rem;font-weight:700;color:#94a3b8;
        text-transform:uppercase;letter-spacing:.04em;}
    .kq-hrow .h-tk{min-width:64px;} .kq-hrow .h-who{flex:1;} .kq-hrow .h-end{text-align:right;}
    .kq-row{display:flex;align-items:center;gap:1rem;padding:.85rem 1.3rem;border-bottom:1px solid #f1f5f9;transition:background .12s;}
    .kq-row:last-child{border-bottom:none;}
    .kq-row:hover{background:#fafbff;}
    .kq-row.locked{opacity:.72;}
    .kq-row .tk{font-weight:800;color:var(--kq-navy);font-size:1rem;min-width:64px;font-variant-numeric:tabular-nums;}
    .kq-row .who{flex:1;min-width:0;}
    .kq-row .who .nm{font-weight:600;color:var(--kq-ink);font-size:.92rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
    .kq-row .who .rm{font-size:.76rem;color:var(--kq-muted);margin-top:.1rem;}
    .kq-row .sr{font-size:.72rem;font-weight:700;padding:.2rem .6rem;border-radius:7px;white-space:nowrap;}
    .kq-row .sr.none{background:#f1f5f9;color:#64748b;}
    .kq-row .sr.resep{background:#fef3c7;color:#b45309;}
    .kq-row .sr.clear{background:#dcfce7;color:#15803d;}
    .kq-row .waitchip{font-size:.72rem;color:#64748b;font-weight:600;font-variant-numeric:tabular-nums;min-width:52px;text-align:right;}
    /* ===== Tombol aksi per-baris — sapuan kiri→kanan (fill) ===== */
    /* Pola: --bg dasar, --fg teks dasar, --fill warna isi hover, --fgh teks hover. */
    .kq-btn{position:relative;overflow:hidden;z-index:1;border:1.5px solid transparent;border-radius:10px;
        padding:.55rem 1.1rem;font-size:.83rem;font-weight:700;cursor:pointer;color:var(--fg,#fff);background:var(--bg);
        display:inline-flex;align-items:center;gap:.4rem;transition:color .25s;white-space:nowrap;}
    .kq-btn::before{content:"";position:absolute;top:0;left:0;height:100%;width:0;border-radius:10px;
        background:var(--fill);z-index:-1;transition:width .25s;}
    .kq-btn:hover{color:var(--fgh,#fff);}
    .kq-btn:hover::before{width:100%;}
    .kq-btn.call{--bg:var(--kq-brand);--fg:#fff;--fill:#1e3a8a;--fgh:#fff;}
    .kq-btn.done{--bg:#16a34a;--fg:#fff;--fill:#166534;--fgh:#fff;}
    .kq-btn.recall{--bg:#fff;--fg:#d97706;--fill:#f59e0b;--fgh:#fff;border-color:#f59e0b;}
    .kq-lock{font-size:.74rem;font-weight:600;color:#9a6a00;background:#fff7ed;border:1px solid #fcd9a0;
        border-radius:9px;padding:.4rem .75rem;display:inline-flex;align-items:center;gap:.35rem;white-space:nowrap;}
    .kq-empty{text-align:center;padding:2.2rem 1rem;color:#94a3b8;font-size:.88rem;}

    @media(max-width:640px){
        .kq-active{flex-direction:column;align-items:flex-start;}
        .kq-row .who .nm{white-space:normal;}
        .kq-stats{grid-template-columns:1fr;}
    }

    /* Modal pilih status resep (dipakai klinik) */
    .modal{position:fixed;inset:0;background:rgba(15,23,42,.5);backdrop-filter:blur(4px);display:none;
        align-items:center;justify-content:center;z-index:100;padding:1.5rem;}
    .modal.open{display:flex;animation:fadeIn .2s ease-out;}
    @keyframes fadeIn{from{opacity:0;}to{opacity:1;}}
    .modal-box{background:#fff;border-radius:18px;width:100%;max-width:460px;box-shadow:0 30px 80px rgba(0,0,0,.35);
        animation:pop .25s cubic-bezier(.2,1,.4,1);overflow:hidden;}
    @keyframes pop{from{opacity:0;transform:translateY(16px) scale(.97);}to{opacity:1;transform:none;}}
    .modal-head{padding:1.4rem 1.6rem 1rem;}
    .modal-head h3{font-size:1.15rem;font-weight:800;color:#1e293b;}
    .modal-head p{font-size:.84rem;color:#64748b;margin-top:.25rem;}
    .modal-head .pt{margin-top:.8rem;display:flex;align-items:center;gap:.6rem;background:#f5f8ff;border:1px solid #dbe6fb;
        border-radius:10px;padding:.6rem .8rem;}
    .modal-head .pt .tk{font-weight:800;color:var(--kq-navy);font-size:1.05rem;}
    .modal-head .pt .nm{font-size:.86rem;color:#556;}
    .modal-body{padding:.4rem 1.6rem 1rem;display:flex;flex-direction:column;gap:.6rem;}
    .opt{display:flex;align-items:center;gap:.9rem;width:100%;text-align:left;border:1.5px solid #e8ecf3;
        background:#fff;border-radius:12px;padding:.85rem 1rem;cursor:pointer;transition:.15s;}
    .opt:hover{border-color:var(--kq-navy);background:#f4f8ff;transform:translateY(-1px);box-shadow:0 4px 14px rgba(0,63,135,.1);}
    .opt .ic{width:40px;height:40px;border-radius:10px;display:grid;place-items:center;flex-shrink:0;font-size:1.2rem;}
    .opt .ic.none{background:#f1f5f9;color:#64748b;}
    .opt .ic.racik{background:#eff4ff;color:#1d4ed8;}
    .opt .ic.nonracik{background:#e0ebff;color:#1e40af;}
    .opt .tx b{display:block;font-size:.95rem;color:#1e293b;}
    .opt .tx span{font-size:.78rem;color:#64748b;}
    .opt .arr{margin-left:auto;color:#c7ced9;font-size:1.1rem;}
    .modal-foot{padding:1rem 1.6rem 1.4rem;display:flex;justify-content:flex-end;}
    .btn-cancel{background:#fff;color:#556;border:1px solid #e8ecf3;padding:.6rem 1.2rem;border-radius:10px;font-weight:600;cursor:pointer;}
    .btn-cancel:hover{background:#f5f7fb;}

    /* ===== Modal KONFIRMASI (custom, ganti confirm() bawaan) ===== */
    .cf-modal{position:fixed;inset:0;background:rgba(15,23,42,.55);backdrop-filter:blur(5px);display:none;
        align-items:center;justify-content:center;z-index:200;padding:1.5rem;}
    .cf-modal.open{display:flex;animation:fadeIn .18s ease-out;}
    .cf-box{background:#fff;border-radius:20px;width:100%;max-width:420px;overflow:hidden;
        box-shadow:0 30px 80px rgba(0,0,0,.4);animation:pop .25s cubic-bezier(.2,1,.4,1);}
    .cf-top{padding:1.8rem 1.8rem 1.2rem;text-align:center;}
    .cf-ic{width:64px;height:64px;border-radius:50%;margin:0 auto 1rem;display:grid;place-items:center;font-size:1.9rem;
        background:var(--cf-ic-bg,#fef3c7);color:var(--cf-ic-fg,#d97706);}
    .cf-title{font-size:1.2rem;font-weight:800;color:#1e293b;}
    .cf-msg{font-size:.92rem;color:#64748b;margin-top:.5rem;line-height:1.55;}
    .cf-msg b{color:#334155;}
    .cf-foot{display:flex;gap:.7rem;padding:0 1.8rem 1.8rem;}
    .cf-foot button{flex:1;border:none;border-radius:12px;padding:.85rem;font-size:.92rem;font-weight:700;cursor:pointer;transition:.15s;}
    .cf-no{background:#f1f5f9;color:#475569;} .cf-no:hover{background:#e2e8f0;}
    .cf-yes{background:var(--cf-yes,#0056b3);color:#fff;} .cf-yes:hover{filter:brightness(1.08);transform:translateY(-1px);
        box-shadow:0 8px 20px rgba(0,63,135,.3);}
</style>

{{-- Markup + JS modal konfirmasi (dipakai lewat data-confirm pada <form>).
     Dibungkus @push('scripts') agar tetap tersaji di <body> walau partial
     ini di-include dari @push('head'). --}}
@push('scripts')
<div class="cf-modal" id="cfModal">
    <div class="cf-box">
        <div class="cf-top">
            <div class="cf-ic" id="cfIc">⟲</div>
            <div class="cf-title" id="cfTitle">Konfirmasi</div>
            <div class="cf-msg" id="cfMsg">—</div>
        </div>
        <div class="cf-foot">
            <button type="button" class="cf-no" id="cfNo">Batal</button>
            <button type="button" class="cf-yes" id="cfYes">Ya, Lanjutkan</button>
        </div>
    </div>
</div>
<script>
(function(){
    var modal=document.getElementById('cfModal'), elIc=document.getElementById('cfIc'),
        elT=document.getElementById('cfTitle'), elM=document.getElementById('cfMsg'),
        elYes=document.getElementById('cfYes'), elNo=document.getElementById('cfNo');
    var pending=null;
    function close(){ modal.classList.remove('open'); pending=null; }
    elNo.addEventListener('click', close);
    modal.addEventListener('click', function(e){ if(e.target===modal) close(); });
    document.addEventListener('keydown', function(e){ if(e.key==='Escape' && modal.classList.contains('open')) close(); });
    elYes.addEventListener('click', function(){ if(pending){ var f=pending; pending=null; f.submit(); } });

    // Semua <form data-confirm="..."> memakai modal ini alih-alih confirm() bawaan.
    // Catatan: form.submit() programatik TIDAK memicu event 'submit', jadi saat
    // pengguna menekan "Ya" kita panggil f.submit() dan form langsung terkirim.
    document.addEventListener('submit', function(e){
        var f=e.target;
        if(!f.matches('form[data-confirm]')) return;
        e.preventDefault();
        pending=f;
        elT.textContent = f.dataset.cfTitle || 'Konfirmasi';
        elM.innerHTML = f.dataset.confirm || 'Lanjutkan tindakan ini?';
        elIc.textContent = f.dataset.cfIcon || '?';
        elIc.style.setProperty('--cf-ic-bg', f.dataset.cfIcbg || '#fef3c7');
        elIc.style.setProperty('--cf-ic-fg', f.dataset.cfIcfg || '#d97706');
        elYes.style.setProperty('--cf-yes', f.dataset.cfYescolor || '#2563eb');
        elYes.textContent = f.dataset.cfYeslabel || 'Ya, Lanjutkan';
        modal.classList.add('open');
    });
})();
</script>
@endpush
