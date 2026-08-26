<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Membuat berkas audio pengumuman antrian dalam Bahasa Indonesia memakai
 * PIPER TTS (offline, gratis, tanpa API key).
 *
 * Alasan dibuat di SERVER, bukan di browser: Web Speech API bergantung pada
 * voice yang terpasang di tiap perangkat. Di Windows voice id-ID harus
 * dipasang manual, dan kualitasnya berbeda antar perangkat. Dengan render di
 * server, SEMUA layar memutar berkas audio yang sama persis tanpa setup apa pun.
 *
 * Hasil render di-cache di public/tts sehingga tiap kalimat hanya dirender
 * sekali; pemanggilan berikutnya langsung memakai berkas yang sudah ada.
 */
class SpeechSynthesizer
{
    /** Folder cache audio (relatif public/). */
    private const CACHE_DIR = 'tts';

    /** Buang berkas cache yang lebih tua dari ini (detik). 30 hari. */
    private const MAX_AGE = 2592000;

    /**
     * Batas waktu tunggu proses render (detik). Tanpa batas ini, proc_close()
     * menunggu proses selesai SEBERAPA PUN lama - kalau koneksi ke server
     * Edge TTS macet/lambat sesaat, request PHP ikut menggantung tanpa batas,
     * membuat jeda antara bel & suara terasa sangat lama. Begitu lewat batas
     * ini, proses dipaksa berhenti dan pemanggil otomatis jatuh ke suara
     * Web Speech API browser (lihat render()/available()).
     */
    private const RENDER_TIMEOUT_SECONDS = 8.0;

    /**
     * Kembalikan URL audio untuk sebuah teks, merender bila belum ada.
     * Null bila Piper tidak tersedia / gagal render (pemanggil harus punya
     * jalur cadangan, mis. Web Speech API di browser).
     */
    public function url(string $text): ?string
    {
        $text = trim($text);
        if ($text === '') {
            return null;
        }

        $file = $this->cacheName($text);
        $path = public_path(self::CACHE_DIR.'/'.$file);

        if (is_file($path) && filesize($path) > 0) {
            touch($path); // tandai masih dipakai agar tidak keburu dibersihkan
            return asset(self::CACHE_DIR.'/'.$file);
        }

        return $this->render($text, $path)
            ? asset(self::CACHE_DIR.'/'.$file)
            : null;
    }

    /** Nama berkas cache: hash teks + seluruh konfigurasi suara. */
    private function cacheName(string $text): string
    {
        $key = implode('|', [
            $text,
            config('tts.engine'),
            config('tts.edge_voice'), config('tts.edge_rate'), config('tts.edge_pitch'),
            config('tts.model'), config('tts.length_scale'),
        ]);
        $ext = config('tts.engine') === 'edge' ? 'mp3' : 'wav';

        return 'a'.substr(sha1($key), 0, 24).'.'.$ext;
    }

    /** Apakah mesin suara siap dipakai? */
    public function available(): bool
    {
        return config('tts.engine') === 'edge'
            ? $this->edgeAvailable()
            : $this->piperAvailable();
    }

    /** Edge TTS siap bila modul python edge_tts terpasang. */
    private function edgeAvailable(): bool
    {
        static $ok = null;
        if ($ok !== null) {
            return $ok;
        }

        $cmd = escapeshellarg(config('tts.python')).' -c "import edge_tts"';
        @exec($cmd.' 2>&1', $out, $code);

        return $ok = ($code === 0);
    }

    /** Piper siap bila binary + model ada. */
    private function piperAvailable(): bool
    {
        $bin   = config('tts.binary');
        $model = config('tts.model');

        return $bin && $model && is_file($bin) && is_file($model);
    }

    /** Render audio memakai mesin terpilih. True bila berhasil. */
    private function render(string $text, string $path): bool
    {
        $dir = dirname($path);
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        if (config('tts.engine') === 'edge') {
            if ($this->renderEdge($text, $path)) {
                $this->prune();

                return true;
            }
            // Edge gagal (mis. internet putus) → jangan sampai layar bisu:
            // biarkan pemanggil memakai suara browser sebagai cadangan.
            return false;
        }

        return $this->renderPiper($text, $path);
    }

    /**
     * Edge TTS (Microsoft) - voice Indonesia neural, gratis tanpa API key.
     * Perlu koneksi internet saat render; hasilnya di-cache.
     */
    private function renderEdge(string $text, string $path): bool
    {
        /*
         * CATATAN WINDOWS: cmd.exe memakan karakter '%' (dianggap penanda
         * variabel), sehingga "--rate=-8%" sampai ke edge-tts sebagai "-8 "
         * dan ditolak. Menggandakan jadi '%%' pun tidak selalu berhasil
         * tergantung cara PHP memanggil shell.
         *
         * Solusi: JANGAN kirim '%' lewat baris perintah sama sekali. Nilai
         * rate/pitch dioper ke skrip Python kecil lewat STDIN sebagai JSON,
         * lalu skrip itu yang memanggil edge-tts.
         */
        $script = $this->edgeScript();
        if (! $script) {
            return false;
        }

        $payload = json_encode([
            'text'   => $text,
            'out'    => $path,
            'voice'  => (string) config('tts.edge_voice'),
            'rate'   => (string) config('tts.edge_rate'),
            'pitch'  => (string) config('tts.edge_pitch'),
        ], JSON_UNESCAPED_UNICODE);

        $cmd = escapeshellarg(config('tts.python')).' '.escapeshellarg($script);

        [$code, $out, $timedOut] = $this->runWithTimeout($cmd, $payload);

        if ($timedOut) {
            Log::warning('TTS: render Edge timeout ('.self::RENDER_TIMEOUT_SECONDS.'s) - proses dipaksa berhenti');
            @unlink($path);

            return false;
        }

        if ($code !== 0 || ! is_file($path) || filesize($path) === 0) {
            Log::warning('TTS: render Edge gagal', [
                'exit' => $code,
                'out'  => mb_substr($out, -900),
            ]);
            @unlink($path);

            return false;
        }

        return true;
    }

