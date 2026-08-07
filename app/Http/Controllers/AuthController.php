<?php

namespace App\Http\Controllers;

use App\Models\AntrianAccess;
use App\Models\UserDetail;
use App\Services\RoomOccupancy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Login sistem antrian — verifikasi ke direktori RS (dbuser.user_detail, SHA1).
 * Sesi menyimpan identitas minimal; tak ada Sign Up (user dibuat oleh admin RS).
 */
class AuthController extends Controller
{
    public function showLogin(Request $request)
    {
        if ($request->session()->get('auth')) {
            return redirect()->route('home');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
            'remember' => ['nullable'],
        ]);

        $invalid = fn () => back()->withInput($request->only('username'))
            ->withErrors(['username' => 'Username atau password salah.']);

        try {
            $access = AntrianAccess::forUser($data['username']);

            // Akun dengan password LOKAL (mis. super admin / akun mesin) diverifikasi
            // langsung tanpa dbuser. Selain itu, verifikasi ke direktori RS (dbuser).
            if ($access && $access->hasLocalPassword()) {
                if (! $access->checkLocalPassword($data['password'])) {
                    return $invalid();
                }
                $displayName = $access->name ?: $access->username;
            } else {
                $ud = UserDetail::where('user', $data['username'])->first();
                if (! $ud || ! $ud->checkPassword($data['password'])) {
                    return $invalid();
                }
                // Password dbuser benar → butuh hak akses lokal untuk boleh masuk.
                if (! $access) {
                    return back()->withInput($request->only('username'))
                        ->withErrors(['username' => 'Akun ini belum diberi akses ke aplikasi antrian. Hubungi administrator.']);
                }
                $displayName = $access->name ?: ($ud->name ?? $ud->user);
            }

            if ($access->is_blocked) {
                return back()->withInput($request->only('username'))
                    ->withErrors(['username' => 'Akun Anda diblokir. Hubungi administrator.']);
            }

            $request->session()->regenerate();
            $request->session()->put('auth', true);
            $request->session()->put('user', $access->username);
            $request->session()->put('name', $displayName);
            $request->session()->put('role', $access->role);
            $request->session()->put('paramedic_id', $access->paramedic_id);

            $access->forceFill(['last_login_at' => now()])->save();

            // "Remember me": perpanjang umur cookie sesi jadi 30 hari.
            if ($request->boolean('remember')) {
                config(['session.lifetime' => 60 * 24 * 30]);
                $request->session()->put('remember', true);
            }

            // Dokter (role klinik): tempatkan ke RUANG dari jadwal hari ini.
            // Dibungkus rescue supaya DB master lambat/mati TAK memblokir login —
            // dokter tetap masuk, hanya belum menempati ruang.
            if ($access->isDoctor()) {
                $redirect = rescue(fn () => $this->assignRoomOnLogin($request, $access), null, false);
                if ($redirect) {
                    return $redirect;
                }
            }

            return redirect()->intended(route('home'));
        } catch (\Throwable $e) {
            Log::warning('Antrian login gagal: '.$e->getMessage());

            return back()->withInput($request->only('username'))
                ->withErrors(['username' => 'Tidak dapat memverifikasi login — cek koneksi database.']);
        }
    }

    /**
     * Tetapkan ruang dokter saat login (dari jadwal hari ini):
     *  - 0 ruang  → tak menempati apa pun (lanjut normal, return null).
     *  - 1 ruang  → langsung tempati bila kosong; bila bentrok → tolak (popup).
     *  - >1 ruang → arahkan ke halaman pilih ruang.
     *
     * @return \Illuminate\Http\RedirectResponse|null  redirect bila perlu, atau null.
     */
    private function assignRoomOnLogin(Request $request, AntrianAccess $access)
    {
        $occ   = app(RoomOccupancy::class);
        $rooms = $occ->roomsToday((int) $access->paramedic_id);

        if (empty($rooms)) {
            return null; // tak ada jadwal ruang hari ini → lanjut tanpa ruang
        }

        if (count($rooms) > 1) {
            // Simpan kandidat ruang di sesi, pilih di halaman khusus.
            $request->session()->put('room_choices', $rooms);

            return redirect()->route('room.choose');
        }

        // Tepat 1 ruang → tempati bila tak bentrok.
        $room     = $rooms[0];
        $occupant = $occ->occupantOf($room, (int) $access->paramedic_id);

        if ($occupant) {
            // Bentrok: ruang sudah ditempati dokter lain hari ini. Logout lagi
            // (belum benar-benar masuk) & tampilkan pesan di halaman login.
            $request->session()->flush();
            $request->session()->regenerate();

            return redirect()->route('login')->with('room_conflict', [
                'room'   => $room,
                'doctor' => $occupant->paramedic_name ?: $occupant->name ?: 'dokter lain',
            ]);
        }

        $occ->occupy($access, $room);
        $request->session()->put('room_code', $room);

        return null;
    }

    public function logout(Request $request)
    {
        // Lepas ruang yang ditempati dokter ini (bila ada) sebelum sesi dihapus.
        rescue(function () use ($request) {
            $username = $request->session()->get('user');
            if ($username && ($access = AntrianAccess::forUser($username)) && $access->isDoctor()) {
                app(RoomOccupancy::class)->release($access);
            }
        }, null, false);

        // Invalidate penuh: hapus SEMUA data sesi + buat ulang token CSRF.
        // Lebih tuntas daripada regenerate() saja.
        $request->session()->flush();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
