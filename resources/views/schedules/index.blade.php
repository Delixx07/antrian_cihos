@extends('layouts.app')
@section('title', 'Jadwal Dokter')

@push('head')
<style>
    .toolbar{display:flex;align-items:center;gap:1rem;margin-bottom:1.2rem;flex-wrap:wrap;}
    .search{position:relative;flex:1;max-width:420px;}
    .search svg{position:absolute;left:.8rem;top:50%;transform:translateY(-50%);color:var(--muted);width:16px;height:16px;}
    .count-badge{margin-left:auto;font-size:.82rem;color:var(--muted);}
    .table-scroll{max-height:600px;overflow:auto;}
    .table-scroll table.data{min-width:1000px;}
    .table-scroll table.data th{position:sticky;top:0;z-index:1;}
    .badge{display:inline-block;padding:.2rem .55rem;border-radius:6px;font-size:.76rem;font-weight:600;background:#eff4ff;color:var(--brand);}
    .day{font-weight:700;color:var(--navy);}
    .sesi{white-space:nowrap;font-variant-numeric:tabular-nums;}
    .sesi.none{color:#c3cad6;}
    .empty{text-align:center;padding:3rem;color:var(--muted);}
</style>
@endpush

@section('content')
    <h1 class="page-title">Jadwal Dokter</h1>

    <div class="card" style="padding:1.2rem 1.2rem 0;">
        <div class="toolbar">
            <form method="get" class="search">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                <input class="input" type="text" name="q" value="{{ $q }}" placeholder="Cari klinik / kode dokter…" onchange="this.form.submit()">
            </form>
            <span class="count-badge">{{ count($rows) }} jadwal</span>
        </div>

        <div class="table-scroll">
            <table class="data">
                <thead>
                    <tr>
                        <th>Hari</th>
                        <th>Kode Klinik</th>
                        <th>Klinik</th>
                        <th>Kode Dokter</th>
                        <th>Nama Dokter</th>
                        <th>Sesi 1</th>
                        <th>Sesi 2</th>
                        <th>Sesi 3</th>
                        <th>Sesi 4</th>
                        <th>Sesi 5</th>
                        <th>Room</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $r)
                        @php
                            $win = function ($s, $e) {
                                $s = substr((string) $s, 0, 5); $e = substr((string) $e, 0, 5);
                                return ($s && $e) ? $s.' - '.$e : null;
                            };
                        @endphp
                        <tr>
                            <td class="day">{{ $days[$r->day_number] ?? $r->day_in_string ?? '—' }}</td>
                            <td><span class="badge">{{ $r->service_unit_code }}</span></td>
                            <td style="color:var(--ink);">{{ $r->service_unit_name }}</td>
                            <td>{{ $r->paramedic_code ?: '—' }}</td>
                            <td style="font-weight:600;color:var(--ink);">{{ $names[$r->paramedic_id] ?? ('#'.$r->paramedic_id) }}</td>
                            @for ($i = 1; $i <= 5; $i++)
                                @php $w = $win($r->{"start_time{$i}"}, $r->{"end_time{$i}"}); @endphp
                                <td class="sesi {{ $w ? '' : 'none' }}">{{ $w ?: '—' }}</td>
                            @endfor
                            <td>{{ $r->room_name ?: $r->room_code ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="11" class="empty">Tidak ada jadwal ditemukan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
