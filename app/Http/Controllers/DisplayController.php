<?php

namespace App\Http\Controllers;

use App\Models\Antrian;
use App\Models\Banner;
use App\Models\Video;
use Illuminate\Http\Request;

/**
 * Layar tunggu / kiosk (PUBLIK, tanpa login) untuk TV ruang tunggu. Menampilkan
 * nomor antrian yang sedang dipanggil per AREA (klinik / kasir / farmasi), video
 * promo aktif, banner slideshow, dan teks berjalan. Data panggilan di-poll
 * berkala. Ketiga area memakai tampilan & logika sama, cuma beda tahap.
 */
class DisplayController extends Controller
{
    private const RUNNING_TEXT = 'SELAMAT DATANG DI CIPUTRA HOSPITAL SURABAYA';

    /** Area valid → [judul layar, kolom timestamp panggil]. */
    private const AREAS = [
        'klinik'  => ['judul' => 'ANTRIAN KLINIK',  'tahap' => Antrian::TAHAP_KLINIK],
        'kasir'   => ['judul' => 'ANTRIAN KASIR',   'tahap' => Antrian::TAHAP_KASIR],
        'farmasi' => ['judul' => 'ANTRIAN FARMASI', 'tahap' => Antrian::TAHAP_FARMASI],
    ];

    /** Halaman layar tunggu (fullscreen) untuk sebuah area. */
    public function show(Request $request, string $area)
    {
        $meta = self::AREAS[$area] ?? abort(404);

        // Filter media per klinik/poli (opsional via ?clinic=SU-XXX). Media tanpa
        // target klinik tampil di semua; yang bertarget hanya di klinik itu.
        $clinic = $request->query('clinic');
        // Filter panggilan per RUANGAN (opsional via ?room=1101 atau ?room=1102,1103).
        $room = $request->query('room');
        $rooms = $this->roomList($room);

        // Bangun query string untuk jsonUrl (klinik + room).
        $qs = array_filter(['clinic' => $clinic, 'room' => $room]);
        $jsonUrl = route('display.json', $area).($qs ? '?'.http_build_query($qs) : '');

        return view('display.layar', [
            'area'        => $area,
            'judul'       => $rooms ? 'RUANG '.implode(' - ', $rooms) : $meta['judul'],
            'banners'     => Banner::active()->forClinic($clinic)->orderBy('sort')->orderBy('id')->get(),
            'video'       => Video::active()->forClinic($clinic)->orderBy('sort')->first(),
            'runningText' => self::RUNNING_TEXT,
            'jsonUrl'     => $jsonUrl,
        ]);
    }

    /** Nama zona (poli) — dari struktur RS. */
    private const ZONE_NAMES = [
        '11' => 'Dental, Women & Children clinic',
        '12' => 'Beauty & Nutrition, Surgery clinic',
        '15' => 'Neuroscience, Orthopedic, Oncology Clinic',
        '16' => 'Medical & Sport Rehabilitation Center',
        '17' => 'Cardiology & Internal Medicine Clinic, Pharmacy',
        '18' => 'Medical Check Up',
    ];

    /** Pasangan ruang per zona — DISALIN PERSIS dari antrian_old (modal.php). */
    private const ZONE_PAIRS = [
        '11' => [['1101'], ['1102', '1103'], ['1105', '1106'], ['1107'], ['1108'], ['1110', '1109']],
        '12' => [['1219', '1220'], ['1217', '1218'], ['1223', '1225'], ['1221', '1222']],
        '15' => [['1528', '1529'], ['1526', '1527'], ['1533'], ['1531', '1532']],
        '17' => [['1736'], ['1737', '1738'], ['1739', '1751'], ['1752', '1753']],
        '18' => [['1855'], ['1857', '1858'], ['1859', '1860']],
    ];

