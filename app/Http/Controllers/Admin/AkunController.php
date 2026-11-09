<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AkunController extends Controller
{
    /** Daftar akun petugas (khusus admin). */
    public function index()
    {
        return view('admin.akun.index', ['daftar' => User::orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100|unique:users,name',
            'role' => 'required|in:'.implode(',', array_keys(User::PERAN)),
            // Pelayanan & Kasi masuk lewat tombol, tanpa email/password. Hanya Admin yang butuh.
            'email' => 'required_if:role,admin|nullable|email|unique:users,email',
            'password' => ['required_if:role,admin', 'nullable', Password::min(8)],
            'pin' => [config('app.wajib_pin') ? 'required_unless:role,admin' : 'nullable', 'nullable', 'regex:/^\d{4,6}$/'],
        ], ['pin.regex' => 'PIN harus 4–6 angka.'], ['name' => 'nama']);

        if ($data['role'] === 'admin') {
            unset($data['pin']);
        }

        if ($data['role'] !== 'admin') {
            $data['email'] = Str::slug($data['name']).'-'.Str::lower(Str::random(4)).'@sekumpul.local';
            $data['password'] = Str::random(40); // tidak dipakai, akun masuk lewat tombol
        }
        User::create($data);

        return back()->with('ok', 'Akun '.$data['name'].' dibuat.');
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
