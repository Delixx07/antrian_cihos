{{-- CSS bersama konsol antrian (klinik / kasir / farmasi). Dipakai via @include. --}}
<style>
    .kq{
        --kq-card:#fff;--kq-line:#e5e9f1;--kq-line-soft:#eef1f6;
        --kq-ink:#101828;--kq-muted:#667085;--kq-faint:#98a2b3;--kq-bg-soft:#f8fafc;
        --kq-navy:#0a2a66;--kq-brand:#2563eb;--kq-brand-dark:#1d4ed8;--kq-brand-tint:#eef4ff;
        --kq-radius:16px;
        --kq-shadow-sm:0 1px 2px rgba(16,24,40,.04),0 1px 3px rgba(16,24,40,.06);
        --kq-shadow-md:0 8px 24px rgba(16,24,40,.06),0 2px 6px rgba(16,24,40,.05);
        font-variant-numeric:tabular-nums;
    }

    .kq-live{font-size:.76rem;color:#15803d;display:inline-flex;align-items:center;gap:.4rem;font-weight:600;
        background:#f0fdf4;border:1px solid #bbf7d0;padding:.4rem .8rem;border-radius:999px;}
    .kq-live .dot{width:6px;height:6px;border-radius:50%;background:#16a34a;animation:kqpulse 1.6s infinite;}
    @keyframes kqpulse{0%,100%{opacity:1;}50%{opacity:.25;}}

    /* Tombol tarik-ulang data dari appointment (manual) - gaya "ghost". */
    .kq-sync{font-size:.78rem;font-weight:600;color:var(--kq-muted);background:#fff;
        border:1px solid var(--kq-line);border-radius:10px;padding:.42rem .85rem;cursor:pointer;
        display:inline-flex;align-items:center;gap:.4rem;transition:background-color .15s,border-color .15s,color .15s;}
    .kq-sync:hover{background:var(--kq-bg-soft);border-color:#d0d5dd;color:var(--kq-ink);}
    .kq-sync:active{transform:translateY(1px);}

    .kq-flash{padding:.85rem 1.1rem;border-radius:12px;margin-bottom:1.2rem;font-size:.87rem;font-weight:500;
        border:1px solid transparent;}
    .kq-flash.ok{background:#f0fdf4;border-color:#bbf7d0;color:#15803d;}
    .kq-flash.err{background:#fef6f0;border-color:#fbd8b8;color:#c2410c;}

    /* ===== Layout 2 kolom: kiri panel panggil (sedikit lebih lebar), kanan list ===== */
    .kq-grid{display:grid;grid-template-columns:350px 1fr;gap:1.25rem;align-items:start;}
    @media(max-width:940px){.kq-grid{grid-template-columns:1fr;}}

    /* Panel (kartu putih) - datar, hairline border, bayangan sangat lembut */
    .kq-panel{background:var(--kq-card);border:1px solid var(--kq-line);border-radius:var(--kq-radius);overflow:hidden;
        margin-bottom:1.25rem;box-shadow:var(--kq-shadow-sm);}
    .kq-panel-h{padding:1rem 1.35rem;border-bottom:1px solid var(--kq-line-soft);display:flex;align-items:center;gap:.6rem;}
    .kq-panel-h h3{font-size:.92rem;font-weight:700;color:var(--kq-ink);margin:0;letter-spacing:-.005em;}
    .kq-panel-h .cnt{background:var(--kq-brand-tint);color:var(--kq-brand-dark);font-size:.74rem;font-weight:700;padding:.15rem .55rem;border-radius:7px;}
    .kq-panel-h .cnt.gray{background:var(--kq-bg-soft);color:var(--kq-faint);}
    .kq-panel-b{padding:1.25rem 1.35rem;}

    /* Kotak nomor panggilan (di panel kiri) */
    .kq-callbox{border:1px solid var(--kq-line);border-radius:14px;padding:1.7rem 1rem;text-align:center;
        background:linear-gradient(180deg,var(--kq-brand-tint) 0%,#fff 100%);margin-bottom:1.1rem;position:relative;}
    .kq-callbox::before{content:"";position:absolute;top:0;left:0;right:0;height:3px;border-radius:14px 14px 0 0;
        background:linear-gradient(90deg,var(--kq-brand),var(--kq-navy));}
    .kq-callbox .num{font-size:2.9rem;font-weight:800;color:var(--kq-navy);line-height:1;letter-spacing:-.02em;}
    .kq-callbox .pt{font-size:.9rem;color:var(--kq-muted);margin-top:.55rem;font-weight:600;}
    .kq-callbox .badge{display:inline-block;margin-top:.65rem;background:#fff;color:var(--kq-brand-dark);
        border:1px solid #cfdcf5;border-radius:999px;padding:.22rem .7rem;font-size:.72rem;font-weight:700;}

    /* Empty-state */
    .kq-idle{text-align:center;padding:1.9rem 1rem 1.1rem;}
    .kq-idle-ic{width:52px;height:52px;margin:0 auto;border-radius:50%;background:var(--kq-bg-soft);
        display:grid;place-items:center;font-size:1.5rem;filter:grayscale(.15);opacity:.8;}
    .kq-idle-t{font-size:.92rem;font-weight:700;color:var(--kq-ink);margin-top:.75rem;}
    .kq-idle-s{font-size:.8rem;color:var(--kq-faint);margin-top:.2rem;}

    /* ===== Sistem tombol bersama (panel kiri + baris list) - flat, modern ===== */
    .kq-acts{display:flex;gap:.6rem;flex-wrap:wrap;}
    .kq-acts form{flex:1;}
    .kq-acts button,.kq-btn{
        appearance:none;-webkit-appearance:none;border:1px solid transparent;border-radius:10px;
        font-weight:600;font-size:.85rem;cursor:pointer;display:inline-flex;align-items:center;
        justify-content:center;gap:.4rem;white-space:nowrap;
        transition:background-color .15s ease,border-color .15s ease,color .15s ease,box-shadow .15s ease,transform .08s ease;
    }
    .kq-acts button{width:100%;padding:.68rem .9rem;}
    .kq-btn{padding:.55rem 1.05rem;font-size:.83rem;}
    .kq-acts button:active,.kq-btn:active{transform:translateY(1px);}

    /* Variant: solid brand (aksi utama / panggil) */
    .kq-btn.call{background:var(--kq-brand);color:#fff;box-shadow:var(--kq-shadow-sm);}
    .kq-btn.call:hover{background:var(--kq-brand-dark);box-shadow:0 4px 10px rgba(37,99,235,.25);}

    /* Variant: solid hijau (selesai) */
    .kq-acts .b-done,.kq-btn.done{background:#16a34a;color:#fff;box-shadow:var(--kq-shadow-sm);}
    .kq-acts .b-done:hover,.kq-btn.done:hover{background:#15803d;box-shadow:0 4px 10px rgba(22,163,74,.25);}

    /* Variant: outline amber (recall / panggil ulang) */
    .kq-acts .b-recall,.kq-btn.recall{background:#fff;color:#b45309;border-color:#fbd8a5;}
    .kq-acts .b-recall:hover,.kq-btn.recall:hover{background:#fffaf0;border-color:#f2b153;}

    .kq-hint{font-size:.74rem;color:var(--kq-muted);margin-top:.7rem;line-height:1.5;}

    /* Baris info (panel Ringkasan) */
    .kq-inforow{display:flex;align-items:center;justify-content:space-between;padding:.7rem 0;border-bottom:1px solid var(--kq-line-soft);font-size:.88rem;color:var(--kq-muted);}
    .kq-inforow:last-child{border-bottom:none;}
    .kq-inforow .chip{padding:.28rem .7rem;border-radius:8px;font-weight:700;font-size:.83rem;}
    .kq-inforow .chip.blue{background:var(--kq-brand-tint);color:var(--kq-brand-dark);}
    .kq-inforow .chip.amber{background:#fff7e6;color:#b45309;}

    .kq-stats{display:grid;grid-template-columns:repeat(2,1fr);gap:1rem;margin-bottom:1.5rem;}
    @media(max-width:640px){.kq-stats{grid-template-columns:1fr;}}

    .kq-list{display:flex;flex-direction:column;}
    /* Baris header kolom (keterangan) */
    .kq-hrow{display:flex;align-items:center;gap:1rem;padding:.6rem 1.35rem;background:var(--kq-bg-soft);
        border-bottom:1px solid var(--kq-line-soft);font-size:.68rem;font-weight:700;color:var(--kq-faint);
        text-transform:uppercase;letter-spacing:.05em;}
    .kq-hrow .h-tk{min-width:70px;} .kq-hrow .h-who{flex:1;} .kq-hrow .h-end{text-align:right;}
    .kq-row{display:flex;align-items:center;gap:1rem;padding:.85rem 1.35rem;border-bottom:1px solid var(--kq-line-soft);transition:background-color .12s;}
    .kq-row:last-child{border-bottom:none;}
    .kq-row:hover{background:var(--kq-bg-soft);}
    .kq-row.locked{opacity:.65;}
    .kq-row .tk{font-weight:700;color:var(--kq-navy);background:var(--kq-brand-tint);border-radius:7px;
        padding:.22rem .5rem;font-size:.85rem;min-width:70px;text-align:center;}
    .kq-row .who{flex:1;min-width:0;}
    .kq-row .who .nm{font-weight:600;color:var(--kq-ink);font-size:.91rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
    .kq-row .who .rm{font-size:.78rem;color:var(--kq-faint);margin-top:.12rem;}
    .kq-row .sr{font-size:.72rem;font-weight:700;padding:.22rem .6rem;border-radius:7px;white-space:nowrap;}
    .kq-row .sr.none{background:var(--kq-bg-soft);color:var(--kq-muted);}
    .kq-row .sr.resep{background:#fff7e6;color:#b45309;}
    .kq-row .sr.clear{background:#f0fdf4;color:#15803d;}
    .kq-row .waitchip{font-size:.76rem;color:var(--kq-faint);font-weight:600;min-width:44px;text-align:right;}
    .kq-lock{font-size:.76rem;font-weight:600;color:#b45309;background:#fff7e6;border:1px solid #fbd8a5;
        border-radius:8px;padding:.42rem .8rem;display:inline-flex;align-items:center;gap:.35rem;white-space:nowrap;}
    .kq-otherctr{font-size:.76rem;font-weight:600;color:var(--kq-faint);background:var(--kq-bg-soft);
        border:1px solid var(--kq-line);border-radius:8px;padding:.42rem .8rem;display:inline-flex;
        align-items:center;gap:.35rem;white-space:nowrap;}
    .kq-empty{text-align:center;padding:2.4rem 1rem;color:var(--kq-faint);font-size:.87rem;}

    @media(max-width:640px){
        .kq-row .who .nm{white-space:normal;}
    }

    /* Modal pilih status resep (dipakai klinik) */
    .modal{position:fixed;inset:0;background:rgba(16,24,40,.5);backdrop-filter:blur(4px);display:none;
        align-items:center;justify-content:center;z-index:100;padding:1.5rem;}
    .modal.open{display:flex;animation:fadeIn .2s ease-out;}
    @keyframes fadeIn{from{opacity:0;}to{opacity:1;}}
    .modal-box{background:#fff;border-radius:18px;width:100%;max-width:460px;box-shadow:0 24px 60px rgba(16,24,40,.3);
        animation:pop .2s cubic-bezier(.2,1,.4,1);overflow:hidden;}
    @keyframes pop{from{opacity:0;transform:translateY(12px) scale(.98);}to{opacity:1;transform:none;}}
    .modal-head{padding:1.4rem 1.6rem 1rem;}
    .modal-head h3{font-size:1.1rem;font-weight:700;color:var(--kq-ink);}
    .modal-head p{font-size:.84rem;color:var(--kq-muted);margin-top:.3rem;}
    .modal-head .pt{margin-top:.9rem;display:flex;align-items:center;gap:.6rem;background:var(--kq-bg-soft);border:1px solid var(--kq-line);
        border-radius:10px;padding:.6rem .8rem;}
    .modal-head .pt .tk{font-weight:700;color:var(--kq-navy);font-size:1rem;}
    .modal-head .pt .nm{font-size:.86rem;color:var(--kq-muted);}
    .modal-body{padding:.4rem 1.6rem 1rem;display:flex;flex-direction:column;gap:.6rem;}
    .opt{display:flex;align-items:center;gap:.9rem;width:100%;text-align:left;border:1px solid var(--kq-line);
        background:#fff;border-radius:12px;padding:.85rem 1rem;cursor:pointer;transition:border-color .15s,background-color .15s;}
    .opt:hover{border-color:var(--kq-brand);background:var(--kq-brand-tint);}
    .opt .ic{width:38px;height:38px;border-radius:10px;display:grid;place-items:center;flex-shrink:0;font-size:1.1rem;}
    .opt .ic.none{background:var(--kq-bg-soft);color:var(--kq-muted);}
    .opt .ic.racik{background:#eff4ff;color:#1d4ed8;}
    .opt .ic.nonracik{background:#e0ebff;color:#1e40af;}
    .opt .tx b{display:block;font-size:.93rem;color:var(--kq-ink);}
    .opt .tx span{font-size:.78rem;color:var(--kq-muted);}
    .opt .arr{margin-left:auto;color:var(--kq-faint);font-size:1.1rem;}
    .modal-foot{padding:1rem 1.6rem 1.4rem;display:flex;justify-content:flex-end;}
    .btn-cancel{background:#fff;color:var(--kq-muted);border:1px solid var(--kq-line);padding:.6rem 1.2rem;
        border-radius:10px;font-weight:600;font-size:.85rem;cursor:pointer;transition:background-color .15s;}
    .btn-cancel:hover{background:var(--kq-bg-soft);}

    /* ===== Modal KONFIRMASI (custom, ganti confirm() bawaan) ===== */
    .cf-modal{position:fixed;inset:0;background:rgba(16,24,40,.55);backdrop-filter:blur(5px);display:none;
        align-items:center;justify-content:center;z-index:200;padding:1.5rem;}
    .cf-modal.open{display:flex;animation:fadeIn .18s ease-out;}
    .cf-box{background:#fff;border-radius:18px;width:100%;max-width:420px;overflow:hidden;
        box-shadow:0 24px 60px rgba(16,24,40,.32);animation:pop .2s cubic-bezier(.2,1,.4,1);}
    .cf-top{padding:1.8rem 1.8rem 1.2rem;text-align:center;}
    .cf-ic{width:56px;height:56px;border-radius:50%;margin:0 auto 1rem;display:grid;place-items:center;font-size:1.6rem;
        background:var(--cf-ic-bg,#fef3c7);color:var(--cf-ic-fg,#d97706);}
    .cf-title{font-size:1.1rem;font-weight:700;color:var(--kq-ink);}
    .cf-msg{font-size:.9rem;color:var(--kq-muted);margin-top:.5rem;line-height:1.55;}
    .cf-msg b{color:var(--kq-ink);}
    .cf-foot{display:flex;gap:.7rem;padding:0 1.8rem 1.8rem;}
    .cf-foot button{flex:1;border:1px solid transparent;border-radius:10px;padding:.75rem;font-size:.88rem;font-weight:600;cursor:pointer;transition:background-color .15s,box-shadow .15s,transform .08s;}
    .cf-foot button:active{transform:translateY(1px);}
    .cf-no{background:#fff;color:var(--kq-muted);border-color:var(--kq-line);} .cf-no:hover{background:var(--kq-bg-soft);}
    .cf-yes{background:var(--cf-yes,var(--kq-brand));color:#fff;} .cf-yes:hover{filter:brightness(1.08);box-shadow:0 6px 16px rgba(16,24,40,.2);}
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
            <div class="cf-msg" id="cfMsg">-</div>
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
