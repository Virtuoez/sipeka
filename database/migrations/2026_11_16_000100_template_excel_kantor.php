<?php

use Database\Seeders\LayananSeeder;
use Illuminate\Database\Migrations\Migration;

/*
 * Surat Pindah & SKTM dicetak dari file Excel kantor (resources/surat/{slug}.xlsx).
 * Isian Pindah mengikuti Formulir Permohonan Pindah WNI (pilihan alasan, klasifikasi, jenis, status KK,
 * anggota yang pindah) dan format nomornya "{no} / 476.P / PEM-KS / ...".
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
