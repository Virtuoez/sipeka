<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

/**
 * Masuk dengan akun Google (versi online). Peran ditentukan Admin: email Google petugas didaftarkan
 * di Akun Petugas (Pelayanan / Kasi PC 1/2/3 / Admin). Email yang tidak terdaftar ditolak.
 */
class GoogleController extends Controller
{
    private function siap(): bool
    {
        return filled(config('services.google.client_id')) && filled(config('services.google.client_secret'));
    }

    public function arahkan()
    {
        if (! $this->siap()) {
            return redirect()->route('login')->withErrors(['google' => 'Login Google belum diatur (GOOGLE_CLIENT_ID / GOOGLE_CLIENT_SECRET kosong).']);
        }

        return Socialite::driver('google')->redirect();
    }

    public function kembali(Request $request)
    {
        if (! $this->siap()) {
            return redirect()->route('login');
        }

        try {
            $google = Socialite::driver('google')->user();
        } catch (Throwable $e) {
            report($e);

            return redirect()->route('login')->withErrors(['google' => 'Login Google dibatalkan atau gagal. Silakan coba lagi.']);
        }

        $email = Str::lower((string) $google->getEmail());
        if ($email === '' || ($google->user['email_verified'] ?? true) === false) {
            return redirect()->route('login')->withErrors(['google' => 'Email akun Google ini belum terverifikasi.']);
        }

        $user = User::whereRaw('lower(email) = ?', [$email])
            ->whereIn('role', ['admin', 'pelayanan', 'kasi'])
            ->first();
        if (! $user) {
            return redirect()->route('login')->withErrors([
                'google' => "Email {$email} belum terdaftar sebagai petugas. Minta Admin mendaftarkannya di menu Akun Petugas.",
            ]);
        }

        Auth::login($user, remember: true);   // perangkat ini diingat
        $request->session()->regenerate();

        return redirect()->intended(app(AuthController::class)->halamanAwal($user));
    }
}
