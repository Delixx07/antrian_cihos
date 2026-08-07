<?php

namespace App\Http\Controllers;

use App\Models\AntrianAccess;
use App\Models\Doctor;
use App\Models\UserDetail;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Manajemen User antrian. Login diverifikasi ke dbuser; controller ini mengelola
 * SIAPA yang boleh masuk + perannya (tabel lokal antrian_access). Hanya admin.
 */
class UserController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $users = AntrianAccess::query()
            ->when($q !== '', fn ($query) => $query->where(function ($w) use ($q) {
                $w->where('username', 'like', "%{$q}%")
                    ->orWhere('name', 'like', "%{$q}%")
                    ->orWhere('paramedic_name', 'like', "%{$q}%");
            }))
            ->orderBy('name')
            ->get();

        return view('users.index', [
            'users' => $users,
            'roles' => AntrianAccess::ROLES,
            'q'     => $q,
        ]);
    }

    /** AJAX: cari user di direktori RS (dbuser) untuk ditambahkan. */
    public function searchDirectory(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        $rows = UserDetail::query()
            ->where(function ($w) use ($q) {
                $w->where('user', 'like', "%{$q}%")
                    ->orWhere('name', 'like', "%{$q}%")
                    ->orWhere('NIK', 'like', "%{$q}%");
            })
            ->orderBy('name')
            ->limit(25)
            ->get(['user', 'name', 'NIK']);

        // Tandai yang sudah punya akses (agar tak dobel).
        $existing = AntrianAccess::whereIn('username', $rows->pluck('user'))->pluck('username')->flip();

        return response()->json($rows->map(fn ($u) => [
            'username' => $u->user,
            'name'     => $u->name ?: $u->user,
            'nik'      => $u->NIK,
            'added'    => $existing->has($u->user),
        ]));
    }

    /** AJAX: cari dokter di master (untuk role Klinik). */
    public function searchDoctors(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $rows = Doctor::query()
            ->when($q !== '', fn ($query) => $query->where(function ($w) use ($q) {
                $w->where('paramedic_name', 'like', "%{$q}%")
                    ->orWhere('paramedic_code', 'like', "%{$q}%");
            }))
            ->orderBy('paramedic_name')
            ->limit(30)
            ->get(['paramedic_id', 'paramedic_code', 'paramedic_name', 'specialty_name']);

        return response()->json($rows->map(fn ($d) => [
            'id'        => (int) $d->paramedic_id,
            'code'      => $d->paramedic_code,
            'name'      => $d->paramedic_name,
            'specialty' => $d->specialty_name ?: '-',
        ]));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        AntrianAccess::create($data);

        return redirect()->route('users.index')->with('ok', 'User berhasil ditambahkan.');
    }

    public function update(Request $request, AntrianAccess $user)
    {
        $data = $this->validated($request, $user->id);

        // Username tak boleh diganti setelah dibuat (identitas ke dbuser).
        unset($data['username']);

        $user->update($data);

        return redirect()->route('users.index')->with('ok', 'User berhasil diperbarui.');
    }

    public function destroy(AntrianAccess $user)
    {
        // Jangan biarkan admin terakhir terhapus (kunci diri sendiri keluar).
        if ($user->isAdmin() && AntrianAccess::where('role', AntrianAccess::ROLE_ADMIN)->count() <= 1) {
            return back()->with('error', 'Tidak bisa menghapus administrator terakhir.');
        }

        $user->delete();

        return redirect()->route('users.index')->with('ok', 'User dihapus.');
    }

    /** Validasi + normalisasi field user. */
    private function validated(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'username'    => [
                'required', 'string', 'max:100',
                Rule::unique('antrian_access', 'username')->ignore($ignoreId),
            ],
            'name'        => ['nullable', 'string', 'max:150'],
            // Password lokal opsional: bila diisi, akun login tanpa dbuser
            // (untuk akun mesin/super admin). Bila kosong, verifikasi ke dbuser.
            'password'    => ['nullable', 'string', 'min:5', 'max:100'],
            'role'        => ['required', Rule::in(array_keys(AntrianAccess::ROLES))],
            'paramedic_id'=> ['nullable', 'integer'],
            'counter'     => ['nullable', 'string', 'max:100'],
            'room_code'   => ['nullable', 'string', 'max:50'],
            'zona'        => ['nullable', 'string', 'max:50'],
            'is_blocked'  => ['nullable', 'boolean'],
        ]);

        $isKlinik = $data['role'] === AntrianAccess::ROLE_KLINIK;

        // Role Klinik wajib terikat ke seorang dokter.
        if ($isKlinik && empty($data['paramedic_id'])) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'paramedic_id' => 'Role Klinik wajib memilih dokter.',
            ]);
        }

        // Lengkapi nama dokter (cache) bila role Klinik.
        $paramedicName = null;
        if ($isKlinik && ! empty($data['paramedic_id'])) {
            $doc = Doctor::find($data['paramedic_id']);
            $paramedicName = $doc?->paramedic_name;
        } else {
            $data['paramedic_id'] = null;
        }

        $out = [
            'username'       => $data['username'],
            'name'           => $data['name'] ?: $data['username'],
            'role'           => $data['role'],
            'paramedic_id'   => $data['paramedic_id'],
            'paramedic_name' => $paramedicName,
            'counter'        => $data['counter'] ?? null,
            'room_code'      => $data['room_code'] ?? null,
            'zona'           => $data['zona'] ?? null,
            'is_blocked'     => (bool) ($data['is_blocked'] ?? false),
        ];

        // Set password lokal hanya bila diisi (biarkan yang lama saat edit kosong).
        if (! empty($data['password'])) {
            $out['password'] = \Illuminate\Support\Facades\Hash::make($data['password']);
        }

        return $out;
    }
}
