<?php

namespace App\Http\Controllers;

use App\Models\Permohonan;
use App\Models\Scopes\DataContoh;
use App\Models\User;
use App\Services\ArsipService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Masuk tanpa email/password untuk petugas: pilih "Pelayanan" atau "Kasi" (lalu pilih PC 1/2/3),
 * plus PIN kalau akun itu diberi PIN (wajib di versi online, lihat config app.wajib_pin).
 * Perangkat mengingat pilihan itu. Admin tetap memakai email + password.
 */
class AuthController extends Controller
{
    public function pilih()
    {
        // Versi online: masuk dengan Google (+ tombol Tamu); pilihan Pelayanan/Kasi tidak ditampilkan
        if (config('app.mode_masuk') === 'google') {
            return view('auth.google', [
                'googleSiap' => filled(config('services.google.client_id')) && filled(config('services.google.client_secret')),
            ]);
        }

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
        // Tombol hanya untuk Pelayanan & Kasi (bukan admin/tamu), dan tidak dipakai di mode Google
        abort_unless(in_array($user->role, ['pelayanan', 'kasi'], true), 403);
        abort_if(config('app.mode_masuk') === 'google', 404);

        if ($user->punyaPin()) {
            // Salah 5x → akun ini dikunci 15 menit (di semua perangkat), supaya PIN tidak bisa ditebak-tebak
            $kunci = 'pin:'.$user->id;
            if (RateLimiter::tooManyAttempts($kunci, 5)) {
                $menit = (int) ceil(RateLimiter::availableIn($kunci) / 60);

                return back()->withErrors(['pin' => "PIN {$user->name} salah 5 kali. Coba lagi dalam {$menit} menit."]);
            }
            if (! preg_match('/^\d{4,6}$/', (string) $request->input('pin'))
                || ! Hash::check($request->input('pin'), $user->pin)) {
                RateLimiter::hit($kunci, 15 * 60);
                $sisa = RateLimiter::retriesLeft($kunci, 5);

                return back()->withErrors(['pin' => "PIN {$user->name} salah.".($sisa ? " Sisa {$sisa} kali percobaan." : '')]);
            }
            RateLimiter::clear($kunci);
        } elseif (config('app.wajib_pin')) {
            return back()->withErrors(['pin' => "{$user->name} belum punya PIN. Minta Admin mengatur PIN di menu Akun Petugas."]);
        }

        Auth::login($user, remember: true);   // perangkat ini diingat: besok langsung masuk
        $request->session()->regenerate();

        return redirect($this->halamanAwal($user));
    }

    /**
     * Masuk sebagai Tamu (demo): bisa mencoba Pelayanan + Kasi, tetapi hanya melihat & membuat DATA CONTOH.
     * Data contoh buatan tamu dari hari-hari sebelumnya dibersihkan setiap kali tamu masuk.
     */
    public function masukTamu(Request $request)
    {
        abort_unless(config('app.tamu'), 404);
        $tamu = User::where('role', 'tamu')->firstOrFail();

        $lama = Permohonan::withoutGlobalScope(DataContoh::class)
            ->where('contoh', true)->whereDate('created_at', '<', today())->get();
        foreach ($lama as $p) {
            if ($p->folder_path && str_starts_with($p->folder_path, ArsipService::ROOT_CONTOH.'/')) {
                app(ArsipService::class)->hapusFolder($p->folder_path);
            }
            $p->delete();
        }

        Auth::login($tamu);   // tanpa "ingat saya": tamu keluar saat browser ditutup
        $request->session()->regenerate();

        return redirect($this->halamanAwal($tamu));
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

    public function halamanAwal(User $user): string
    {
        return match ($user->role) {
            'pelayanan', 'tamu' => route('admin.pengajuan.index'),
            'kasi' => route('admin.antrean'),
            default => route('admin.dashboard'),
        };
    }
}
