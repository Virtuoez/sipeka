<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Masuk tanpa password untuk petugas (website hanya di jaringan kantor):
 * pilih "Pelayanan" atau "Kasi" (lalu pilih PC 1/2/3). Perangkat mengingat pilihan itu.
 * Admin tetap memakai email + password karena bisa menghapus data & mengubah pengaturan.
 */
class AuthController extends Controller
{
    public function pilih()
    {
        $petugas = User::whereIn('role', ['pelayanan', 'kasi'])->orderBy('name')->get();

        // Akun yang sedang terbuka di perangkat lain (aktif 15 menit terakhir), supaya dua PC tidak memilih Kasi yang sama
        $dipakai = DB::table('sessions')
            ->whereNotNull('user_id')
            ->where('last_activity', '>=', now()->subMinutes(15)->getTimestamp())
            ->pluck('user_id')->unique()->all();

        return view('auth.pilih', [
            'pelayanan' => $petugas->where('role', 'pelayanan')->values(),
            'kasi' => $petugas->where('role', 'kasi')->values(),
            'dipakai' => $dipakai,
        ]);
    }

    public function masuk(User $user, Request $request)
    {
        // Admin tidak boleh masuk lewat tombol
        abort_unless($user->punyaPeran('pelayanan', 'kasi'), 403);

        Auth::login($user, remember: true);   // perangkat ini diingat: besok langsung masuk
        $request->session()->regenerate();

        return redirect($this->halamanAwal($user));
    }

    public function formAdmin()
    {
        return view('auth.login');
    }

    public function loginAdmin(Request $request)
    {
        $kredensial = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $kunci = 'login:'.Str::lower($request->email).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($kunci, 5)) {
            return back()->withErrors(['email' => 'Terlalu banyak percobaan. Coba lagi dalam '.RateLimiter::availableIn($kunci).' detik.']);
        }

        if (! Auth::attempt($kredensial)) {
            RateLimiter::hit($kunci, 60);

            return back()->withInput($request->only('email'))->withErrors(['email' => 'Email atau password salah.']);
        }

        RateLimiter::clear($kunci);
        $request->session()->regenerate();

        return redirect()->intended($this->halamanAwal($request->user()));
    }

    /** "Ganti peran": keluar lalu kembali ke layar pilihan. */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function halamanAwal(User $user): string
    {
        return match ($user->role) {
            'pelayanan' => route('admin.pengajuan.index'),
            'kasi' => route('admin.antrean'),
            default => route('admin.dashboard'),
        };
    }
}
