<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AkunController extends Controller
{
    /** Daftar akun petugas (khusus admin). */
    public function index()
    {
        // Akun tamu (demo) tidak ditampilkan: tidak untuk diubah/dihapus
        return view('admin.akun.index', ['daftar' => User::where('role', '!=', 'tamu')->orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100|unique:users,name',
            'role' => 'required|in:'.implode(',', array_keys(User::PERAN)),
            // Admin: email + password. Pelayanan & Kasi: email Google (untuk versi online) opsional.
            'email' => 'required_if:role,admin|nullable|email|unique:users,email',
            'password' => ['required_if:role,admin', 'nullable', Password::min(8)],
            'pin' => [config('app.wajib_pin') && config('app.mode_masuk') === 'tombol' ? 'required_unless:role,admin' : 'nullable', 'nullable', 'regex:/^\d{4,6}$/'],
        ], ['pin.regex' => 'PIN harus 4–6 angka.'], ['name' => 'nama']);

        if ($data['role'] === 'admin') {
            unset($data['pin']);
        } else {
            $data['email'] = filled($data['email'] ?? null)
                ? Str::lower($data['email'])
                : Str::slug($data['name']).'-'.Str::lower(Str::random(4)).'@sekumpul.local';
            $data['password'] = Str::random(40); // tidak dipakai: masuk lewat tombol/PIN atau Google
        }
        User::create($data);

        return back()->with('ok', 'Akun '.$data['name'].' dibuat.');
    }

    /** Email Google petugas: dipakai untuk "Masuk dengan Google" di versi online. */
    public function aturEmail(Request $request, User $user)
    {
        abort_if($user->isTamu(), 422);
        $data = $request->validate([
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
        ], [], ['email' => 'email Google']);

        $user->update(['email' => Str::lower($data['email'])]);

        return back()->with('ok', "{$user->name} sekarang masuk dengan Google memakai {$user->email}.");
    }

    /** Atur / ganti / hapus PIN akun Pelayanan atau Kasi. */
    public function aturPin(Request $request, User $user)
    {
        abort_unless($user->punyaPeran('pelayanan', 'kasi'), 422, 'PIN hanya untuk akun Pelayanan & Kasi.');

        if ($request->boolean('hapus')) {
            $user->update(['pin' => null]);
            RateLimiter::clear('pin:'.$user->id);

            return back()->with('ok', "PIN {$user->name} dihapus."
                .(config('app.wajib_pin') ? ' Akun ini tidak bisa masuk sampai diberi PIN lagi.' : ''));
        }

        $request->validate(['pin' => ['required', 'regex:/^\d{4,6}$/']], ['pin.regex' => 'PIN harus 4–6 angka.']);
        $user->update(['pin' => $request->pin]);
        RateLimiter::clear('pin:'.$user->id);   // buka kunci kalau sebelumnya salah 5x

        return back()->with('ok', "PIN {$user->name} disimpan. Beritahu petugasnya secara langsung, jangan lewat grup.");
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->is($request->user())) {
            return back()->withErrors(['akun' => 'Tidak bisa menghapus akun sendiri.']);
        }
        // Permohonan yang masih dipegang akun ini dikembalikan ke antrean
        $user->permohonanDikerjakan()->where('status', 'diproses')->get()->each->lepaskan();
        $user->delete();

        return back()->with('ok', 'Akun dihapus.');
    }

    /** Ganti password akun sendiri. */
    public function saya()
    {
        return view('admin.akun.saya');
    }

    public function gantiPassword(Request $request)
    {
        $request->validate([
            'password_lama' => 'required|current_password',
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [], ['password_lama' => 'password lama', 'password' => 'password baru']);

        $request->user()->update(['password' => $request->password]);

        return back()->with('ok', 'Password berhasil diganti.');
    }
}
