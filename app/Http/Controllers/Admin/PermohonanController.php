<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Berkas;
use App\Models\Layanan;
use App\Models\Penduduk;
use App\Models\Permohonan;
use App\Services\ArsipService;
use App\Services\SuratService;
use Illuminate\Http\Request;

class PermohonanController extends Controller
{
    public function __construct(private SuratService $surat, private ArsipService $arsip) {}

    /** Daftar semua permohonan, untuk admin & pelayanan memantau. */
    public function index(Request $request)
    {
        $daftar = Permohonan::with('layanan', 'petugas')
            ->when($request->status, fn ($q, $v) => $q->where('status', $v))
            ->when($request->layanan, fn ($q, $v) => $q->whereHas('layanan', fn ($l) => $l->where('slug', $v)))
            ->when($request->tanggal, fn ($q, $v) => $q->whereDate('tanggal_antrian', $v))
            ->when($request->q, fn ($q, $v) => ctype_digit($v) && strlen($v) <= 4
                // Angka pendek (mis. "15") = nomor antrian
                ? $q->where('nomor_antrian', (int) $v)
                : $q->where(fn ($w) => $w->whereLike('kode', "%$v%")->orWhereLike('nik', "%$v%")->orWhereLike('nama', "%$v%")))
            ->orderByDesc('tanggal_antrian')->orderByDesc('nomor_antrian')
            ->paginate(15)->withQueryString();

        return view('admin.permohonan.index', [
            'daftar' => $daftar,
            'layanan' => Layanan::orderBy('urutan')->get(),
        ]);
    }

    /**
     * Halaman Pelayanan: ketik nomor antrian, langsung terlihat statusnya.
     * Di bawahnya daftar antrian pada tanggal itu (bawaan: hari ini).
     */
    public function cekAntrian(Request $request)
    {
        $request->validate(['tanggal' => 'nullable|date', 'nomor' => 'nullable|integer|min:1']);
        $tanggal = $request->date('tanggal') ?? today();

        $daftar = Permohonan::with('layanan', 'petugas')
            ->whereDate('tanggal_antrian', $tanggal)
            ->orderBy('nomor_antrian')->get();

        return view('admin.permohonan.cek-antrian', [
            'tanggal' => $tanggal,
            'daftar' => $daftar,
            'hasil' => $request->filled('nomor') ? $daftar->firstWhere('nomor_antrian', (int) $request->nomor) : null,
            'jumlah' => $daftar->countBy('status'),
        ]);
    }

    /**
     * Halaman Kasi: antrean bersama (belum diambil) + yang sedang dikerjakan akun ini.
     * Begitu diambil satu komputer, permohonan hilang dari antrean komputer lain.
     */
    public function antrean(Request $request)
    {
        return view('admin.permohonan.antrean', [
            'milikSaya' => Permohonan::with('layanan')
                ->where('status', 'diproses')->where('petugas_id', $request->user()->id)
                ->orderBy('diambil_at')->get(),
            // Kasi hanya melihat jenis surat bidangnya (Pemerintahan / Kesos / PM)
            'antrean' => Permohonan::with('layanan')->bagianKasi($request->user())
                ->where('status', 'baru')->whereNull('petugas_id')
                ->orderBy('tanggal_antrian')->orderBy('nomor_antrian')->get(),
        ]);
    }

    public function ambil(Permohonan $permohonan, Request $request)
    {
        abort_unless($permohonan->layanan?->untukKasi($request->user()), 403,
            'Surat ini bagian '.$permohonan->layanan?->labelBidang().', bukan bagian akun ini.');

        if (! $permohonan->ambilOleh($request->user())) {
            $permohonan->refresh()->load('petugas');
            $oleh = $permohonan->petugas ? ' oleh '.$permohonan->petugas->name : '';

            return redirect()->route('admin.antrean')->withErrors(['ambil' => "Antrian No. {$permohonan->nomorLabel()} sudah diambil{$oleh}."]);
        }

        return redirect()->route('admin.permohonan.show', $permohonan)->with('ok', "Antrian No. {$permohonan->nomorLabel()} sekarang Anda kerjakan. Komputer lain tidak bisa membukanya.");
    }

    public function lepas(Permohonan $permohonan, Request $request)
    {
        $user = $request->user();
        abort_unless($permohonan->status === 'diproses'
            && ($permohonan->petugas_id === $user->id || $user->punyaPeran('admin', 'pelayanan')), 403);

        $permohonan->lepaskan();

        return $user->isKasi()
            ? redirect()->route('admin.antrean')->with('ok', "Antrian No. {$permohonan->nomorLabel()} dikembalikan ke antrean.")
            : back()->with('ok', 'Permohonan dikembalikan ke antrean, sekarang bisa diambil komputer lain.');
    }

    public function show(Permohonan $permohonan, Request $request)
    {
        $this->bolehLihat($permohonan, $request);
        $permohonan->load('layanan', 'berkas', 'petugas', 'penginput');

        return view('admin.permohonan.show', [
            'p' => $permohonan,
            'penduduk' => $permohonan->penduduk(),
        ]);
    }

