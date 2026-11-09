<?php

namespace App\Services;

use App\Models\Permohonan;
use App\Models\Scopes\DataContoh;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

/**
 * Mengatur folder arsip supaya berkas tersortir otomatis:
 *
 *   storage/app/private/arsip/{Folder Layanan}/{Tahun}/{MM - Bulan}/{YYMMDD-NNN}_{NAMA}/
 *       SURAT_Surat_Keterangan_Domisili_NAMA.pdf
 *       SURAT_Surat_Keterangan_Domisili_NAMA.docx
 */
class ArsipService
{
    public const ROOT = 'arsip';

    /** Folder terpisah untuk surat dari data contoh (akun tamu). */
    public const ROOT_CONTOH = 'arsip-contoh';

    /** Folder arsip yang boleh dibuka akun yang sedang masuk: tamu hanya arsip-contoh. */
    public static function root(): string
    {
        return DataContoh::aktif() ? self::ROOT_CONTOH : self::ROOT;
    }

    public static function disk()
    {
        return Storage::disk('local');
    }

    public static function namaAman(string $teks, int $maks = 60): string
    {
        $teks = Str::ascii($teks);
        $teks = preg_replace('/[^A-Za-z0-9\-]+/', '_', $teks);

        return Str::limit(trim($teks, '_'), $maks, '');
    }

    public static function folderPermohonan(Permohonan $p): string
    {
        $tanggal = $p->created_at ?? now();
        $bulan = $tanggal->format('m').' - '.ucfirst($tanggal->locale('id')->translatedFormat('F'));

        return implode('/', [
            $p->contoh ? self::ROOT_CONTOH : self::ROOT,
            $p->layanan->folder,
            $tanggal->format('Y'),
            $bulan,
            $p->kode.'_'.Str::upper(self::namaAman($p->nama, 40)),
        ]);
    }

    public function namaFileSurat(Permohonan $p, string $ext): string
    {
        return 'SURAT_'.self::namaAman($p->layanan->nama, 50).'_'.Str::upper(self::namaAman($p->nama, 30)).'.'.$ext;
    }

    public function simpanSurat(Permohonan $p, string $isi, string $ext): string
    {
        $path = $p->folder_path.'/'.$this->namaFileSurat($p, $ext);
        self::disk()->put($path, $isi);

        return $path;
    }

    /**
     * Ubah path relatif arsip jadi path absolut, dan pastikan tidak keluar dari folder arsip
     * milik akun yang sedang masuk (tamu: arsip-contoh, lainnya: arsip).
     */
    public static function pathAbsolut(string $relatif): ?string
    {
        $relatif = trim(str_replace('\\', '/', $relatif), '/');
        if ($relatif === '' || str_contains($relatif, '..') || ! str_starts_with($relatif.'/', self::root().'/')) {
            return null;
        }

        return self::disk()->path($relatif);
    }

    /** Buat ZIP dari satu folder arsip (rekursif). Mengembalikan path file zip sementara. */
    public function zipFolder(string $relatif): ?string
    {
        $abs = self::pathAbsolut($relatif);
        if (! $abs || ! is_dir($abs)) {
            return null;
        }

        $tmp = tempnam(sys_get_temp_dir(), 'arsip').'.zip';
        $zip = new ZipArchive;
        $zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $iter = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($abs, \FilesystemIterator::SKIP_DOTS));
        foreach ($iter as $file) {
            if ($file->isFile()) {
                $dalamZip = ltrim(str_replace('\\', '/', substr($file->getPathname(), strlen($abs))), '/');
                $zip->addFile($file->getPathname(), $dalamZip);
            }
        }
        $ada = $zip->numFiles > 0;
        $zip->close();

        return $ada && file_exists($tmp) ? $tmp : null;
    }
}
