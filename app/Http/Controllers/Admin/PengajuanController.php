<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Layanan;
use App\Models\Penduduk;
use App\Models\Permohonan;
use App\Services\ArsipService;
use Illuminate\Http\Request;

/**
 * Meja pelayanan (dipakai di tablet): petugas memilih jenis surat yang diminta warga,
 * mengisi formulir bersama warga, mencentang berkas fisik yang dibawa, lalu warga mendapat
 * nomor antrian. Permohonan langsung masuk ke antrean komputer Kasi.
 */
class PengajuanController extends Controller
{
    public function index()
    {
        return view('admin.pengajuan.index', [
            'layanan' => Layanan::where('aktif', true)->orderBy('urutan')->get(),
            'hariIni' => Permohonan::whereDate('tanggal_antrian', today())->count(),
        ]);
    }

    public function form(Layanan $layanan)
    {
        abort_unless($layanan->aktif, 404);

        return view('admin.pengajuan.form', ['layanan' => $layanan]);
    }

    public function simpan(Layanan $layanan, Request $request)
    {
        abort_unless($layanan->aktif, 404);

        $aturan = [
            'nik' => ['required', 'digits:16'],
            'no_kk' => ['nullable', 'digits:16'],
            'nama' => ['required', 'string', 'max:100'],
            'tempat_lahir' => ['required', 'string', 'max:60'],
            'tanggal_lahir' => ['required', 'date', 'before:today'],
            'jenis_kelamin' => ['required', 'in:Laki-laki,Perempuan'],
            'agama' => ['required', 'in:'.implode(',', Penduduk::AGAMA)],
            'status_perkawinan' => ['required', 'in:'.implode(',', Penduduk::STATUS_KAWIN)],
            'pekerjaan' => ['required', 'string', 'max:60'],
            'kewarganegaraan' => ['required', 'in:WNI,WNA'],
            'alamat' => ['required', 'string', 'max:255'],
            'rt' => ['required', 'digits_between:1,3'],
            'rw' => ['required', 'digits_between:1,3'],
            'no_hp' => ['nullable', 'regex:/^(\+62|62|0)8[0-9]{7,12}$/'],
            'berkas' => ['nullable', 'array'],
        ];
        $nama = [];

        foreach ($layanan->isian ?? [] as $isian) {
            $r = [$isian['wajib'] ? 'required' : 'nullable'];
            $r[] = match ($isian['tipe']) {
                'tanggal' => 'date',
                'angka' => 'numeric',
                'pilihan' => \Illuminate\Validation\Rule::in($isian['pilihan'] ?? []),
                default => 'string',
            };
            if (in_array($isian['tipe'], ['teks', 'paragraf'])) {
                $r[] = $isian['tipe'] === 'teks' ? 'max:150' : 'max:1000';
            }
            $aturan['isian.'.$isian['key']] = $r;
            $nama['isian.'.$isian['key']] = $isian['label'];
        }

        // Berkas wajib harus dicentang (artinya sudah dicek petugas & dibawa warga)
        foreach ($layanan->persyaratan ?? [] as $syarat) {
            if ($syarat['wajib']) {
                $aturan['berkas.'.$syarat['key']] = ['accepted'];
                $nama['berkas.'.$syarat['key']] = $syarat['label'];
            }
        }

        $valid = $request->validate($aturan, [
            'no_hp.regex' => 'Nomor HP tidak valid (contoh: 081234567890).',
            'berkas.*.accepted' => 'Berkas ":attribute" belum dibawa/dicentang. Minta warga melengkapinya dulu.',
        ], $nama);

        $identitas = collect($valid)->only([
            'no_kk', 'tempat_lahir', 'tanggal_lahir', 'jenis_kelamin', 'agama', 'status_perkawinan',
            'pekerjaan', 'kewarganegaraan', 'alamat', 'rt', 'rw',
        ])->all();
        $identitas['rt'] = str_pad($identitas['rt'], 3, '0', STR_PAD_LEFT);
        $identitas['rw'] = str_pad($identitas['rw'], 3, '0', STR_PAD_LEFT);

        $dicentang = array_keys(array_filter($request->input('berkas', [])));
        $identitas['berkas_dibawa'] = collect($layanan->persyaratan ?? [])
            ->filter(fn ($s) => in_array($s['key'], $dicentang, true))
            ->pluck('label')->values()->all();

        $permohonan = Permohonan::buatDenganAntrian([
            'layanan_id' => $layanan->id,
            'nik' => $valid['nik'],
            'nama' => trim($valid['nama']),
            'no_hp' => $valid['no_hp'] ?? null,
            'data' => array_merge($identitas, $valid['isian'] ?? []),
            'status' => 'baru',
            'dibuat_oleh' => $request->user()->id,
        ]);
        $permohonan->setRelation('layanan', $layanan);
        $permohonan->update(['folder_path' => ArsipService::folderPermohonan($permohonan)]);

        return redirect()->route('admin.pengajuan.tiket', $permohonan);
    }

    /** Layar nomor antrian untuk ditunjukkan ke warga. */
    public function tiket(Permohonan $permohonan)
    {
        return view('admin.pengajuan.tiket', ['p' => $permohonan->load('layanan')]);
    }

    /** Isi otomatis formulir dari data penduduk berdasarkan NIK (dipanggil lewat JavaScript). */
    public function cariNik(Request $request)
    {
        $request->validate(['nik' => 'required|digits:16']);
        $p = Penduduk::where('nik', $request->nik)->first();
        if (! $p) {
            return response()->json(['ditemukan' => false]);
        }

        return response()->json([
            'ditemukan' => true,
            'status' => $p->status,
            'data' => [
                'nama' => $p->nama,
                'no_kk' => $p->no_kk,
                'tempat_lahir' => $p->tempat_lahir,
                'tanggal_lahir' => $p->tanggal_lahir?->toDateString(),
                'jenis_kelamin' => $p->jenis_kelamin,
                'agama' => $p->agama,
                'status_perkawinan' => $p->status_perkawinan,
                'pekerjaan' => $p->pekerjaan,
                'kewarganegaraan' => $p->kewarganegaraan,
                'alamat' => $p->alamat,
                'rt' => $p->rt,
                'rw' => $p->rw,
                'no_hp' => $p->no_hp,
            ],
        ]);
    }
}