    /**
     * Jalankan proses dgn input STDIN, dibatasi waktu - TANPA ini,
     * stream_get_contents()/proc_close() menunggu proses selesai seberapa pun
     * lama (lihat catatan RENDER_TIMEOUT_SECONDS). Poll status proses secara
     * berkala; begitu lewat batas waktu, proses dipaksa berhenti.
     *
     * @return array{0: int, 1: string, 2: bool} [exit_code, stdout+stderr, timed_out]
     */
    private function runWithTimeout(string $cmd, string $stdin): array
    {
        $desc = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];

        // bypass_shell: PENTING di Windows - tanpa ini proc_open menjalankan
        // command lewat cmd.exe, dan proc_terminate() cuma membunuh cmd.exe-
        // nya (proses python/piper ASLI di baliknya tetap jalan, tidak
        // benar-benar terpotong).
        $proc = @proc_open($cmd, $desc, $pipes, null, null, ['bypass_shell' => true]);
        if (! is_resource($proc)) {
            return [-1, 'gagal menjalankan proses', false];
        }

        fwrite($pipes[0], $stdin);
        fclose($pipes[0]);

        // JANGAN baca pipe stdout/stderr di sini - stream_set_blocking(false)
        // pada pipe proc_open TIDAK berlaku andal di Windows (batasan PHP di
        // Windows), sehingga stream_get_contents() tetap memblokir sampai
        // proses selesai dan meniadakan polling batas waktu di bawah. Cukup
        // poll proc_get_status() saja (tak menyentuh pipe), baca isinya
        // SETELAH loop selesai/proses dihentikan.
        $deadline = microtime(true) + self::RENDER_TIMEOUT_SECONDS;
        $timedOut = false;

        while (true) {
            $status = proc_get_status($proc);
            if (! $status['running']) {
                break;
            }
            if (microtime(true) >= $deadline) {
                $timedOut = true;
                proc_terminate($proc, 9);
                break;
            }
            usleep(50000); // 50ms
        }

        $out  = stream_get_contents($pipes[1]);
        $out .= stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $code = proc_close($proc);

        return [$timedOut ? -1 : $code, $out, $timedOut];
    }

    /**
     * Tulis (sekali) skrip Python pembantu untuk Edge TTS dan kembalikan
     * lokasinya. Parameter dioper lewat STDIN agar '%' tidak dirusak cmd.exe.
     */
    private function edgeScript(): ?string
    {
        $path = storage_path('app/edge_tts_render.py');

        $code = <<<'PY'
import sys, json, asyncio
import edge_tts

def main():
    cfg = json.loads(sys.stdin.read())
    async def go():
        c = edge_tts.Communicate(
            cfg["text"], cfg["voice"],
            rate=cfg.get("rate", "+0%"),
            pitch=cfg.get("pitch", "+0Hz"),
        )
        await c.save(cfg["out"])
    asyncio.run(go())

if __name__ == "__main__":
    main()
PY;

        if (! is_file($path) || md5_file($path) !== md5($code)) {
            $dir = dirname($path);
            if (! is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
            if (@file_put_contents($path, $code) === false) {
                Log::warning('TTS: gagal menulis skrip Edge TTS', ['path' => $path]);

                return null;
            }
        }

        return $path;
    }

    /** Piper (offline). True bila berhasil. */
    private function renderPiper(string $text, string $path): bool
    {
        if (! $this->piperAvailable()) {
            return false;
        }

        // Teks dikirim lewat STDIN (bukan argumen) agar aman dari karakter
        // khusus & tidak kena batas panjang baris perintah Windows.
        $cmd = sprintf(
            '%s --model %s --output_file %s --length_scale %s',
            escapeshellarg(config('tts.binary')),
            escapeshellarg(config('tts.model')),
            escapeshellarg($path),
            escapeshellarg((string) config('tts.length_scale'))
        );

        [$code, $out, $timedOut] = $this->runWithTimeout($cmd, $text);

        if ($timedOut) {
            Log::warning('TTS: render Piper timeout ('.self::RENDER_TIMEOUT_SECONDS.'s) - proses dipaksa berhenti');
            @unlink($path);

            return false;
        }

        if ($code !== 0 || ! is_file($path) || filesize($path) === 0) {
            Log::warning('TTS: render gagal', ['exit' => $code, 'stderr' => mb_substr($out, 0, 500)]);
            @unlink($path);

            return false;
        }

        $this->prune();

        return true;
    }

    /** Hapus berkas cache lama agar folder tidak membengkak. */
    private function prune(): void
    {
        // Cukup sesekali saja - 1 dari 50 render.
        if (random_int(1, 50) !== 1) {
            return;
        }

        $dir = public_path(self::CACHE_DIR);
        foreach (glob($dir.'/*.{wav,mp3}', GLOB_BRACE) ?: [] as $f) {
            if (filemtime($f) < time() - self::MAX_AGE) {
                @unlink($f);
            }
        }
    }
}