    /** Tombol Main Display (admisi, kasir, farmasi) — label + ikon Material Symbols. */
    private const MAIN_AREAS = [
        ['label' => 'Admisi Rawat Jalan', 'params' => ['area' => 'klinik'],                    'icon' => 'calendar_today'],
        ['label' => 'Admisi IGD',         'params' => ['area' => 'klinik', 'unit' => 'igd'],   'icon' => 'local_hospital'],
        ['label' => 'Admisi Radiologi',   'params' => ['area' => 'klinik', 'unit' => 'radiologi'], 'icon' => 'medical_services'],
        ['label' => 'Kasir Farmasi',      'params' => ['area' => 'kasir', 'sub' => 'farmasi'], 'icon' => 'credit_card'],
        ['label' => 'Kasir Administrasi', 'params' => ['area' => 'kasir'],                     'icon' => 'receipt_long'],
        ['label' => 'Farmasi',            'params' => ['area' => 'farmasi'],                    'icon' => 'medication'],
    ];

    /**
     * MENU pemilihan display (halaman awal /display). Menampilkan tombol Main
     * Display + kartu Zona (klik → modal pilih Main/Client Display per ruang).
     */
    public function menu()
    {
        // Ruangan per zona dari master (room_ticket_prefixes), utk fallback zona
        // yg tak ada di peta tetap.
        $byZone = [];
        try {
            foreach (\App\Models\RoomTicketPrefix::orderBy('room_code')->get(['room_code']) as $r) {
                $byZone[substr($r->room_code, 0, 2)][] = $r->room_code;
            }
        } catch (\Throwable $e) {
            $byZone = [];
        }

        // Susun data zona: pakai peta tetap bila ada; selain itu pasangkan 2-2.
        $zoneData = [];
        foreach ($byZone as $zone => $rooms) {
            $zoneData[$zone] = [
                'name'  => self::ZONE_NAMES[$zone] ?? 'Zona '.$zone,
                'pairs' => self::ZONE_PAIRS[$zone] ?? array_chunk($rooms, 2),
            ];
        }
        // Zona di peta tetap tapi belum muncul dari master → tetap tampilkan.
        foreach (self::ZONE_PAIRS as $zone => $pairs) {
            if (! isset($zoneData[$zone])) {
                $zoneData[$zone] = ['name' => self::ZONE_NAMES[$zone] ?? 'Zona '.$zone, 'pairs' => $pairs];
            }
        }
        ksort($zoneData);

        return view('display.menu', [
            'mainAreas' => self::MAIN_AREAS,
            'zoneData'  => $zoneData,
        ]);
    }

    /** Ubah "1102,1103" / "1102-1103" jadi array kode ruang; null bila kosong. */
    private function roomList(?string $room): array
    {
        if (! $room) {
            return [];
        }

        return collect(preg_split('/[,\-]/', $room))
            ->map(fn ($r) => trim($r))->filter()->values()->all();
    }

