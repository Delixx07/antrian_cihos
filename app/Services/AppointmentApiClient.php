<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Klien HTTP ke aplikasi Appointment. Antrian membaca data registrasi (antrian)
 * live dari sana lewat API, jadi tak perlu koneksi ODBC/SQL Server sendiri.
 * Autentikasi: header X-Api-Key (services.appointment.api_key).
 */
class AppointmentApiClient
{
    private string $baseUrl;
    private string $apiKey;
    private int $timeout;

    public function __construct()
    {
        $this->baseUrl = (string) config('services.appointment.base_url');
        $this->apiKey  = (string) config('services.appointment.api_key');
        $this->timeout = (int) config('services.appointment.timeout', 15);
    }

    /**
     * Semua registrasi hari ini (lintas dokter) - sumber untuk mengisi antrian
     * lokal (farmasi/kasir melayani semua pasien, bukan per dokter).
     *
     * @return array{ok:bool, rows:array, total:int, date:?string, error:?string, message:?string}
     */
    public function allRegistrations(?string $date = null): array
    {
        return $this->fetch(null, $date);
    }

    /**
     * Registrasi/antrian hari ini untuk seorang dokter.
     *
     * @return array{ok:bool, rows:array, total:int, date:?string, error:?string, message:?string}
     */
    public function doctorRegistrations(int $paramedicId, ?string $date = null): array
    {
        return $this->fetch($paramedicId, $date);
    }

    /**
     * Panggil endpoint registrasi; paramedicId null = semua.
     *
     * Server appointment kadang menjawab gagal/timeout sesaat (terpantau ±1
     * dari 3 permintaan). Karena itu permintaan DIULANG beberapa kali dengan
     * jeda pendek sebelum menyerah - jauh lebih murah daripada menampilkan
     * "data tidak tersedia" ke petugas padahal datanya ada.
     */
    private function fetch(?int $paramedicId, ?string $date): array
    {
        $attempts = 3;
        $last     = null;

        for ($i = 1; $i <= $attempts; $i++) {
            $res = $this->fetchOnce($paramedicId, $date);
            if ($res['ok']) {
                return $res;
            }
            $last = $res;

            if ($i < $attempts) {
                usleep(250000 * $i);   // 0,25 dtk lalu 0,5 dtk
            }
        }

        return $last;
    }

    /** Satu kali permintaan ke endpoint registrasi. */
    private function fetchOnce(?int $paramedicId, ?string $date): array
    {
        try {
            $resp = Http::withHeaders(['X-Api-Key' => $this->apiKey])
                ->timeout($this->timeout)
                ->acceptJson()
                ->get($this->baseUrl.'/api/queue/registrations', array_filter([
                    'paramedic_id' => $paramedicId,
                    'date'         => $date,
                ], fn ($v) => $v !== null && $v !== ''));

            $json = $resp->json();

            if ($resp->successful() && is_array($json) && ($json['ok'] ?? false)) {
                return [
                    'ok'    => true,
                    'rows'  => $json['rows'] ?? [],
                    'total' => (int) ($json['total'] ?? count($json['rows'] ?? [])),
                    'date'  => $json['date'] ?? $date,
                    'error' => null,
                    'message' => null,
                ];
            }

            // API menjawab tapi gagal (mis. 503 dari MEDINFRAS).
            return [
                'ok'      => false,
                'rows'    => [],
                'total'   => 0,
                'date'    => $date,
                'error'   => is_array($json) ? ($json['error'] ?? 'api_error') : 'api_error',
                'message' => is_array($json) ? ($json['message'] ?? 'Gagal mengambil data antrian.') : 'Gagal mengambil data antrian.',
            ];
        } catch (\Throwable $e) {
            Log::warning('AppointmentApiClient doctorRegistrations failed: '.$e->getMessage());

            return [
                'ok'      => false,
                'rows'    => [],
                'total'   => 0,
                'date'    => $date,
                'error'   => 'connection_failed',
                'message' => 'Tidak dapat menghubungi aplikasi Appointment. Pastikan aplikasi & jaringan aktif.',
            ];
        }
    }
}
