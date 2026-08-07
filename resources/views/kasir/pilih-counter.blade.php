@extends('layouts.app')
@section('title', 'Pilih Counter Kasir')

@push('head')
<style>
    .pc-wrap{max-width:640px;margin:2rem auto 0;background:#fff;border:1px solid var(--line);border-radius:16px;padding:2rem 2.2rem;box-shadow:0 10px 40px rgba(0,0,0,.06);}
    .pc-wrap h2{font-size:1.4rem;font-weight:800;color:var(--ink);margin-bottom:1.6rem;}
    .pc-wrap select{width:100%;padding:.75rem .9rem;border:1.5px solid var(--line);border-radius:10px;font-size:.95rem;outline:none;margin-bottom:1rem;background:#fff;}
    .pc-wrap select:focus{border-color:var(--brand);box-shadow:0 0 0 3px #2563eb1c;}
    .pc-btn{display:block;margin:1.4rem auto 0;background:#5bc47a;color:#fff;border:none;padding:.75rem 2rem;border-radius:10px;font-weight:700;font-size:.95rem;cursor:pointer;}
    .pc-btn:hover{background:#4bb069;}
    .err-flash{background:#fdecec;border:1px solid #f5b5b5;color:#b42318;padding:.7rem 1rem;border-radius:9px;margin-bottom:1rem;font-size:.88rem;}
</style>
@endpush

@section('content')
    <div class="pc-wrap">
        <h2>Silahkan Pilih Counter Pemanggil</h2>
        @if ($errors->any())<div class="err-flash">{{ $errors->first() }}</div>@endif
        <form method="post" action="{{ route('kasir.set-counter') }}">
            @csrf
            <select name="counter" required>
                <option value="">Pilih...</option>
                @foreach (['Counter 1','Counter 2','Counter 3','Counter 4'] as $c)
                    <option value="{{ $c }}" {{ $counter===$c?'selected':'' }}>{{ strtoupper($c) }}</option>
                @endforeach
            </select>
            <button type="submit" class="pc-btn">PILIH COUNTER</button>
        </form>
    </div>
@endsection