    public function berkas(Berkas $berkas, Request $request)
    {
        // Permohonan di luar data akun ini (mis. tamu membuka lampiran data asli) tersaring scope → 404
        abort_unless($berkas->permohonan, 404);
        $this->bolehLihat($berkas->permohonan, $request);
        $abs = ArsipService::pathAbsolut($berkas->path);
        abort_unless($abs && is_file($abs), 404);

        return response()->file($abs, ['Content-Type' => $berkas->mime ?: 'application/octet-stream']);
    }

    public function tolak(Permohonan $permohonan, Request $request)
    {
        abort_unless($permohonan->bolehDitolak($request->user()), 403);
        $request->validate(['catatan' => 'required|string|max:1000'], [], ['catatan' => 'alasan penolakan']);
        $permohonan->update([
            'status' => 'ditolak',
            'catatan' => $request->catatan,
            'petugas_id' => $request->user()->id,
        ]);

        $pesan = 'Permohonan ditolak. Pemohon dapat melihat alasannya di menu Cek Status.';

        return $request->user()->isKasi()
            ? redirect()->route('admin.antrean')->with('ok', $pesan)
            : redirect()->route('admin.permohonan.show', $permohonan)->with('ok', $pesan);
    }

    /** Halaman editor surat: template sudah terisi data pemohon, tinggal cek/ubah lalu simpan. */
    public function surat(Permohonan $permohonan, Request $request)
    {
        $this->bolehKerjakan($permohonan, $request);
        $permohonan->load('layanan');

        if (! $permohonan->surat_html) {
            $permohonan->nomor_surat ??= SuratService::nomorSuratBerikutnya($permohonan->layanan);
            $permohonan->tanggal_surat ??= now();
            $permohonan->surat_html = $this->surat->renderUntuk($permohonan);
            $permohonan->save();
        }

        $word = SuratService::templateWord($permohonan->layanan);
        if ($word) {
            // Surat dicetak dari file Word/Excel kantor: tampilkan data yang akan masuk ke surat
            $ext = SuratService::ekstensiTemplate($permohonan->layanan);
            $nilai = $ext === 'xlsx' ? $this->surat->nilaiExcel($permohonan) : $this->surat->nilaiWord($permohonan);
            $penanda = $ext === 'xlsx'
                ? SuratService::penandaExcel($word)
                : array_values(array_unique((new \PhpOffice\PhpWord\TemplateProcessor($word))->getVariables()));

            return view('admin.permohonan.surat-word', ['p' => $permohonan, 'penanda' => $penanda, 'nilai' => $nilai, 'ext' => $ext]);
        }

        return view('admin.permohonan.surat', ['p' => $permohonan]);
    }

    public function simpanSurat(Permohonan $permohonan, Request $request)
    {
        $this->bolehKerjakan($permohonan, $request);
        $permohonan->load('layanan');
        $word = SuratService::templateWord($permohonan->layanan);
        $valid = $request->validate([
            'nomor_surat' => 'required|string|max:100',
            'tanggal_surat' => 'required|date',
            'surat_html' => $word ? 'nullable|string' : 'required|string',
            'surat_word' => 'nullable|file|mimes:'.($word ? SuratService::ekstensiTemplate($permohonan->layanan) : 'docx').'|max:10240',
            'aksi' => 'required|in:simpan,selesai',
        ]);

        $permohonan->fill([
            'nomor_surat' => $valid['nomor_surat'],
            'tanggal_surat' => $valid['tanggal_surat'],
        ]);
        if (! $word) {
            $permohonan->surat_html = $valid['surat_html'];
        }

        if ($valid['aksi'] === 'selesai') {
            $permohonan->status = 'selesai';
            $permohonan->selesai_at = now();
            $permohonan->catatan = null;
            $this->arsipkanSurat($permohonan, $request->file('surat_word'));
        }
        $permohonan->save();

        if ($valid['aksi'] === 'simpan') {
            return back()->with('ok', 'Draf surat disimpan.');
        }

        $jenis = ['docx' => 'Word', 'xlsx' => 'Excel'][SuratService::ekstensiTemplate($permohonan->layanan) ?? ''] ?? 'PDF + Word';
        $pesan = "Antrian No. {$permohonan->nomorLabel()} selesai. Surat ($jenis) sudah masuk folder arsip.";

        // Kasi kembali ke antrean: permohonan ini sudah hilang dari daftarnya.
        return $request->user()->isKasi()
            ? redirect()->route('admin.antrean')->with('ok', $pesan)
            : redirect()->route('admin.permohonan.show', $permohonan)->with('ok', $pesan);
    }

    public function resetSurat(Permohonan $permohonan, Request $request)
    {
        $this->bolehKerjakan($permohonan, $request);
        $permohonan->load('layanan');
        $permohonan->update(['surat_html' => $this->surat->renderUntuk($permohonan)]);

        return back()->with('ok', 'Surat diisi ulang dari template terbaru.');
    }

