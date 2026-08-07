{{--
  Pemilih target klinik untuk media (video/banner).
  Param:
    $clinics  : koleksi klinik (service_unit_code, service_unit_name)
    $selected : array kode terpilih (default []) → [] berarti "Semua Klinik"
    $uid      : id unik untuk name radio (mis. "new" atau video id)
--}}
@php $selected = $selected ?? []; $isPick = count($selected) > 0; @endphp
<div class="cp" data-uid="{{ $uid }}">
    <label class="cp-mode">
        <input type="radio" name="clinic_mode" value="all" {{ $isPick ? '' : 'checked' }} onchange="cpToggle(this)">
        <span><b>Semua Klinik</b> — tampil di semua layar tunggu</span>
    </label>
    <label class="cp-mode">
        <input type="radio" name="clinic_mode" value="pick" {{ $isPick ? 'checked' : '' }} onchange="cpToggle(this)">
        <span><b>Klinik Tertentu</b> — pilih klinik di bawah</span>
    </label>

    <div class="cp-list {{ $isPick ? '' : 'off' }}">
        <div class="cp-tools">
            <button type="button" class="cp-mini" onclick="cpAll(this,true)">Pilih semua</button>
            <button type="button" class="cp-mini" onclick="cpAll(this,false)">Kosongkan</button>
        </div>
        <div class="cp-grid">
            @foreach ($clinics as $c)
                <label class="cp-item">
                    <input type="checkbox" name="clinics[]" value="{{ $c->service_unit_code }}"
                        {{ in_array($c->service_unit_code, $selected, true) ? 'checked' : '' }}>
                    <span>{{ $c->service_unit_name }}</span>
                </label>
            @endforeach
        </div>
    </div>
</div>
