<?php

namespace App\Http\Controllers;

use App\Models\Antrian;
use App\Models\KioskRegistration;
use App\Services\AnnouncementQueue;
use App\Services\SpeechSynthesizer;
use Illuminate\Http\Request;

class SpeechController extends Controller
{
    
    private const DIGITS = [
        '0' => 'nol', '1' => 'satu', '2' => 'dua', '3' => 'tiga', '4' => 'empat',
        '5' => 'lima', '6' => 'enam', '7' => 'tujuh', '8' => 'delapan', '9' => 'sembilan',
    ];

    
    private const LETTERS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';

    /** Audio untuk satu panggilan  */
    public function show(Request $request, SpeechSynthesizer $tts)
    {
        $data = $request->validate([
            'no'   => ['required', 'string', 'max:20'],
            'dest' => ['nullable', 'string', 'max:20'],
            'area' => ['nullable', 'string', 'in:klinik,kasir,farmasi,registrasi'],
        ]);

        $text = $this->sentence(
            $data['no'],
            $data['dest'] ?? null,
            $data['area'] ?? 'klinik'
        );

        if (! config('tts.enabled') || ! $tts->available()) {
            // Layar akan memakai Web Speech API browser sebagai cadangan.
            return response()->json(['url' => null, 'text' => $text, 'reason' => 'piper-unavailable']);
        }

        return response()->json(['url' => $tts->url($text), 'text' => $text]);
    }

    public function enqueue(Request $request, AnnouncementQueue $queue)
    {
        $data = $request->validate([
            'no'   => ['required', 'string', 'max:20'],
            'dest' => ['nullable', 'string', 'max:20'],
            'area' => ['nullable', 'string', 'in:klinik,kasir,farmasi,registrasi'],
            'seq'  => ['nullable', 'integer'],
        ]);

        $added = $queue->push(
            $data['no'],
            $data['dest'] ?? null,
            $data['area'] ?? 'klinik',
            (int) ($data['seq'] ?? 0)
        );

        return response()->json(['queued' => $added, 'pending' => $queue->pending()]);
    }

    public function nextAnnouncement(AnnouncementQueue $queue, SpeechSynthesizer $tts)
    {
        // Pindai panggilan baru dari SEMUA area langsung ke database, supaya
        // speaker tetap berbunyi walau layar area itu tidak sedang dibuka.
        $this->scanCalls($queue);

        $item = $queue->next();
        if (! $item) {
            return response()->json(['item' => null, 'pending' => $queue->pending()]);
        }

        $text = $this->sentence($item['no'], $item['dest'] ?? null, $item['area'] ?? 'klinik');
        $url  = (config('tts.enabled') && $tts->available()) ? $tts->url($text) : null;

        if (! $url) {
            $queue->done($item['key']);
        }

        return response()->json([
            'item' => [
                'key'  => $item['key'],
                'no'   => $item['no'],
                'dest' => $item['dest'] ?? null,
                'area' => $item['area'] ?? 'klinik',
                'url'  => $url,
                'text' => $text,
            ],
            'pending' => $queue->pending(),
        ]);
    }

    public function doneAnnouncement(Request $request, AnnouncementQueue $queue)
    {
        $queue->done($request->query('key'));

        return response()->json(['ok' => true, 'pending' => $queue->pending()]);
    }

    /**
     * Pindai panggilan yang SEDANG aktif di semua area, lalu daftarkan ke
     * antrean suara.
     *
     * Ini membuat speaker pusat mandiri: suara tetap keluar walaupun layar
     * area tersebut TIDAK sedang dibuka di PC mana pun. Duplikat dicegah oleh
     * kunci area|nomor|panggil_count di AnnouncementQueue, sehingga panggilan
     * yang sama tidak diumumkan dua kali - tapi RECALL (panggil_count naik)
     * tetap terdeteksi sebagai panggilan baru.
     */
    private function scanCalls(AnnouncementQueue $queue): void
    {
        // Pemindaian PERTAMA setelah speaker dinyalakan hanya mencatat, tidak
        // mengumumkan - supaya panggilan lama tidak diputar ulang.
        $first = ! \Illuminate\Support\Facades\Cache::get('ann:scan_started', false);
        \Illuminate\Support\Facades\Cache::put('ann:scan_started', true, 600);

        // klinik & kasir: pasien benar-benar berada di tahap itu, jadi query
        // via kolom `tahap` valid. FARMASI beda - pasien ber-resep TETAP di
        // tahap kasir sepanjang alur (lihat Antrian::selesaiFarmasi()), jadi
        // filter lewat status_resep, bukan tahap (sama seperti
        // DisplayController::areaQuery() untuk area farmasi).
        $areas = [
            'klinik' => Antrian::TAHAP_KLINIK,
            'kasir'  => Antrian::TAHAP_KASIR,
        ];

        foreach ($areas as $area => $tahap) {
            $rows = Antrian::today()
                ->where('tahap', $tahap)
                ->whereNotNull("{$tahap}_panggil_at")
                ->whereNull("{$tahap}_selesai_at")
                ->where("{$tahap}_panggil_at", '>=', now()->subMinutes(3))
                ->orderBy("{$tahap}_panggil_at")
                ->limit(20)
                ->get(['no_antrian', 'counter', 'room_code', 'panggil_count']);

            $this->announceRows($queue, $rows, $area, $first,
                fn ($r) => $area === 'klinik' ? $r->room_code : $r->counter);
        }

        $farmasiRows = Antrian::today()
            ->whereIn('status_resep', [Antrian::RESEP_RACIK, Antrian::RESEP_NON_RACIK])
            ->whereNotNull('farmasi_panggil_at')->whereNull('farmasi_selesai_at')
            ->where('farmasi_panggil_at', '>=', now()->subMinutes(3))
            ->orderBy('farmasi_panggil_at')->limit(20)
            ->get(['no_antrian', 'counter', 'panggil_count']);
        $this->announceRows($queue, $farmasiRows, 'farmasi', $first, fn ($r) => $r->counter);

        // REGISTRASI: data ada di KioskRegistration (tabel & koneksi terpisah),
        // bukan tabel `antrian` - lihat DisplayController::json() cabang registrasi.
        $regRows = KioskRegistration::whereDate('tanggal', today())
            ->whereNotNull('panggil_at')->whereNull('selesai_at')
            ->where('panggil_at', '>=', now()->subMinutes(3))
            ->orderBy('panggil_at')->limit(20)
            ->get(['rg_no as no_antrian', 'counter', 'panggil_count']);
        $this->announceRows($queue, $regRows, 'registrasi', $first, fn ($r) => $r->counter);
    }

