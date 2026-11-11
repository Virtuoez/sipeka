<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Layanan;
use App\Models\Permohonan;
use App\Services\SuratService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class LayananController extends Controller
{
    public function index()
    {
        return view('admin.layanan.index', [
            'daftar' => Layanan::withCount('permohonan')->orderBy('urutan')->get(),
        ]);
    }

    public function create()
    {
        return view('admin.layanan.form', ['layanan' => new Layanan(['aktif' => true, 'ikon' => 'file-earmark-text'])]);
    }

    public function store(Request $request)
    {
        $data = $this->validasi($request);
        $data['urutan'] = (Layanan::max('urutan') ?? 0) + 1;
        $data['template_html'] = '<p>{{kop_surat}}</p><p style="text-align: center;"><strong><u>'.e(mb_strtoupper($data['nama'])).'</u></strong></p><p style="text-align: center;">Nomor: {{nomor_surat}}</p><p>Tulis isi surat di sini...</p>';
        $layanan = Layanan::create($data);

        return redirect()->route('admin.layanan.template', $layanan)->with('ok', 'Layanan dibuat. Sekarang atur template suratnya.');
    }

    public function edit(Layanan $layanan)
    {
        return view('admin.layanan.form', ['layanan' => $layanan]);
    }

    public function update(Request $request, Layanan $layanan)
    {
        $layanan->update($this->validasi($request, $layanan));

        return redirect()->route('admin.layanan.index')->with('ok', 'Layanan diperbarui.');
    }

    public function destroy(Layanan $layanan)
    {
        if ($layanan->permohonan()->exists()) {
            $layanan->update(['aktif' => false]);

            return back()->with('ok', 'Layanan sudah punya permohonan, jadi hanya dinonaktifkan (tidak tampil di website).');
        }
        $layanan->delete();

        return back()->with('ok', 'Layanan dihapus.');
    }

    public function template(Layanan $layanan)
    {
        return view('admin.layanan.template', [
            'layanan' => $layanan,
            'penandaUmum' => SuratService::PENANDA_UMUM,
        ]);
    }

    public function simpanTemplate(Request $request, Layanan $layanan)
    {
        $request->validate(['template_html' => 'required|string']);
        $layanan->update(['template_html' => $request->template_html]);

        return back()->with('ok', 'Template surat disimpan. Permohonan baru akan memakai template ini.');
    }

    /** Pratinjau template dengan data contoh, dalam format PDF atau Word. */
    public function pratinjau(Request $request, Layanan $layanan, SuratService $surat, string $format)
    {
        abort_unless(in_array($format, ['pdf', 'docx']), 404);

        $contoh = new Permohonan([
            'nik' => '6303000101900001', 'nama' => 'Nama Contoh Warga', 'no_hp' => '081200000000',
            'nomor_surat' => SuratService::nomorSuratBerikutnya($layanan), 'tanggal_surat' => now(),
            'data' => array_merge([
                'no_kk' => '6303000101900000', 'tempat_lahir' => 'Martapura', 'tanggal_lahir' => '1990-01-01',
                'jenis_kelamin' => 'Laki-laki', 'agama' => 'Islam', 'status_perkawinan' => 'Kawin', 'pekerjaan' => 'Wiraswasta',
                'kewarganegaraan' => 'WNI', 'alamat' => 'Jl. Sekumpul Gg. Contoh No. 1', 'rt' => '001', 'rw' => '001',
            ], collect($layanan->isian ?? [])->mapWithKeys(fn ($i) => [$i['key'] => $i['tipe'] === 'tanggal' ? now()->toDateString() : '['.$i['label'].']'])->all()),
        ]);
        $contoh->setRelation('layanan', $layanan);

        $html = $surat->isiTemplate($request->input('template_html', $layanan->template_html ?? ''), $surat->nilaiPenanda($contoh));
        $isi = $format === 'pdf' ? $surat->pdf($html) : $surat->docx($html);

        return response($isi, 200, [
            'Content-Type' => $format === 'pdf' ? 'application/pdf' : 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'Content-Disposition' => ($format === 'pdf' ? 'inline' : 'attachment').'; filename="CONTOH_'.Str::slug($layanan->nama, '_').'.'.$format.'"',
        ]);
    }

    private function validasi(Request $request, ?Layanan $layanan = null): array
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'slug' => ['nullable', 'alpha_dash', 'max:60', Rule::unique('layanan', 'slug')->ignore($layanan?->id)],
            'kode_surat' => ['nullable', 'string', 'max:30'],
            'format_nomor' => ['nullable', 'string', 'max:100'],
            'buku_nomor' => ['nullable', 'string', 'max:30'],
            'bidang' => ['nullable', Rule::in(array_keys(Layanan::BIDANG))],
            'folder' => ['nullable', 'string', 'max:80'],
            'ikon' => ['nullable', 'string', 'max:40'],
            'deskripsi' => ['nullable', 'string', 'max:500'],
            'persyaratan_teks' => ['nullable', 'string', 'max:3000'],
            'isian_teks' => ['nullable', 'string', 'max:3000'],
        ]);

        return [
            'nama' => $data['nama'],
            'slug' => $data['slug'] ?: Str::slug($data['nama']),
            'kode_surat' => $data['kode_surat'],
            'format_nomor' => $data['format_nomor'] ?? null,
            'buku_nomor' => isset($data['buku_nomor']) ? Str::lower(trim($data['buku_nomor'])) : null,
            'bidang' => $data['bidang'] ?? null,
            'folder' => trim(preg_replace('/[\\\\\/:*?"<>|.]+/', ' ', $data['folder'] ?: $data['nama'])),
            'ikon' => $data['ikon'] ?: 'file-earmark-text',
            'deskripsi' => $data['deskripsi'],
            'persyaratan' => Layanan::parsePersyaratan($data['persyaratan_teks'] ?? ''),
            'isian' => Layanan::parseIsian($data['isian_teks'] ?? ''),
            'aktif' => $request->boolean('aktif'),
        ];
    }
}
