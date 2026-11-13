<?php

use App\Services\ArsipService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/*
 * Kasi Kesos & Kasi PM digabung menjadi "Kasi Kesos & PM" (permintaan atasan).
 * - Bidang "pm" dilebur ke "kesos" (layanan & akun). Buku nomor surat PM tetap terpisah.
 * - Akun "Kasi Kesos" diganti nama; akun "Kasi PM" tetap ada (bidangnya ikut gabungan), admin boleh menghapusnya.
 * - Nama/NIP Kasi PM dipakai untuk Kasi gabungan bila Kasi Kesos belum diisi.
 * - Folder arsip "Kasi Kesos" & "Kasi PM" dipindah ke "Kasi Kesos & PM" (file + path di database).
 *   Salinan Google Drive Kasi PM tidak bisa dibaca akun Drive Kasi Kesos, jadi ditandai belum tersalin
 *   (Sinkronkan di menu Google Drive menyalinnya lagi dari file lokal).
 */
return new class extends Migration
{
    private const BARU = 'Kasi Kesos & PM';

    private const LAMA = ['Kasi Kesos' => 'kesos', 'Kasi PM' => 'pm'];

    public function up(): void
    {
        DB::table('layanan')->where('bidang', 'pm')->update(['bidang' => 'kesos']);
        DB::table('users')->where('bidang', 'pm')->update(['bidang' => 'kesos']);
        DB::table('users')->where('role', 'kasi')->where('name', 'Kasi Kesos')->update(['name' => self::BARU]);

        $atur = DB::table('pengaturan')->pluck('nilai', 'kunci');
        foreach (['nama', 'nip'] as $k) {
            if (blank($atur["{$k}_kasi_kesos"] ?? null) && filled($atur["{$k}_kasi_pm"] ?? null)) {
                DB::table('pengaturan')->updateOrInsert(['kunci' => "{$k}_kasi_kesos"], ['nilai' => $atur["{$k}_kasi_pm"]]);
            }
        }

        foreach ([ArsipService::ROOT, ArsipService::ROOT_CONTOH] as $root) {
            foreach (self::LAMA as $label => $bidang) {
                $this->pindah("$root/$label/", $root.'/'.self::BARU.'/', $bidang === 'pm');
            }
        }
    }

    /** Pindahkan file arsip + perbarui path di database dari awalan $dari ke $ke. */
    private function pindah(string $dari, string $ke, bool $driveLain): void
    {
        $disk = ArsipService::disk();
        $folderLama = rtrim($dari, '/');
        if ($disk->exists($folderLama)) {
            foreach ($disk->allFiles($folderLama) as $f) {
                $tujuan = $ke.substr($f, strlen($dari));
                if (! $disk->exists($tujuan)) {
                    $disk->move($f, $tujuan);
                }
            }
            if (! $disk->allFiles($folderLama)) {
                $disk->deleteDirectory($folderLama);
            }
        }

        $ganti = fn (string $path) => $ke.substr($path, strlen($dari));
        $cocok = fn (string $tabel, string $kolom) => DB::table($tabel)->where($kolom, 'like', $dari.'%')->get()
            ->filter(fn ($r) => str_starts_with((string) $r->{$kolom}, $dari));

        foreach ($cocok('permohonan', 'folder_path') as $r) {
            DB::table('permohonan')->where('id', $r->id)->update(['folder_path' => $ganti($r->folder_path)]);
        }
        foreach ($cocok('arsip_file', 'path') as $r) {
            $baru = $ganti($r->path);
            if (DB::table('arsip_file')->where('path', $baru)->exists()) {
                DB::table('arsip_file')->where('id', $r->id)->delete();

                continue;
            }
            DB::table('arsip_file')->where('id', $r->id)->update(['path' => $baru] + ($driveLain ? ['drive_id' => null] : []));
        }
        foreach ($cocok('drive_folder', 'path') as $r) {
            $baru = $ganti($r->path);
            // Folder di Drive Kasi PM bukan milik Drive gabungan: buang, nanti dibuat ulang saat sinkron
            if ($driveLain || DB::table('drive_folder')->where('path', $baru)->exists()) {
                DB::table('drive_folder')->where('id', $r->id)->delete();
            } else {
                DB::table('drive_folder')->where('id', $r->id)->update(['path' => $baru]);
            }
        }
    }

    public function down(): void
    {
        //
    }
};
