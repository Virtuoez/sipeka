<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ArsipService;
use Illuminate\Http\Request;

/** Penjelajah folder arsip: Layanan > Tahun > Bulan > Permohonan > file. */
class ArsipController extends Controller
{
    public function index(Request $request)
    {
        $path = trim((string) $request->query('path', ArsipService::ROOT), '/');
        $abs = ArsipService::pathAbsolut($path);
        if (! $abs) {
            return redirect()->route('admin.arsip.index');
        }
        $disk = ArsipService::disk();
        if (! $disk->exists($path)) {
            $disk->makeDirectory($path);
        }

        $folder = collect($disk->directories($path))->sort()->map(fn ($d) => [
            'path' => $d,
            'nama' => basename($d),
            'isi' => count($disk->allFiles($d)),
        ])->values();

        $file = collect($disk->files($path))->sort()->map(fn ($f) => [
            'path' => $f,
            'nama' => basename($f),
            'ukuran' => $disk->size($f),
            'waktu' => $disk->lastModified($f),
        ])->values();

        // Remah roti (breadcrumb)
        $remah = [];
        $akum = '';
        foreach (explode('/', $path) as $bagian) {
            $akum = ltrim($akum.'/'.$bagian, '/');
            $remah[] = ['nama' => $akum === ArsipService::ROOT ? 'Arsip' : $bagian, 'path' => $akum];
        }

        return view('admin.arsip.index', compact('path', 'folder', 'file', 'remah'));
    }

    public function file(Request $request)
    {
        $abs = ArsipService::pathAbsolut((string) $request->query('path'));
        abort_unless($abs && is_file($abs), 404);

        return $request->boolean('unduh') ? response()->download($abs) : response()->file($abs);
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
