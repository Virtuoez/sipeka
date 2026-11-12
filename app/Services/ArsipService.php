<?php

namespace App\Services;

use App\Models\Permohonan;
use App\Models\Scopes\DataContoh;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

/**
 * Mengatur folder arsip supaya berkas tersortir otomatis:
 *
 *   storage/app/private/arsip/{Kasi Bidang}/{Folder Layanan}/{Tahun}/{MM - Bulan}/{YYMMDD-NNN}_{NAMA}/
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

        // Dikelompokkan per Kasi seperti folder kantor: arsip/Kasi Pemerintahan/04 - Keterangan Kematian/...
        $bidang = \App\Models\Layanan::BIDANG[$p->layanan->bidang] ?? null;

        return implode('/', array_filter([
            $p->contoh ? self::ROOT_CONTOH : self::ROOT,
            $bidang,
            $p->layanan->folder,
            $tanggal->format('Y'),
            $bulan,
            $p->kode.'_'.Str::upper(self::namaAman($p->nama, 40)),
        ]));
    }

    public function namaFileSurat(Permohonan $p, string $ext): string
    {
        return 'SURAT_'.self::namaAman($p->layanan->nama, 50).'_'.Str::upper(self::namaAman($p->nama, 30)).'.'.$ext;
    }

    public function simpanSurat(Permohonan $p, string $isi, string $ext): string
    {
        $path = $p->folder_path.'/'.$this->namaFileSurat($p, $ext);
        $this->simpan($path, $isi);

        return $path;
    }

    /**
     * Simpan file arsip: ke folder storage (cepat, untuk PC kantor) + daftar arsip_file (database)
     * + salinan di Google Drive kantor bila tersambung. Gagal unggah tidak menggagalkan surat;
     * file ditandai belum tersalin dan bisa diulang lewat tombol Sinkronkan.
     */
    public function simpan(string $path, string $isi): void
    {
        self::disk()->put($path, $isi);
        $lama = DB::table('arsip_file')->where('path', $path)->first();
        $data = ['ukuran' => strlen($isi), 'updated_at' => now()];
        $lama
            ? DB::table('arsip_file')->where('id', $lama->id)->update($data)
            : DB::table('arsip_file')->insert($data + ['path' => $path, 'created_at' => now()]);

        $this->keDrive($path, $isi, $lama->drive_id ?? null);
    }

    /** Ke Drive Kasi bidang pemilik arsip (arsip contoh/tamu dan bidang yang belum tersambung dilewati). */
    private function keDrive(string $path, string $isi, ?string $idLama): bool
    {
        $drive = GoogleDrive::untukPath($path);
        if (! $drive) {
            return false;
        }
        try {
            $id = $drive->unggah($path, $isi, $idLama);
            DB::table('arsip_file')->where('path', $path)->update(['drive_id' => $id]);

            return true;
        } catch (\Throwable $e) {
            report($e);

            return false;
        }
    }

    /** Isi file arsip: dari storage, atau dari Google Drive bila salinan lokal sudah hilang (Vercel). */
    public function isi(string $path): ?string
    {
        $disk = self::disk();
        if ($disk->exists($path)) {
            return $disk->get($path);
        }
        $id = DB::table('arsip_file')->where('path', $path)->value('drive_id');
        $drive = GoogleDrive::untukPath($path);
        if (! $id || ! $drive) {
            return null;
        }
        try {
            $isi = $drive->unduh($id);
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
        if ($isi !== null) {
            $disk->put($path, $isi);   // simpan sementara supaya pembukaan berikutnya cepat
        }

        return $isi;
    }

    public function ada(string $path): bool
    {
        return self::disk()->exists($path) || DB::table('arsip_file')->where('path', $path)->exists();
    }

    /**
     * Isi satu folder arsip: gabungan folder storage dan daftar arsip_file.
     *
     * @return array{0: Collection, 1: Collection} [folder, file]
     */
    public function daftar(string $path): array
    {
        $disk = self::disk();
        $awalan = $path.'/';
        $semua = $this->semuaFile($path);

        $folder = [];
        $file = [];
        foreach ($semua as $f => $info) {
            $sisa = substr($f, strlen($awalan));
            if (str_contains($sisa, '/')) {
                $nama = strstr($sisa, '/', true);
                $folder[$nama] = ($folder[$nama] ?? 0) + 1;
            } else {
                $file[] = ['path' => $f, 'nama' => $sisa] + $info;
            }
        }
        foreach ($disk->directories($path) as $d) {
            $folder[basename($d)] ??= 0;
        }
        ksort($folder);

        return [
            collect($folder)->map(fn ($isi, $nama) => ['path' => $awalan.$nama, 'nama' => $nama, 'isi' => $isi])->values(),
            collect($file)->sortBy('nama')->values(),
        ];
    }

    /** Baris tabel di bawah folder. LIKE hanya penyaring kasar ("_" = wildcard), dipastikan lagi dengan str_starts_with. */
    private static function barisDiBawah(string $tabel, string $path): Collection
    {
        $awalan = trim($path, '/').'/';

        return DB::table($tabel)->where('path', 'like', $awalan.'%')->get()
            ->filter(fn ($r) => str_starts_with($r->path, $awalan))->values();
    }

    /**
     * Semua file (rekursif) di bawah folder, gabungan storage + daftar arsip_file.
     *
     * @return Collection<string, array{ukuran: int, waktu: int, drive: bool}>
     */
    private function semuaFile(string $path): Collection
    {
        $disk = self::disk();
        $semua = collect($disk->exists($path) ? $disk->allFiles($path) : [])->mapWithKeys(fn ($f) => [$f => [
            'ukuran' => $disk->size($f), 'waktu' => $disk->lastModified($f), 'drive' => false,
        ]]);
        foreach (self::barisDiBawah('arsip_file', $path) as $r) {
            $lokal = $semua->get($r->path);
            $semua->put($r->path, [
                'ukuran' => $lokal['ukuran'] ?? (int) $r->ukuran,
                'waktu' => $lokal['waktu'] ?? strtotime($r->updated_at),
                'drive' => (bool) $r->drive_id,
            ]);
        }

        return $semua;
    }

    /** Hapus folder arsip permohonan: storage, daftar, dan salinan Drive (ke Sampah Drive). */
    public function hapusFolder(string $path): void
    {
        $path = trim($path, '/');
        self::disk()->deleteDirectory($path);
        DB::table('arsip_file')->whereIn('id', self::barisDiBawah('arsip_file', $path)->pluck('id'))->delete();

        $folder = DB::table('drive_folder')->where('path', $path)->value('drive_id');
        $drive = GoogleDrive::untukPath($path);
        if ($folder && $drive) {
            try {
                $drive->buang($folder);
            } catch (\Throwable $e) {
                report($e);
            }
        }
        DB::table('drive_folder')->where('path', $path)->orWhereIn('id', self::barisDiBawah('drive_folder', $path)->pluck('id'))->delete();
    }

    /**
     * Salin ke Drive Kasi semua arsip bidangnya yang belum tersalin (mis. surat lama sebelum Drive disambungkan).
     *
     * @return array{0: int, 1: int, 2: int, 3: int} [berhasil, gagal, hilang, sisa]
     */
    public function sinkronkan(GoogleDrive $drive, int $batas = 40): array
    {
        $disk = self::disk();
        $akar = $drive->pathAkar();
        // File di storage yang belum terdaftar → daftarkan dulu
        if ($disk->exists($akar)) {
            $terdaftar = DB::table('arsip_file')->pluck('path')->flip();
            foreach ($disk->allFiles($akar) as $f) {
                if (! $terdaftar->has($f)) {
                    DB::table('arsip_file')->insert(['path' => $f, 'ukuran' => $disk->size($f), 'created_at' => now(), 'updated_at' => now()]);
                }
            }
        }

        $ok = $gagal = $hilang = 0;
        $belum = fn () => self::barisDiBawah('arsip_file', $akar)->whereNull('drive_id');
        foreach ($belum()->sortBy('id')->take($batas) as $r) {
            if (! $disk->exists($r->path)) {
                // File sudah hilang (mis. folder sementara Vercel terhapus sebelum Drive disambungkan): keluarkan dari daftar
                DB::table('arsip_file')->where('id', $r->id)->delete();
                $hilang++;

                continue;
            }
            if (! $this->keDrive($r->path, $disk->get($r->path), null)) {
                $gagal++;
                break;   // Drive bermasalah: hentikan dulu, coba lagi nanti
            }
            $ok++;
        }

        return [$ok, $gagal, $hilang, $belum()->count()];
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
        $relatif = trim(str_replace('\\', '/', $relatif), '/');
        if (! self::pathAbsolut($relatif)) {
            return null;
        }

        $tmp = tempnam(sys_get_temp_dir(), 'arsip').'.zip';
        $zip = new ZipArchive;
        $zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        // File yang hanya ada di Google Drive ikut diambil
        foreach ($this->semuaFile($relatif)->keys() as $f) {
            $isi = $this->isi($f);
            if ($isi !== null) {
                $zip->addFromString(substr($f, strlen($relatif) + 1), $isi);
            }
        }
        $ada = $zip->numFiles > 0;
        $zip->close();

        return $ada && file_exists($tmp) ? $tmp : null;
    }
}
