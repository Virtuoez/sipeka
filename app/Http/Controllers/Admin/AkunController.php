<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
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
        ], [], ['name' => 'nama']);

        if ($data['role'] !== 'admin') {
            $data['email'] = Str::slug($data['name']).'-'.Str::lower(Str::random(4)).'@sekumpul.local';
            $data['password'] = Str::random(40); // tidak dipakai, akun masuk lewat tombol
        }
        User::create($data);

        return back()->with('ok', 'Akun '.$data['name'].' dibuat.');
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