    /** JSON panggilan terkini area (untuk polling layar). */
    public function json(Request $request, string $area)
    {
        $meta  = self::AREAS[$area] ?? abort(404);
        $tahap = $meta['tahap'];
        $rooms = $this->roomList($request->query('room'));

        // Nomor yang sedang dipanggil (belum selesai) di tahap ini hari ini,
        // urut terbaru dulu (paling atas = panggilan aktif). Bila ?room= diberikan,
        // batasi ke ruangan itu (bisa gabungan beberapa ruang).
        $calls = Antrian::today()
            ->where('tahap', $tahap)
            ->whereNotNull("{$tahap}_panggil_at")
            ->whereNull("{$tahap}_selesai_at")
            ->when($rooms, fn ($q) => $q->whereIn('room_code', $rooms))
            ->orderByDesc("{$tahap}_panggil_at")
            ->limit(6)
            ->get(['no_antrian', 'pasien_nama', 'counter', 'poli_nama', 'room_code', "{$tahap}_panggil_at as panggil_at", 'panggil_count']);

        $first = $calls->first();

        // ANTRIAN BERIKUTNYA yang MENUNGGU (belum dipanggil), urut paling lama dulu.
        // Sesuai sistem lama: kartu utama = yang sedang dipanggil; 4 kotak = beberapa
        // nomor berikutnya yang menunggu giliran (BUKAN riwayat panggilan).
        $waiting = Antrian::today()
            ->where('tahap', $tahap)
            ->whereNull("{$tahap}_panggil_at")
            ->when($rooms, fn ($q) => $q->whereIn('room_code', $rooms))
            ->orderBy("{$tahap}_tunggu_at")
            ->orderByRaw('LENGTH(no_antrian), no_antrian')
            ->limit(9) // 6 baris daftar + 3 kartu "upcoming" di layar.
            ->get(['no_antrian', 'pasien_nama', 'room_code', 'counter']);

        $next = $waiting->first();

        // TOTAL yang menunggu (tanpa limit) — layar hanya menampilkan 5 kartu
        // pertama, angka ini memberi tahu sisanya ("+45 lagi") tanpa 50 kotak.
        $waitingTotal = Antrian::today()
            ->where('tahap', $tahap)
            ->whereNull("{$tahap}_panggil_at")
            ->when($rooms, fn ($q) => $q->whereIn('room_code', $rooms))
            ->count();

        /*
         * Tujuan: klinik pakai ruang; kasir/farmasi pakai counter.
         *
         * Nilai counter di data sudah menyertakan kata depannya sendiri
         * (mis. "Counter 2", "1859 (Zona 18)"). Layar menambahkan label
         * "COUNTER"/"ROOM" di depannya, sehingga bila tidak dibersihkan
         * hasilnya jadi "COUNTER Counter 2". Karena itu kata depan yang
         * berulang & embel-embel dalam kurung dibuang di sini.
         */
        $tujuan = function ($r) use ($tahap) {
            $v = trim((string) ($tahap === 'klinik' ? $r->room_code : $r->counter));
            if ($v === '') {
                return '—';
            }
            $v = preg_replace('/\s*\([^)]*\)\s*/', ' ', $v);              // buang "(Zona 18)"
            $v = preg_replace('/^(counter|loket|ruang|room)\s*/i', '', $v); // buang kata depan

            return trim($v) !== '' ? trim($v) : '—';
        };

        return response()->json([
            'current' => $first ? [
                'no'      => $first->no_antrian,
                'nama'    => $first->pasien_nama,
                // klinik → RUANG dokter; kasir/farmasi → counter.
                'tujuan'  => $tujuan($first),
                'counter' => $first->counter,
                'seq'     => $first->panggil_count, // berubah tiap panggil/ulang → pemicu animasi
                'at'      => optional($first->panggil_at)->timestamp,
            ] : null,
            'next' => $next ? [
                'no'   => $next->no_antrian,
                'nama' => $next->pasien_nama,
            ] : null,
            // 5 kartu = antrian berikutnya menunggu (nomor + ruang/counter tujuan).
            'queue' => $waiting->map(fn ($r) => [
                'no'     => $r->no_antrian,
                'tujuan' => $tujuan($r),
            ])->values(),
            'waiting_total' => $waitingTotal,
        ]);
    }

    /**
     * CLIENT DISPLAY (per ruang): halaman fullscreen menampilkan dokter yang
     * LOGIN & menempati ruang + "Now Serving" + Sesi 1/2 (antrean menunggu).
     * ?room=1859 atau 1859,1860 (2 ruang berdampingan).
     */
    public function client(string $room)
    {
        $rooms = array_slice($this->roomList($room), 0, 2);
        abort_if(empty($rooms), 400, 'Parameter room wajib.');

        return view('display.client', ['rooms' => $rooms, 'roomParam' => $room]);
    }