    public function unduh(Permohonan $permohonan, string $format, Request $request)
    {
        abort_unless(in_array($format, ['pdf', 'docx', 'xlsx']), 404);
        $this->bolehKerjakan($permohonan, $request);
        $permohonan->load('layanan');
        abort_unless($permohonan->surat_html, 404, 'Surat belum dibuat.');

        $word = SuratService::templateWord($permohonan->layanan);
        if ($word) {
            abort_unless($format === SuratService::ekstensiTemplate($permohonan->layanan), 404, 'Surat ini dicetak dari file kantor. Unduh lewat tombol Unduh Surat.');
            // Surat selesai: pakai file yang sudah diarsipkan (bisa jadi file yang sudah diedit Kasi)
            $arsip = $permohonan->folder_path.'/'.$this->arsip->namaFileSurat($permohonan, $format);
            $isi = $permohonan->status === 'selesai' && $permohonan->folder_path ? $this->arsip->isi($arsip) : null;
            $isi ??= $this->surat->isiTemplateKantor($permohonan, $word);
        } elseif ($format === 'xlsx') {
            abort(404);
        } else {
            $html = $this->surat->final($permohonan);
            $isi = $format === 'pdf' ? $this->surat->pdf($html) : $this->surat->docx($html);
            if ($permohonan->status === 'selesai') {
                $this->arsip->simpanSurat($permohonan, $isi, $format);
            }
        }

        $mime = SuratService::MIME[$format];
        $disposisi = $format === 'pdf' ? 'inline' : 'attachment';

        return response($isi, 200, [
            'Content-Type' => $mime,
            'Content-Disposition' => $disposisi.'; filename="'.$this->arsip->namaFileSurat($permohonan, $format).'"',
        ]);
    }

    public function zip(Permohonan $permohonan, Request $request)
    {
        $this->bolehLihat($permohonan, $request);
        $tmp = $this->arsip->zipFolder($permohonan->folder_path);
        abort_unless($tmp, 404, 'Folder arsip masih kosong.');

        return response()->download($tmp, basename($permohonan->folder_path).'.zip')->deleteFileAfterSend();
    }

    /** Simpan/perbarui data pemohon ke tabel data penduduk. */
    public function keDataPenduduk(Permohonan $permohonan, Request $request)
    {
        $this->bolehLihat($permohonan, $request);
        $d = $permohonan->data ?? [];
        $penduduk = Penduduk::updateOrCreate(['nik' => $permohonan->nik], array_filter([
            'nama' => $permohonan->nama,
            'no_hp' => $permohonan->no_hp,
            'no_kk' => $d['no_kk'] ?? null,
            'tempat_lahir' => $d['tempat_lahir'] ?? null,
            'tanggal_lahir' => $d['tanggal_lahir'] ?? null,
            'jenis_kelamin' => $d['jenis_kelamin'] ?? null,
            'agama' => $d['agama'] ?? null,
            'status_perkawinan' => $d['status_perkawinan'] ?? null,
            'pekerjaan' => $d['pekerjaan'] ?? null,
            'kewarganegaraan' => $d['kewarganegaraan'] ?? null,
            'alamat' => $d['alamat'] ?? null,
            'rt' => $d['rt'] ?? null,
            'rw' => $d['rw'] ?? null,
        ], fn ($v) => $v !== null && $v !== ''));

        return back()->with('ok', ($penduduk->wasRecentlyCreated ? 'Ditambahkan ke' : 'Diperbarui di').' data penduduk: '.$penduduk->nama);
    }

    public function destroy(Permohonan $permohonan)
    {
        if ($permohonan->folder_path && ArsipService::pathAbsolut($permohonan->folder_path)) {
            $this->arsip->hapusFolder($permohonan->folder_path);
        }
        $permohonan->delete();

        return redirect()->route('admin.permohonan.index')->with('ok', 'Permohonan dan folder arsipnya dihapus.');
    }

    private function bolehLihat(Permohonan $p, Request $request): void
    {
        abort_unless($p->bolehDilihat($request->user()), 403, 'Permohonan ini sedang dikerjakan komputer lain atau sudah diarsipkan.');
    }

    private function bolehKerjakan(Permohonan $p, Request $request): void
    {
        abort_unless($p->bolehDikerjakan($request->user()), 403, 'Ambil permohonan ini dari antrean dulu sebelum membuat surat.');
    }

    private function arsipkanSurat(Permohonan $p, ?\Illuminate\Http\UploadedFile $wordDiedit = null): void
    {
        $word = SuratService::templateWord($p->layanan);
        if ($word) {
            // Word/Excel kantor; kalau Kasi mengunggah file yang sudah diedit, file itulah yang diarsipkan
            $isi = $wordDiedit ? $wordDiedit->get() : $this->surat->isiTemplateKantor($p, $word);
            $this->arsip->simpanSurat($p, $isi, SuratService::ekstensiTemplate($p->layanan));

            return;
        }

        $html = $this->surat->final($p);
        $this->arsip->simpanSurat($p, $this->surat->pdf($html), 'pdf');
        $this->arsip->simpanSurat($p, $this->surat->docx($html), 'docx');
    }
}
