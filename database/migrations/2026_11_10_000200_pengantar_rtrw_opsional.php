<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Surat Pengantar RT/RW tidak lagi wajib di semua layanan (keputusan kelurahan).
return new class extends Migration
{
    public function up(): void
    {
        $this->ubah(false);

        // Kalimat bawaan surat yang menyebut pengantar RT/RW diganti netral, karena berkas itu kini tidak selalu ada.
        // Hanya kalimat bawaan persis yang diganti; template yang sudah ditulis ulang admin tidak tersentuh.
        $netral = 'Berdasarkan data kependudukan dan keterangan yang bersangkutan, orang tersebut';
        foreach ([
            'Berdasarkan surat pengantar RT/RW setempat, orang tersebut',
            'Berdasarkan data kependudukan dan surat pengantar RT/RW, orang tersebut',
        ] as $lama) {
            DB::table('layanan')->where('template_html', 'like', '%'.$lama.'%')
                ->update(['template_html' => DB::raw('replace(template_html, '.DB::getPdo()->quote($lama).', '.DB::getPdo()->quote($netral).')')]);
        }
    }

    public function down(): void
    {
        $this->ubah(true);
    }

    private function ubah(bool $wajib): void
    {
        foreach (DB::table('layanan')->get(['id', 'persyaratan']) as $l) {
            $daftar = collect(json_decode($l->persyaratan ?? '[]', true))->map(function ($s) use ($wajib) {
                if (preg_match('/pengantar\s+rt/i', $s['label'])) {
                    $s['wajib'] = $wajib;
                }

                return $s;
            });
            DB::table('layanan')->where('id', $l->id)->update(['persyaratan' => $daftar->values()->toJson()]);
        }
    }
};
