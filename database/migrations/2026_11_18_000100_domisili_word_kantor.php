<?php

use Database\Seeders\LayananSeeder;
use Illuminate\Database\Migrations\Migration;

/*
 * Surat Keterangan Domisili Penduduk dicetak dari file Word kantor (resources/surat/domisili.docx):
 * isian Alamat KTP & Alamat Domisili, nomor "{no} / DP/ KS - {bulan_romawi} / {tahun}" (buku nomor sendiri),
 * ditandatangani Kasi Pemerintahan. Template yang sudah diedit admin tidak ditimpa.
 */
return new class extends Migration
{
    public function up(): void
    {
        (new LayananSeeder)->pasang(timpaTemplate: false);
    }

    public function down(): void
    {
        //
    }
};