    /** JSON data Client Display (polling). Sumber dokter = antrian_access yg menempati. */
    public function clientJson(string $room)
    {
        $rooms = $this->roomList($room);
        $dow   = (int) now()->isoWeekday(); // 1=Senin..7=Minggu

        $cards = [];
        foreach ($rooms as $rc) {
            // Dokter yg MENEMPATI ruang ini hari ini (login) — mirip islogin lama.
            $occ = \App\Models\AntrianAccess::where('room_code', $rc)
                ->whereNotNull('room_occupied_at')
                ->whereDate('room_occupied_at', today())
                ->orderByDesc('room_occupied_at')
                ->first();
            $pid = (int) ($occ->paramedic_id ?? 0);

            // Dua window sesi dokter itu di ruang ini (dari master.doctor_schedules).
            [$s1, $s2] = $this->clientSessions($pid, $rc, $dow);
            $q = $pid ? $this->clientQueue($pid, $rc, $s2)
                      : ['sedang' => null, 'seq' => 0, 'sesi1' => [], 'sesi2' => []];

            $photo = null;
            if ($pid && ($ph = \App\Models\DoctorPhoto::find($pid))) {
                $photo = $ph->url();
            }

            $cards[] = [
                'room'   => $rc,
                'poli'   => $this->clientPoli($rc, $dow),
                'doctor' => $occ->paramedic_name ?? null,
                'photo'  => $photo,
                'sedang' => $q['sedang'],
                'seq'    => $q['seq'] ?? 0,   // naik tiap panggil/recall → pemicu suara
                'sesi'   => [
                    ['label' => 'SESSION 1', 'jam' => $s1, 'list' => $q['sesi1']],
                    ['label' => 'SESSION 2', 'jam' => $s2, 'list' => $q['sesi2']],
                ],
            ];
        }

        return response()->json(['cards' => $cards]);
    }

    /** Dua window sesi dokter (start/end 1 & 2) di ruang ini hari ini, atau [null,null]. */
    private function clientSessions(int $pid, string $room, int $dow): array
    {
        if ($pid <= 0) {
            return [null, null];
        }
        $s = \App\Models\DoctorSchedule::where('paramedic_id', $pid)
            ->where('room_code', $room)->where('day_number', $dow)
            ->first(['start_time1', 'end_time1', 'start_time2', 'end_time2']);
        if (! $s) {
            return [null, null];
        }
        $win = function ($a, $b) {
            $a = trim((string) $a); $b = trim((string) $b);
            if ($a === '' && $b === '') return null;
            return ['start' => substr($a, 0, 5), 'end' => substr($b, 0, 5)];
        };
        return [$win($s->start_time1, $s->end_time1), $win($s->start_time2, $s->end_time2)];
    }

    /** Antrean pasien dokter di ruang ini, dipisah per sesi (by jam tunggu). */
    private function clientQueue(int $pid, string $room, ?array $s2): array
    {
        $base = Antrian::today()->where('room_code', $room)
            ->where('paramedic_id', $pid)->where('tahap', Antrian::TAHAP_KLINIK);

        // Ambil juga panggil_count sebagai `seq`: nilainya naik tiap kali tombol
        // panggil/recall ditekan, sehingga layar bisa membedakan RECALL nomor
        // yang sama dari sekadar data berulang → dipakai untuk memicu suara.
        $call = (clone $base)->whereNotNull('klinik_panggil_at')->whereNull('klinik_selesai_at')
            ->orderByDesc('klinik_panggil_at')
            ->first(['no_antrian', 'panggil_count']);

        $sedang = $call->no_antrian ?? null;
        $seq    = (int) ($call->panggil_count ?? 0);

        $rows = (clone $base)->whereNull('klinik_panggil_at')
            ->orderByRaw('LENGTH(no_antrian), no_antrian')->limit(30)
            ->get(['no_antrian', 'klinik_tunggu_at']);

        $s2start = $s2['start'] ?? null;
        $sesi1 = []; $sesi2 = [];
        foreach ($rows as $r) {
            $jam = $r->klinik_tunggu_at ? $r->klinik_tunggu_at->format('H:i') : null;
            if ($s2start && $jam !== null && $jam >= $s2start) {
                $sesi2[] = $r->no_antrian;
            } else {
                $sesi1[] = $r->no_antrian;
            }
        }
        return ['sedang' => $sedang ?: null, 'seq' => $seq, 'sesi1' => $sesi1, 'sesi2' => $sesi2];
    }

    /** Nama poli ruang (dari jadwal, fallback data antrian). */
    private function clientPoli(string $room, int $dow): string
    {
        $p = \App\Models\DoctorSchedule::where('room_code', $room)->where('day_number', $dow)
            ->where('service_unit_name', '<>', '')->value('service_unit_name');
        if ($p) return $p;
        $p = Antrian::where('room_code', $room)->where('poli_nama', '<>', '')->value('poli_nama');
        return $p ?: '—';
    }
}
