<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Penduduk;
use App\Models\Permohonan;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PendudukController extends Controller
{
    public function index(Request $request)
    {
        $query = Penduduk::query()
            ->when($request->q, fn ($q, $v) => $q->where(fn ($w) => $w
                // whereLike tanpa beda huruf besar/kecil, sama di SQLite (lokal) maupun Postgres (Vercel)
                ->whereLike('nama', "%$v%")->orWhereLike('nik', "%$v%")->orWhereLike('no_kk', "%$v%")))
            ->when($request->rt, fn ($q, $v) => $q->where('rt', str_pad($v, 3, '0', STR_PAD_LEFT)))
            ->when($request->jk, fn ($q, $v) => $q->where('jenis_kelamin', $v))
            ->when($request->status, fn ($q, $v) => $q->where('status', $v), fn ($q) => $q->where('status', 'aktif'));

        if ($request->export === 'csv') {
            abort_unless($request->user()->punyaPeran('admin', 'pelayanan') && ! $request->user()->isTamu(), 403);

            return $this->exportCsv($query);
        }

        return view('admin.penduduk.index', [
            'daftar' => $query->orderBy('rt')->orderBy('no_kk')->orderBy('nama')->paginate(20)->withQueryString(),
            'daftarRt' => Penduduk::select('rt')->distinct()->orderBy('rt')->pluck('rt')->filter(),
        ]);
    }

    public function create()
    {
        return view('admin.penduduk.form', ['penduduk' => new Penduduk(['kewarganegaraan' => 'WNI', 'status' => 'aktif'])]);
    }

    public function store(Request $request)
    {
        $penduduk = Penduduk::create($this->validasi($request));

        return redirect()->route('admin.penduduk.index', ['q' => $penduduk->nik])->with('ok', 'Data penduduk ditambahkan.');
    }

    public function show(Penduduk $penduduk)
    {
        return view('admin.penduduk.show', [
            'penduduk' => $penduduk,
            'keluarga' => $penduduk->no_kk ? Penduduk::where('no_kk', $penduduk->no_kk)->orderBy('tanggal_lahir')->get() : collect(),
            'riwayat' => Permohonan::with('layanan')->where('nik', $penduduk->nik)->latest()->get(),
        ]);
    }

    public function edit(Penduduk $penduduk)
    {
        return view('admin.penduduk.form', ['penduduk' => $penduduk]);
    }

    public function update(Request $request, Penduduk $penduduk)
    {
        $penduduk->update($this->validasi($request, $penduduk));

        return redirect()->route('admin.penduduk.show', $penduduk)->with('ok', 'Data penduduk diperbarui.');
    }

    public function destroy(Penduduk $penduduk)
    {
        $penduduk->delete();

        return redirect()->route('admin.penduduk.index')->with('ok', 'Data penduduk dihapus.');
    }

    /** Import dari CSV (bisa dibuat dari Excel: Save As > CSV). Baris pertama = judul kolom. */
    public function import(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt|max:10240']);

        $fh = fopen($request->file('file')->getRealPath(), 'r');
        $baris1 = fgets($fh);
        $pemisah = substr_count($baris1, ';') > substr_count($baris1, ',') ? ';' : ',';
        $judul = array_map(fn ($h) => strtolower(trim(preg_replace('/^\xEF\xBB\xBF/', '', $h))), str_getcsv($baris1, $pemisah));

        if (! in_array('nik', $judul) || ! in_array('nama', $judul)) {
            return back()->withErrors(['file' => 'File harus punya kolom "nik" dan "nama" di baris pertama.']);
        }

        $baru = $ubah = $lewat = 0;
        while (($row = fgetcsv($fh, 0, $pemisah)) !== false) {
            $data = array_intersect_key(array_combine($judul, array_pad($row, count($judul), null)), array_flip(Penduduk::KOLOM));
            $data = array_map(fn ($v) => $v === null ? null : trim($v), $data);
            $data['nik'] = preg_replace('/\D/', '', (string) ($data['nik'] ?? ''));

            if (strlen($data['nik']) !== 16 || empty($data['nama'])) {
                $lewat++;

                continue;
            }
            if (! empty($data['tanggal_lahir'])) {
                $data['tanggal_lahir'] = $this->tanggal($data['tanggal_lahir']);
            }
            foreach (['rt', 'rw'] as $k) {
                if (! empty($data[$k])) {
                    $data[$k] = str_pad(preg_replace('/\D/', '', $data[$k]), 3, '0', STR_PAD_LEFT);
                }
            }

            $p = Penduduk::updateOrCreate(['nik' => $data['nik']], array_filter($data, fn ($v) => $v !== null && $v !== ''));
            $p->wasRecentlyCreated ? $baru++ : $ubah++;
        }
        fclose($fh);

        return back()->with('ok', "Import selesai: $baru data baru, $ubah diperbarui, $lewat baris dilewati (NIK/nama tidak valid).");
    }

    public function templateCsv()
    {
        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, Penduduk::KOLOM, ';');
            fputcsv($out, ['6303000000000099', '6303000000000098', 'Contoh Nama', 'Martapura', '1990-12-31', 'Laki-laki', 'Islam', 'Kawin', 'Wiraswasta', 'SMA/Sederajat', 'O', 'WNI', 'Kepala Keluarga', 'Jl. Sekumpul No. 1', '001', '001', '081200000000', 'aktif', ''], ';');
            fclose($out);
        }, 'template-import-penduduk.csv', ['Content-Type' => 'text/csv']);
    }

    private function exportCsv($query)
    {
        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM supaya Excel membaca huruf dengan benar
            fputcsv($out, Penduduk::KOLOM, ';');
            $query->orderBy('rt')->orderBy('no_kk')->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $r) {
                    $baris = [];
                    foreach (Penduduk::KOLOM as $k) {
                        $v = $k === 'tanggal_lahir' ? $r->tanggal_lahir?->format('Y-m-d') : $r->$k;
                        // Tanda petik di depan NIK/KK supaya Excel tidak mengubahnya jadi 6.3E+15
                        $baris[] = in_array($k, ['nik', 'no_kk']) && $v ? "'".$v : $v;
                    }
                    fputcsv($out, $baris, ';');
                }
            });
            fclose($out);
        }, 'data-penduduk-sekumpul-'.now()->format('Ymd').'.csv', ['Content-Type' => 'text/csv']);
    }

    private function tanggal(string $v): ?string
    {
        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'd.m.Y'] as $f) {
            $t = \DateTime::createFromFormat($f, $v);
            if ($t && $t->format($f) === $v) {
                return $t->format('Y-m-d');
            }
        }

        return null;
    }

    private function validasi(Request $request, ?Penduduk $penduduk = null): array
    {
        // No KK opsional: "-" / spasi dianggap kosong
        $request->merge(['no_kk' => preg_replace('/\D+/', '', (string) $request->input('no_kk')) ?: null]);

        $data = $request->validate([
            'nik' => ['required', 'digits:16', Rule::unique('penduduk', 'nik')->ignore($penduduk?->id)],
            'no_kk' => ['nullable', 'digits:16'],
            'nama' => ['required', 'string', 'max:100'],
            'tempat_lahir' => ['nullable', 'string', 'max:60'],
            'tanggal_lahir' => ['nullable', 'date'],
            'jenis_kelamin' => ['nullable', 'in:Laki-laki,Perempuan'],
            'agama' => ['nullable', 'string', 'max:30'],
            'status_perkawinan' => ['nullable', 'string', 'max:30'],
            'pekerjaan' => ['nullable', 'string', 'max:100'],
            'pendidikan' => ['nullable', 'string', 'max:100'],
            'golongan_darah' => ['nullable', 'string', 'max:5'],
            'kewarganegaraan' => ['required', 'in:WNI,WNA'],
            'hubungan_keluarga' => ['nullable', 'string', 'max:50'],
            'alamat' => ['nullable', 'string', 'max:255'],
            'rt' => ['nullable', 'digits_between:1,3'],
            'rw' => ['nullable', 'digits_between:1,3'],
            'no_hp' => ['nullable', 'string', 'max:20'],
            'status' => ['required', 'in:aktif,pindah,meninggal'],
            'keterangan' => ['nullable', 'string', 'max:500'],
        ]);
        foreach (['rt', 'rw'] as $k) {
            if (! empty($data[$k])) {
                $data[$k] = str_pad($data[$k], 3, '0', STR_PAD_LEFT);
            }
        }

        return $data;
    }
}
