{{-- CSS untuk toggle-checkbox splash. Include sekali di @push('head'). --}}
<style>
    .tgc-form{display:inline-flex;align-items:center;gap:.6rem;}
    .tgc-label{font-size:.8rem;font-weight:700;color:#8a94a6;transition:color .3s;}
    .tgc-label.on{color:#16a34a;}

    .checkbox-wrapper-12{position:relative;}
    .checkbox-wrapper-12 > svg{position:absolute;top:-130%;left:-170%;width:110px;pointer-events:none;}
    .checkbox-wrapper-12 *{box-sizing:border-box;}
    .checkbox-wrapper-12 input[type="checkbox"]{
        -webkit-appearance:none;-moz-appearance:none;appearance:none;
        -webkit-tap-highlight-color:transparent;cursor:pointer;margin:0;}
    .checkbox-wrapper-12 input[type="checkbox"]:focus{outline:0;}

    .checkbox-wrapper-12 .cbx{width:24px;height:24px;position:relative;}
    .checkbox-wrapper-12 .cbx input{
        position:absolute;top:0;left:0;width:24px;height:24px;
        border:2px solid #bfbfc0;border-radius:50%;}
    .checkbox-wrapper-12 .cbx label{
        width:24px;height:24px;background:none;border-radius:50%;position:absolute;top:0;left:0;
        transform:translate3d(0,0,0);pointer-events:none;}
    .checkbox-wrapper-12 .cbx svg{position:absolute;top:5px;left:4px;z-index:1;pointer-events:none;}
    /* Tanpa transisi saat halaman dimuat → centang langsung tampil, tidak ada
       jeda. Transisi+delay hanya dipakai saat toggle benar-benar diklik. */
    .checkbox-wrapper-12 .cbx svg path{
        stroke:#fff;stroke-width:3;stroke-linecap:round;stroke-linejoin:round;
        stroke-dasharray:19;stroke-dashoffset:19;}
    .checkbox-wrapper-12.tgc-anim .cbx svg path{
        transition:stroke-dashoffset .3s ease;transition-delay:.2s;}
    .checkbox-wrapper-12 .cbx input:checked{border-color:#16a34a;background:#16a34a;}
    /* Splash HANYA saat diklik user (class .tgc-anim ditambah JS). Tanpa ini
       animasi ikut jalan tiap halaman dimuat ulang - sehingga semua toggle
       yang sedang aktif ikut beranimasi saat satu toggle ditekan. */
    .checkbox-wrapper-12.tgc-anim .cbx input:checked + label{animation:splash-12 .6s ease forwards;}
    .checkbox-wrapper-12 .cbx input:checked + label + svg path{stroke-dashoffset:0;}

    @keyframes splash-12{
        40%{background:#16a34a;
            box-shadow:0 -18px 0 -8px #16a34a,16px -8px 0 -8px #16a34a,16px 8px 0 -8px #16a34a,
                0 18px 0 -8px #16a34a,-16px 8px 0 -8px #16a34a,-16px -8px 0 -8px #16a34a;}
        100%{background:#16a34a;
            box-shadow:0 -36px 0 -10px transparent,32px -16px 0 -10px transparent,
                32px 16px 0 -10px transparent,0 36px 0 -10px transparent,
                -32px 16px 0 -10px transparent,-32px -16px 0 -10px transparent;}
    }
</style>
