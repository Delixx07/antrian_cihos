@php
    $statusClass = function ($s) {
        $x = strtolower((string) $s);
        if (str_contains($x, 'check') || str_contains($x, 'hadir')) return 'checkin';
        if (str_contains($x, 'wait') || str_contains($x, 'tunggu')) return 'wait';
        return 'other';
    };
@endphp
@forelse ($rows as $r)
    <tr>
        <td><span class="ticket">{{ $r['ticket'] }}</span></td>
        <td><span class="qn">{{ $r['queue'] }}</span></td>
        <td style="font-weight:600;color:var(--ink);">{{ $r['patient'] }}</td>
        <td style="color:#556;">{{ $r['medicalNo'] }}</td>
        <td style="color:#556;">{{ $r['room'] }}{{ (!empty($r['roomName']) && $r['roomName'] !== $r['room']) ? ' · '.$r['roomName'] : '' }}</td>
        <td class="tabular-nums" style="color:#556;">{{ $r['timeRegis'] }}</td>
        <td><span class="st {{ $statusClass($r['status']) }}"><i></i> {{ $r['status'] }}</span></td>
    </tr>
@empty
    <tr><td colspan="7" class="empty">Belum ada pasien pada antrian hari ini.</td></tr>
@endforelse
