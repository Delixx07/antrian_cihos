<?php

namespace App\Http\Controllers;

use App\Models\Antrian;
use App\Models\Banner;
use App\Models\KioskRegistration;
use App\Models\Setting;
use App\Models\Video;
use App\Models\Zone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Layar tunggu / kiosk (PUBLIK, tanpa login) untuk TV ruang tunggu. Menampilkan
 * nomor antrian yang sedang dipanggil per AREA (klinik / kasir / farmasi), video
 * promo aktif, banner slideshow, dan teks berjalan. Data panggilan di-poll
 * berkala. Ketiga area memakai tampilan & logika sama, cuma beda tahap.
 */
class DisplayController extends Controller
{
    /** Dipakai kalau admin belum pernah mengisi teks berjalan sendiri (lihat Setting). */
    public const RUNNING_TEXT_DEFAULT = 'WELCOME TO CIPUTRA HOSPITAL SURABAYA';

    /** Area valid → [judul layar, kolom timestamp panggil]. */
    private const AREAS = [
        'klinik'     => ['judul' => 'CLINIC QUEUE',       'tahap' => Antrian::TAHAP_KLINIK],
        'kasir'      => ['judul' => 'CASHIER QUEUE',      'tahap' => Antrian::TAHAP_KASIR],
        'farmasi'    => ['judul' => 'PHARMACY QUEUE',     'tahap' => Antrian::TAHAP_FARMASI],
        // 'tahap' di sini cuma placeholder (tak dipakai query) - data registrasi
        // datang dari KioskRegistration, lihat cabang khusus di json().
        'registrasi' => ['judul' => 'REGISTRATION QUEUE', 'tahap' => 'registrasi'],
    ];

    /** Halaman layar tunggu (fullscreen) untuk sebuah area. */
    public function show(Request $request, string $area)
    {
        $meta = self::AREAS[$area] ?? abort(404);

        // Filter media per klinik/poli (opsional via ?clinic=SU-XXX). Media tanpa
        // target klinik tampil di semua; yang bertarget hanya di klinik itu.
        $clinic = $request->query('clinic');
        // Filter panggilan per RUANGAN (opsional via ?room=1101 atau ?room=1102,1103),
        // ATAU per ZONA (opsional via ?floor=11 - "Main Display" zona dari menu,
        // mencakup SEMUA ruang di zona itu, lihat display/menu.blade.php openZone()).
        $room  = $request->query('room');
        $floor = $request->query('floor');
        $rooms = $this->roomList($room) ?: $this->zoneRooms($floor);

        // Bangun query string untuk jsonUrl (klinik + room/floor).
        $qs = array_filter(['clinic' => $clinic, 'room' => $room, 'floor' => $room ? null : $floor]);
        $jsonUrl = route('display.json', $area).($qs ? '?'.http_build_query($qs) : '');
        $runningText = Setting::get('running_text', self::RUNNING_TEXT_DEFAULT);

        // Judul + keterangan zona (mis. "Dental, Women & Children clinic") - cuma
        // untuk layar Klinik. Mode RUANG (?room=) → judul sebut ruangnya persis;
        // mode ZONA (?floor=) → judul sebut nama zonanya, tanpa daftar semua ruang.
        $judul    = $meta['judul'];
        $zoneDesc = null;
        if ($area === 'klinik' && $room && $rooms) {
            $judul    = 'ROOM '.implode(' - ', $rooms);
            $zoneDesc = optional(Zone::allCached()->get(substr($rooms[0], 0, 2)))->name;
        } elseif ($area === 'klinik' && ! $room && $floor && ($z = Zone::allCached()->get($floor))) {
            $judul    = 'ZONE '.$floor;
            $zoneDesc = $z->name;
        }

        // Farmasi punya tata letak SENDIRI (queue racik/non-racik terpisah +
        // kartu "sedang diproses") - bukan template glass klinik/kasir.
        if ($area === 'farmasi') {
            return view('display.farmasi', [
                'judul'       => $judul,
                'runningText' => $runningText,
                'jsonUrl'     => $jsonUrl,
            ]);
        }

        return view('display.layar', [
            'area'        => $area,
            'judul'       => $judul,
            'zoneDesc'    => $zoneDesc,
            'banners'     => Banner::active()->forClinic($clinic)->orderBy('sort')->orderBy('id')->get(),
            'video'       => Video::active()->forClinic($clinic)->orderBy('sort')->first(),
            'runningText' => $runningText,
            'jsonUrl'     => $jsonUrl,
        ]);
    }

