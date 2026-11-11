<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pengaturan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PengaturanController extends Controller
{
    public function edit()
    {
        return view('admin.pengaturan', ['s' => Pengaturan::semua()]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'nama_kelurahan' => 'required|string|max:60',
            'kecamatan' => 'required|string|max:60',
            'kabupaten' => 'required|string|max:60',
            'provinsi' => 'required|string|max:60',
            'alamat_kantor' => 'required|string|max:255',
            'telepon' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:100',
            'jam_layanan' => 'nullable|string|max:255',
            'nama_lurah' => 'required|string|max:100',
            'nip_lurah' => 'nullable|string|max:30',
            'jabatan_ttd' => 'required|string|max:100',
            'nama_kasi_pemerintahan' => 'nullable|string|max:100',
            'nip_kasi_pemerintahan' => 'nullable|string|max:30',
            'nama_kasi_kesos' => 'nullable|string|max:100',
            'nip_kasi_kesos' => 'nullable|string|max:30',
            'nama_kasi_pm' => 'nullable|string|max:100',
            'nip_kasi_pm' => 'nullable|string|max:30',
            'nama_camat' => 'nullable|string|max:100',
            'nip_camat' => 'nullable|string|max:30',
            'format_nomor' => 'required|string|max:100',
            'kertas' => 'required|in:A4,F4',
            'pengumuman' => 'nullable|string|max:1000',
            'logo' => 'nullable|image|mimes:png,jpg,jpeg|max:1024',
        ]);

        if ($request->hasFile('logo')) {
            $lama = Pengaturan::ambil('logo');
            if ($lama) {
                Storage::disk('public')->delete($lama);
            }
            $data['logo'] = $request->file('logo')->store('logo', 'public');
        } else {
            unset($data['logo']);
        }
        if ($request->boolean('hapus_logo')) {
            Storage::disk('public')->delete((string) Pengaturan::ambil('logo'));
            $data['logo'] = '';
        }

        Pengaturan::simpan($data);

        return back()->with('ok', 'Pengaturan disimpan.');
    }
}
