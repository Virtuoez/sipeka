<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Berkas Kartu Keluarga tidak lagi wajib dicentang di semua layanan: banyak warga tidak membawa KK fisik (keputusan pemilik).
// Hanya persyaratan berlabel persis "Kartu Keluarga"; "Kartu Keluarga Lama" dll. tidak tersentuh.
return new class extends Migration
{
    public function up(): void
    {
        $this->ubah(false);
    }

    public function down(): void
    {
        $this->ubah(true);
    }

    private function ubah(bool $wajib): void
    {
        foreach (DB::table('layanan')->get(['id', 'persyaratan']) as $l) {
            $daftar = collect(json_decode($l->persyaratan ?? '[]', true))->map(function ($s) use ($wajib) {
                if (strcasecmp(trim($s['label'] ?? ''), 'Kartu Keluarga') === 0) {
                    $s['wajib'] = $wajib;
                }

                return $s;
            });
            DB::table('layanan')->where('id', $l->id)->update(['persyaratan' => $daftar->values()->toJson()]);
        }
    }
};
