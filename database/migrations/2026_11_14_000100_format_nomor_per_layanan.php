<?php

use Database\Seeders\LayananSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Nomor surat & template persis seperti surat kantor:
 * - layanan.format_nomor: format nomor per jenis surat (mis. "{no} / KS-Pem / {bulan_romawi} / {tahun}")
 * - layanan.buku_nomor: jenis surat dengan buku yang sama memakai nomor urut bersama
 * - template yang belum diedit admin diganti versi yang lebih mirip surat kantor
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('layanan', function (Blueprint $table) {
            $table->string('format_nomor', 100)->nullable()->after('kode_surat');
            $table->string('buku_nomor', 30)->nullable()->after('format_nomor');
        });

        (new LayananSeeder)->pasang(timpaTemplate: false);
    }

    public function down(): void
    {
        Schema::table('layanan', function (Blueprint $table) {
            $table->dropColumn(['format_nomor', 'buku_nomor']);
        });
    }
};
