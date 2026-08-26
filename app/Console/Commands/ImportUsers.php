<?php

namespace App\Console\Commands;

use App\Models\AntrianAccess;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Impor user dari direktori RS (dbuser.user_detail) menjadi HAK AKSES aplikasi
 * antrian (tabel antrian_access), dipetakan otomatis berdasarkan departemen.
 *
 * Password TIDAK disalin - saat login, password tetap diverifikasi ke dbuser
 * (lihat AuthController). Tabel antrian_access hanya menyimpan IZIN & peran.
 *
 *   php artisan antrian:import-users --dry-run   # lihat dulu, tanpa menyimpan
 *   php artisan antrian:import-users             # jalankan
 *   php artisan antrian:import-users --all       # ikut dept yang tak relevan
 */
class ImportUsers extends Command
{
    protected $signature = 'antrian:import-users
                            {--dry-run : Tampilkan hasil tanpa menyimpan}
                            {--all : Ikut impor dept yang tidak memakai antrian (role spv, diblokir)}';

    protected $description = 'Impor user dari dbuser.user_detail ke antrian_access + petakan role';

    /**
     * Peta DEPARTEMEN (dbuser.dept_detail) → ROLE aplikasi antrian.
     * Dept yang tidak ada di sini dianggap tidak memakai aplikasi antrian.
     */
    private const DEPT_ROLE = [
        1  => 'administrator',        // Super Admin
        7  => 'klinik',               // General Practitioner
        8  => 'kasir_administrasi',   // Registration & Cashier
        10 => 'farmasi',              // Pharmacy
        20 => 'klinik',               // Specialist Doctor
        24 => 'spv',                  // Manager

        // Dept 23 "Coordinator" (59 orang) SENGAJA tidak dipetakan: role spv
        // memberi akses melihat antrian SELURUH dokter, terlalu luas untuk
        // diberikan otomatis. Bila ada yang membutuhkan, admin menambahkannya
        // lewat menu User Management.
    ];

    /** Urutan prioritas bila user punya beberapa dept sekaligus. */
    private const PRIORITY = ['administrator', 'klinik', 'kasir_administrasi', 'farmasi', 'spv'];

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $all = (bool) $this->option('all');

        $this->info($dry ? 'MODE UJI COBA - tidak ada data yang disimpan.' : 'Mengimpor user…');

        // Cocokkan dokter: username dbuser = paramedic_code di master.doctors.
        $doctors = DB::connection('master')->table('doctors')
            ->pluck('paramedic_id', 'paramedic_code');

        $rows = DB::connection('dbuser')->table('user_detail')
            ->select('user', 'name', 'akses_by_dept')->get();

        $stat = ['baru' => 0, 'diperbarui' => 0, 'dilewati' => 0, 'per_role' => []];

        foreach ($rows as $u) {
            $username = trim((string) $u->user);
            if ($username === '') {
                continue;
            }

            $role = $this->mapRole((string) $u->akses_by_dept);

            if ($role === null) {
                if (! $all) {
                    $stat['dilewati']++;

                    continue;
                }
                $role = 'spv';   // --all: beri peran paling terbatas
            }

            // Dokter: sertakan paramedic_id agar antriannya bisa terhubung.
            $pid  = $doctors[$username] ?? null;
            $pname = null;
            if ($pid) {
                $role = 'klinik';   // terdaftar sbg dokter → pasti role klinik
                $pname = $u->name;
            }

            $stat['per_role'][$role] = ($stat['per_role'][$role] ?? 0) + 1;

            if ($dry) {
                continue;
            }

            $existing = AntrianAccess::where('username', $username)->first();

            // JANGAN timpa akun yang sudah diatur manual (mis. admin, akun mesin)
            // - hanya lengkapi data yang masih kosong.
            if ($existing) {
                $existing->fill(array_filter([
                    'name'           => $existing->name ?: $u->name,
                    'paramedic_id'   => $existing->paramedic_id ?: $pid,
                    'paramedic_name' => $existing->paramedic_name ?: $pname,
                ]))->save();
                $stat['diperbarui']++;

                continue;
            }

            AntrianAccess::create([
                'username'       => $username,
                'name'           => $u->name,
                'role'           => $role,
                'paramedic_id'   => $pid,
                'paramedic_name' => $pname,
                'is_blocked'     => false,
            ]);
            $stat['baru']++;
        }

        $this->newLine();
        $this->line('  Baru ditambahkan : '.$stat['baru']);
        $this->line('  Diperbarui       : '.$stat['diperbarui']);
        $this->line('  Dilewati (dept tak relevan): '.$stat['dilewati']);
        $this->newLine();
        $this->line('  Rincian per role:');
        ksort($stat['per_role']);
        foreach ($stat['per_role'] as $r => $n) {
            $this->line(sprintf('    %-20s %d', $r, $n));
        }

        if ($dry) {
            $this->newLine();
            $this->comment('  Jalankan tanpa --dry-run untuk benar-benar menyimpan.');
        }

        return self::SUCCESS;
    }

    /** Tentukan role dari daftar dept ("18;23"), pakai prioritas bila ganda. */
    private function mapRole(string $deptCsv): ?string
    {
        $roles = [];
        foreach (preg_split('/[;,]/', $deptCsv) as $d) {
            $d = (int) trim($d);
            if ($d && isset(self::DEPT_ROLE[$d])) {
                $roles[] = self::DEPT_ROLE[$d];
            }
        }
        if (! $roles) {
            return null;
        }

        foreach (self::PRIORITY as $p) {
            if (in_array($p, $roles, true)) {
                return $p;
            }
        }

        return $roles[0];
    }
}
