<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ArsipService;
use Illuminate\Http\Request;

/** Penjelajah folder arsip: Layanan > Tahun > Bulan > Permohonan > file. */
class ArsipController extends Controller
{
    public function index(Request $request, ArsipService $arsip)
    {
        // Tamu hanya folder arsip-contoh; petugas lain folder arsip
        $path = trim((string) $request->query('path', ArsipService::root()), '/');
        $abs = ArsipService::pathAbsolut($path);
        if (! $abs) {
            return redirect()->route('admin.arsip.index');
        }
        $disk = ArsipService::disk();
        if (! $disk->exists($path)) {
            $disk->makeDirectory($path);
        }

        // Gabungan folder storage + daftar arsip di database (file yang tersimpan di Google Drive ikut tampil)
        [$folder, $file] = $arsip->daftar($path);

        // Remah roti (breadcrumb)
        $remah = [];
        $akum = '';
        foreach (explode('/', $path) as $bagian) {
            $akum = ltrim($akum.'/'.$bagian, '/');
            $remah[] = ['nama' => $akum === ArsipService::root() ? 'Arsip' : $bagian, 'path' => $akum];
        }

        return view('admin.arsip.index', compact('path', 'folder', 'file', 'remah'));
    }

    public function file(Request $request, ArsipService $arsip)
    {
        $path = trim(str_replace('\\', '/', (string) $request->query('path')), '/');
        abort_unless(ArsipService::pathAbsolut($path), 404);
        $isi = $arsip->isi($path);
        abort_if($isi === null, 404, 'File tidak ditemukan. Kalau file ini disimpan di Google Drive, pastikan Drive masih tersambung.');

        $nama = basename($path);
        $mime = \App\Services\SuratService::MIME[strtolower(pathinfo($nama, PATHINFO_EXTENSION))] ?? 'application/octet-stream';
        $disposisi = $request->boolean('unduh') ? 'attachment' : 'inline';

        return response($isi, 200, [
            'Content-Type' => $mime,
            'Content-Disposition' => $disposisi.'; filename="'.addslashes($nama).'"',
        ]);
    }

    public function zip(Request $request, ArsipService $arsip)
    {
        $path = trim((string) $request->query('path'), '/');
        $tmp = $arsip->zipFolder($path);
        if (! $tmp) {
            return back()->withErrors(['arsip' => 'Folder kosong, tidak ada yang bisa diunduh.']);
        }

        return response()->download($tmp, str_replace('/', ' - ', $path).'.zip')->deleteFileAfterSend();
    }
}