    /** Semua kode ruang dalam satu zona (dari tabel `zones`), untuk ?floor=. */
    private function zoneRooms(?string $floor): array
    {
        $zone = $floor ? Zone::allCached()->get($floor) : null;

        // rooms BOLEH kosong ([]) - zona yang cuma dikonfigurasi namanya saja
        // (lihat komentar migration `create_zones_table`). array_merge() tanpa
        // argumen itu error di PHP 8, jadi harus dijaga eksplisit.
        return ($zone && $zone->rooms) ? array_merge(...$zone->rooms) : [];
    }

    /** Tombol Main Display (admisi, kasir, farmasi) - label + ikon (lihat display/menu.blade.php). */
    private const MAIN_AREAS = [
        ['label' => 'Admisi Rawat Jalan', 'params' => ['area' => 'klinik'],                    'icon' => 'calendar_today'],
        ['label' => 'Admisi IGD',         'params' => ['area' => 'klinik', 'unit' => 'igd'],   'icon' => 'local_hospital'],
        ['label' => 'Admisi Radiologi',   'params' => ['area' => 'klinik', 'unit' => 'radiologi'], 'icon' => 'medical_services'],
        ['label' => 'Kasir Farmasi',      'params' => ['area' => 'kasir', 'sub' => 'farmasi'], 'icon' => 'credit_card'],
        ['label' => 'Kasir Administrasi', 'params' => ['area' => 'kasir'],                     'icon' => 'receipt_long'],
        ['label' => 'Farmasi',            'params' => ['area' => 'farmasi'],                    'icon' => 'medication'],
        ['label' => 'Registrasi',         'params' => ['area' => 'registrasi'],                 'icon' => 'how_to_reg'],
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
            // Master (RoomTicketPrefix) gagal diakses - jangan sampai halaman menu
            // ikut mati, tapi tetap catat supaya kegagalan ini kelihatan di log,
            // bukan cuma diam-diam jatuh ke peta zona tetap (ZONE_PAIRS).
            Log::warning('DisplayController::menu() gagal baca RoomTicketPrefix: '.$e->getMessage());
            $byZone = [];
        }

        // Susun data zona: pakai peta tetap bila ada; selain itu pasangkan 2-2.
        $zones = Zone::allCached();