    /** Umumkan (atau catat, bila $first) satu batch baris panggilan area tertentu. */
    private function announceRows(AnnouncementQueue $queue, $rows, string $area, bool $first, \Closure $destOf): void
    {
        foreach ($rows as $r) {
            $seq = (int) $r->panggil_count;

            if ($queue->seen($r->no_antrian, $area, $seq)) {
                continue;   // sudah pernah diumumkan/dicatat
            }

            $dest = $this->cleanDest((string) $destOf($r));

            // Saat speaker BARU dinyalakan, panggilan yang sudah berjalan
            // hanya dicatat (tidak diumumkan) agar tidak memutar riwayat.
            if ($first) {
                $queue->markSeen($r->no_antrian, $area, $seq);

                continue;
            }

            if ($queue->push($r->no_antrian, $dest !== '' ? $dest : null, $area, $seq)) {
                $queue->markSeen($r->no_antrian, $area, $seq);
            }
        }
    }

    private function sentence(string $no, ?string $dest, string $area): string
    {
        // klinik → "ruangan"; kasir/farmasi → "counter" (istilah yang dipakai
        // di lapangan & pada program lama, bukan "loket").
        $word = $area === 'klinik' ? 'ruangan' : 'counter';
        $dest = trim((string) $dest);

        $text = 'Nomor antrian, '.$this->spell($no);

        if ($dest !== '' && $dest !== '-') {
            $said = $area === 'klinik' ? $this->roomSpoken($dest) : $this->spell($dest);
            if ($said !== '') {
                $text .= ', silakan menuju '.$word.', '.$said;
            }
        }

        return $text.'.';
    }

    /**
     * Cara menyebut nomor RUANG: disebut LENGKAP, dipecah per DUA digit,
     * tiap pasangan dibaca sebagai bilangan.
     *
     *   "1838" → "delapan belas, tiga puluh delapan"
     *   "1859" → "delapan belas, lima puluh sembilan"
     *   "1105" → "sebelas, nol lima"
     *
     * Dipecah 2-2 (bukan "seribu delapan ratus tiga puluh delapan") supaya
     * lebih pendek & mudah dicocokkan dengan angka di papan nama ruangan.
     * Pasangan yang puluhannya NOL tetap menyebut "nol" agar terdengar dua
     * digit ("05" → "nol lima"), tidak menyusut jadi "lima" saja.
     */
    private function roomSpoken(string $dest): string
    {
        if (! ctype_digit($dest)) {
            return $this->spell($dest);
        }

        // Jumlah digit ganjil → beri nol di depan supaya rapi berpasangan.
        $d = strlen($dest) % 2 === 1 ? '0'.$dest : $dest;

        $parts = [];
        foreach (str_split($d, 2) as $pair) {
            $parts[] = $pair[0] === '0'
                ? self::DIGITS[$pair[0]].' '.self::DIGITS[$pair[1]]   // "nol lima"
                : $this->numberWords((int) $pair);                     // "delapan belas"
        }

        return implode(', ', $parts);
    }

    private function numberWords(int $n): string
    {
        $satuan = ['nol', 'satu', 'dua', 'tiga', 'empat', 'lima',
                   'enam', 'tujuh', 'delapan', 'sembilan'];

        if ($n < 10) {
            return $satuan[$n];
        }
        if ($n < 20) {
            return match ($n) {
                10 => 'sepuluh',
                11 => 'sebelas',
                default => $satuan[$n - 10].' belas',
            };
        }

        $puluh = $satuan[intdiv($n, 10)].' puluh';
        $sisa  = $n % 10;

        return $sisa ? $puluh.' '.$satuan[$sisa] : $puluh;
    }

    private function spell(string $s): string
    {
        $out    = [];
        $digits = '';

        $flush = function () use (&$out, &$digits) {
            if ($digits !== '') {
                $out[]  = $this->digitsSpoken($digits);
                $digits = '';
            }
        };

        foreach (str_split(strtoupper(trim($s))) as $ch) {
            if (isset(self::DIGITS[$ch])) {
                $digits .= $ch;
            } elseif (str_contains(self::LETTERS, $ch)) {
                $flush();
                $out[] = $ch.'.,';
            }
        }
        $flush();

        return trim(implode(' ', $out));
    }

    /**
     * Sebutkan RUN angka pada nomor antrian (mis. "010" dari "RG010")
     * digit-demi-digit dengan jeda jelas antar tiap digit - supaya terdengar
     * tegas & tidak ambigu (mis. "F, D, 0, 0, 1" - bukan "FD, nol sepuluh"
     * yang terdengar seperti satu bilangan).
     *
     *   "001" → "nol, nol, satu"
     *   "010" → "nol, satu, nol"
     */
    private function digitsSpoken(string $digits): string
    {
        return implode(', ', array_map(fn ($ch) => self::DIGITS[$ch], str_split($digits)));
    }
}
