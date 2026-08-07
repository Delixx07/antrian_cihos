{{--
  Checkbox toggle animasi "splash" (untuk Aktifkan/Nonaktifkan).
  Param:
    $id      : id unik (mis. "vid-5" / "ban-3")
    $checked : bool status aktif sekarang
    $action  : URL form (route toggle)
    $label   : teks di samping (mis. "Aktif diputar")
  Klik → auto-submit form (toggle di server).
--}}
<form method="post" action="{{ $action }}" class="tgc-form">
    @csrf @method('PUT')
    <div class="checkbox-wrapper-12">
        <div class="cbx">
            {{-- .tgc-anim ditambahkan saat diklik saja, supaya splash tidak
                 ikut jalan untuk semua toggle aktif ketika halaman dimuat. --}}
            <input id="{{ $id }}" type="checkbox" {{ $checked ? 'checked' : '' }}
                onchange="this.closest('.checkbox-wrapper-12').classList.add('tgc-anim');this.closest('form').submit()">
            <label for="{{ $id }}"></label>
            <svg width="15" height="14" viewBox="0 0 15 14" fill="none">
                <path d="M2 8.36364L6.23077 12L13 2"></path>
            </svg>
        </div>
        <svg xmlns="http://www.w3.org/2000/svg" version="1.1">
            <defs>
                <filter id="goo-12">
                    <feGaussianBlur in="SourceGraphic" stdDeviation="4" result="blur"></feGaussianBlur>
                    <feColorMatrix in="blur" mode="matrix" values="1 0 0 0 0  0 1 0 0 0  0 0 1 0 0  0 0 0 22 -7" result="goo-12"></feColorMatrix>
                    <feBlend in="SourceGraphic" in2="goo-12"></feBlend>
                </filter>
            </defs>
        </svg>
    </div>
    <span class="tgc-label {{ $checked ? 'on' : '' }}">{{ $label }}</span>
</form>
