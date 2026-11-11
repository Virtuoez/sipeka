<?php

use Database\Seeders\LayananSeeder;
use Illuminate\Database\Migrations\Migration;

/*
 * Surat dicetak dari file Word kantor (resources/surat/{slug}.docx).
 * Menambah isian yang dipakai template Word (lokasi tanah, balik nama, SHM untuk harga tanah)
 * dan memperbarui template HTML yang belum diedit admin.
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