        $zoneData = [];
        foreach ($byZone as $zone => $rooms) {
            $z = $zones->get($zone);
            $zoneData[$zone] = [
                'name'  => $z->name ?? 'Zona '.$zone,
                // rooms kosong ([]) = zona cuma dikonfigurasi namanya saja →
                // pasangkan otomatis 2-2 dari master, SAMA seperti zona yg
                // sama sekali belum ada di tabel `zones`.
                'pairs' => ($z && $z->rooms) ? $z->rooms : array_chunk($rooms, 2),
            ];
        }
        // Zona yang sudah dikonfigurasi admin tapi belum muncul dari master → tetap tampilkan.
        foreach ($zones as $zone => $z) {
            if (! isset($zoneData[$zone]) && $z->rooms) {
                $zoneData[$zone] = ['name' => $z->name, 'pairs' => $z->rooms];
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

    /**
     * Query dasar Antrian hari ini untuk sebuah AREA layar. Farmasi difilter
     * lewat status_resep (racik + non_racik digabung jadi satu layar), bukan
     * lewat kolom `tahap` - lebih tahan terhadap perubahan alur di masa depan
     * (mis. kolom `tahap` sempat tak konsisten dipakai untuk farmasi).
     */
    private function areaQuery(string $area, string $tahap)
    {
        if ($area === 'farmasi') {
            return Antrian::today()
                ->whereIn('status_resep', [Antrian::RESEP_RACIK, Antrian::RESEP_NON_RACIK]);
        }

        return Antrian::today()->where('tahap', $tahap);
    }

    /** JSON panggilan terkini area (untuk polling layar). */
    public function json(Request $request, string $area)
    {
        $meta  = self::AREAS[$area] ?? abort(404);
        $tahap = $meta['tahap'];
        $rooms = $this->roomList($request->query('room')) ?: $this->zoneRooms($request->query('floor'));

        // REGISTRASI: data sama sekali TIDAK ada di tabel `antrian` (beda
        // koneksi/tabel - lihat App\Models\KioskRegistration), jadi tidak bisa
        // lewat areaQuery()/interpolasi kolom "{$tahap}_..." di bawah seperti
        // klinik/kasir/farmasi. Hitung terpisah, tapi BENTUK JSON-nya SAMA
        // (current/next/queue/waiting_total) supaya display.layar's poll() JS
        // yang sudah ada jalan tanpa ubahan sama sekali.
        if ($area === 'registrasi') {
            $regBase = fn () => KioskRegistration::whereDate('tanggal', today());

            $calls = $regBase()->whereNotNull('panggil_at')->whereNull('selesai_at')
                ->orderByDesc('panggil_at')->limit(6)
                ->get(['rg_no', 'counter', 'panggil_at', 'panggil_count']);
            $first = $calls->first();

            $waiting = $regBase()->where('status', 'menunggu')
                ->orderBy('created_at')->limit(9)->get(['rg_no']);
            $next = $waiting->first();

            $waitingTotal = $regBase()->where('status', 'menunggu')->count();

            // Buang kata depan yang sudah menyatu di nilai counter (mis. "Counter 2")
            // supaya layar tak menampilkan dobel "COUNTER Counter 2" - sama seperti
            // pembersihan $tujuan() di bawah untuk klinik/kasir/farmasi.
            $cleanCounter = function (?string $v) {
                $v = trim((string) $v);
                if ($v === '') {
                    return '-';
                }
                $v = preg_replace('/\s*\([^)]*\)\s*/', ' ', $v);
                $v = preg_replace('/^(counter|loket|ruang|room)\s*/i', '', $v);

                return trim($v) !== '' ? trim($v) : '-';
            };

            return response()->json([
                'current' => $first ? [
                    'no'      => $first->rg_no,
                    'tujuan'  => $cleanCounter($first->counter),
                    'counter' => $first->counter,
                    'seq'     => $first->panggil_count,
                    'at'      => optional($first->panggil_at)->timestamp,
                ] : null,
                'next' => $next ? ['no' => $next->rg_no] : null,
                'queue' => $waiting->map(fn ($r) => [
                    'no' => $r->rg_no, 'tujuan' => '-', 'booking' => false,
                ])->values(),
                'waiting_total' => $waitingTotal,
            ]);
        }

        // Nomor yang sedang dipanggil (belum selesai) di tahap ini hari ini,
        // urut terbaru dulu (paling atas = panggilan aktif). Bila ?room= diberikan,
        // batasi ke ruangan itu (bisa gabungan beberapa ruang).
        $calls = $this->areaQuery($area, $tahap)
            ->whereNotNull("{$tahap}_panggil_at")
            ->whereNull("{$tahap}_selesai_at")
            ->when($rooms, fn ($q) => $q->whereIn('room_code', $rooms))
            ->orderByDesc("{$tahap}_panggil_at")
            ->limit(6)
            ->get(['no_antrian', 'counter', 'poli_nama', 'room_code', "{$tahap}_panggil_at as panggil_at", 'panggil_count']);

        $first = $calls->first();

        // ANTRIAN BERIKUTNYA yang MENUNGGU (belum dipanggil), urut paling lama dulu.
        // Sesuai sistem lama: kartu utama = yang sedang dipanggil; 4 kotak = beberapa
        // nomor berikutnya yang menunggu giliran (BUKAN riwayat panggilan).
        $waiting = $this->areaQuery($area, $tahap)
            ->whereNull("{$tahap}_panggil_at")
            ->when($rooms, fn ($q) => $q->whereIn('room_code', $rooms))
            // Urut berdasarkan NOMOR SLOT, bukan waktu tunggu - supaya pasien
            // booking tampil di posisi seharusnya (berselang dengan yang sudah
            // check-in), bukan terlempar ke belakang.
            ->orderBy('queue_no')
            ->orderByRaw('LENGTH(no_antrian), no_antrian')
            ->limit(9) // 6 baris daftar + 3 kartu "upcoming" di layar.
            // is_booking ikut diambil: pasien yang belum check-in tetap
            // ditampilkan (samar) supaya urutan terlihat utuh sejak awal.
            ->get(['no_antrian', 'room_code', 'counter', 'is_booking']);

        $next = $waiting->first();

        // TOTAL yang menunggu (tanpa limit) - layar hanya menampilkan beberapa
        // kartu pertama, angka ini memberi tahu sisanya tanpa puluhan kotak.
        // Hanya pasien NYATA yang dihitung: yang masih booking belum tentu
        // datang, jadi tidak boleh menggelembungkan angka antrian.
        $waitingTotal = $this->areaQuery($area, $tahap)
            ->nyata()
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
                return '-';
            }
            $v = preg_replace('/\s*\([^)]*\)\s*/', ' ', $v);              // buang "(Zona 18)"
            $v = preg_replace('/^(counter|loket|ruang|room)\s*/i', '', $v); // buang kata depan

            return trim($v) !== '' ? trim($v) : '-';
        };

        // FARMASI: layar sendiri butuh queue terpisah racik/non-racik, dan
        // sampai 4 kartu "sedang diproses" (semua panggilan aktif, bukan cuma
        // yang terbaru). $mkQueue pakai scope yang sama dgn konsol Farmasi.
        $farmasiExtra = [];
        if ($area === 'farmasi') {
            $mkQueue = fn (string $jenis) => Antrian::today()->farmasiQueue($jenis)
                ->whereNull('farmasi_panggil_at')
                ->orderBy('farmasi_tunggu_at')
                ->limit(12)
                ->get(['no_antrian'])
                ->map(fn ($r) => ['no' => $r->no_antrian])->values();

            $farmasiExtra = [
                'queue_racik'     => $mkQueue(Antrian::FARMASI_RACIK),
                'queue_non_racik' => $mkQueue(Antrian::FARMASI_NON_RACIK),
                'processing'      => $calls->take(4)->map(fn ($r) => [
                    'no'     => $r->no_antrian,
                    'tujuan' => $tujuan($r),
                ])->values(),
            ];
        }

        // CATATAN PRIVASI: endpoint ini PUBLIK (dipakai layar TV, tanpa login),
        // jadi `pasien_nama` sengaja TIDAK ikut dikirim - layar hanya
        // menampilkan nomor antrian & tujuan. Nama pasien hanya boleh muncul
        // di halaman terproteksi (mis. konsol dokter di queue/klinik).
        return response()->json([
            'current' => $first ? [
                'no'      => $first->no_antrian,
                // klinik → RUANG dokter; kasir/farmasi → counter.
                'tujuan'  => $tujuan($first),
                'counter' => $first->counter,
                'seq'     => $first->panggil_count, // berubah tiap panggil/ulang → pemicu animasi
                'at'      => optional($first->panggil_at)->timestamp,
            ] : null,
            'next' => $next ? [
                'no' => $next->no_antrian,
            ] : null,
            // Kartu antrian berikutnya. `booking` = pasien belum check-in →
            // ditampilkan SAMAR agar urutan terlihat utuh & tidak ada yang
            // terkesan menyelip saat pasien itu registrasi.
            'queue' => $waiting->map(fn ($r) => [
                'no'      => $r->no_antrian,
                'tujuan'  => $tujuan($r),
                'booking' => (bool) $r->is_booking,
            ])->values(),
            'waiting_total' => $waitingTotal,
            ...$farmasiExtra,
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
            // Dokter yg MENEMPATI ruang ini hari ini (login) - mirip islogin lama.
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

            // Upload lewat app kalau ada, else fallback foto lama di img-dokter
            // (dicocokkan by nama) - lihat DoctorPhoto::urlFor().
            $photo = $pid ? \App\Models\DoctorPhoto::urlFor($pid, $occ->paramedic_name ?? null) : null;

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
            ->get(['no_antrian', 'klinik_tunggu_at', 'is_booking']);

        // Baris `is_booking` (belum check-in) IKUT ditampilkan - supaya nomornya
        // sudah kelihatan dari awal & tak "meloncat" posisi begitu pasien check-in
        // - tapi ditandai `pending` supaya layar bisa merender lebih redup.
        $s2start = $s2['start'] ?? null;
        $sesi1 = []; $sesi2 = [];
        foreach ($rows as $r) {
            $jam = $r->klinik_tunggu_at ? $r->klinik_tunggu_at->format('H:i') : null;
            $item = ['ticket' => $r->no_antrian, 'pending' => (bool) $r->is_booking];
            if ($s2start && $jam !== null && $jam >= $s2start) {
                $sesi2[] = $item;
            } else {
                $sesi1[] = $item;
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
        return $p ?: '-';
    }
}
