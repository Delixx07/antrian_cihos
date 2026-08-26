<?php

namespace App\Database;

/**
 * Koneksi SQL Server via ODBC (odbc_connect), sesuai revisi yang diminta.
 *
 * Menggantikan pemakaian DB::connection('sqlsrv') (pdo_sqlsrv) dengan
 * odbc_connect / odbc_exec. Kredensial dibaca dari .env (SQLSRV_*), bukan
 * di-hardcode, supaya aman dan mudah dipindah antar device.
 *
 * Butuh extension `odbc` aktif di php.ini dan ODBC Driver for SQL Server
 * terpasang di Windows. Server RS menolak enkripsi paksa -> Encrypt=no.
 *
 * Pemakaian:
 *   $rows = OdbcSqlServer::select(
 *       "SELECT * FROM vRegistration WHERE CAST(RegistrationDate AS date) = ?",
 *       [$date]
 *   );
 */
class OdbcSqlServer
{
    /** @var resource|null koneksi ODBC yang di-reuse selama satu request */
    protected static $conn = null;

    /**
     * Ambil (atau buka) koneksi ODBC ke SQL Server.
     *
     * @return resource
     * @throws \RuntimeException kalau extension/driver/koneksi gagal
     */
    public static function connection()
    {
        if (static::$conn !== null) {
            return static::$conn;
        }

        if (!function_exists('odbc_connect')) {
            throw new \RuntimeException(
                "Extension 'odbc' belum aktif di PHP. Aktifkan di php.ini (extension=odbc)."
            );
        }

        $host     = env('SQLSRV_HOST', 'localhost');
        $port     = env('SQLSRV_PORT', '1433');
        $database = env('SQLSRV_DATABASE', 'master');
        $username = env('SQLSRV_USERNAME', 'sa');
        $password = env('SQLSRV_PASSWORD', '');
        $encrypt  = env('SQLSRV_ENCRYPT', 'no');
        $trust    = env('SQLSRV_TRUST_SERVER_CERTIFICATE', 'true');

        // Coba beberapa nama driver ODBC, dari yang paling baru ke lama.
        $drivers = [
            'ODBC Driver 18 for SQL Server',
            'ODBC Driver 17 for SQL Server',
            'SQL Server Native Client 11.0',
            'SQL Server',
        ];

        $lastError = '';
        foreach ($drivers as $driver) {
            $dsn = sprintf(
                'Driver={%s};Server=%s,%s;Database=%s;Encrypt=%s;TrustServerCertificate=%s;',
                $driver,
                $host,
                $port,
                $database,
                $encrypt === 'yes' ? 'yes' : 'no',
                $trust === 'true' || $trust === true ? 'yes' : 'no'
            );

            $conn = @odbc_connect($dsn, $username, $password);
            if ($conn) {
                static::$conn = $conn;
                return $conn;
            }
            $lastError = odbc_errormsg();
        }

        throw new \RuntimeException(
            "Gagal menyambung SQL Server via ODBC. Cek jaringan RS & driver ODBC. Error: {$lastError}"
        );
    }

    /**
     * Jalankan SELECT dan kembalikan semua baris sebagai array asosiatif.
     *
     * Placeholder pakai tanda tanya (?) - parameter di-bind lewat odbc_execute
     * (prepared statement) supaya aman dari SQL injection.
     *
     * @param  string  $sql
     * @param  array   $bindings
     * @return array<int, array<string, mixed>>
     */
    public static function select(string $sql, array $bindings = []): array
    {
        $conn = static::connection();

        $stmt = odbc_prepare($conn, $sql);
        if ($stmt === false) {
            throw new \RuntimeException('odbc_prepare gagal: '.odbc_errormsg($conn));
        }

        // '@' meredam WARNING informatif dari SQL Server (mis. SQLSTATE 01003
        // "Null value is eliminated by an aggregate") yang bukan kegagalan.
        // Kegagalan sungguhan tetap terdeteksi karena return-nya false → throw.
        if (!@odbc_execute($stmt, array_values($bindings))) {
            throw new \RuntimeException('odbc_execute gagal: '.odbc_errormsg($conn));
        }

        $rows = [];
        while ($row = odbc_fetch_array($stmt)) {
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Sama seperti select(), tapi hasilnya di-cache selama $ttl detik.
     *
     * Untuk data SQL Server yang jarang berubah (daftar unit, dokter, jadwal),
     * ini menghindari query berat ke server RS setiap kali halaman dibuka -
     * penyebab utama "buffering lama". Kunci cache diturunkan dari SQL+bindings.
     *
     * @param  string  $sql
     * @param  array   $bindings
     * @param  int     $ttl  detik (default 300 = 5 menit)
     * @return array<int, array<string, mixed>>
     */
    public static function cached(string $sql, array $bindings = [], int $ttl = 300): array
    {
        $key = 'odbc:'.md5($sql.'|'.serialize($bindings));

        return \Illuminate\Support\Facades\Cache::remember(
            $key, $ttl, fn () => static::select($sql, $bindings)
        );
    }

    /**
     * Jalankan SELECT dan kembalikan satu nilai skalar (kolom pertama baris pertama).
     *
     * @param  string  $sql
     * @param  array   $bindings
     * @return mixed
     */
    public static function scalar(string $sql, array $bindings = [])
    {
        $rows = static::select($sql, $bindings);
        if (empty($rows)) {
            return null;
        }
        $first = $rows[0];
        return reset($first);
    }

    /**
     * Sama seperti scalar(), tapi hasilnya di-cache $ttl detik. Untuk hitungan
     * yang cukup stabil (mis. jumlah appointment per tanggal) dan dipanggil
     * berulang, agar tak query ke server RS tiap kali.
     *
     * @param  string  $sql
     * @param  array   $bindings
     * @param  int     $ttl  detik (default 120 = 2 menit)
     * @return mixed
     */
    public static function cachedScalar(string $sql, array $bindings = [], int $ttl = 120)
    {
        $key = 'odbc:scalar:'.md5($sql.'|'.serialize($bindings));

        return \Illuminate\Support\Facades\Cache::remember(
            $key, $ttl, fn () => static::scalar($sql, $bindings)
        );
    }

    /** Tutup koneksi (opsional; PHP menutup otomatis di akhir request). */
    public static function disconnect(): void
    {
        if (static::$conn !== null) {
            odbc_close(static::$conn);
            static::$conn = null;
        }
    }
}
