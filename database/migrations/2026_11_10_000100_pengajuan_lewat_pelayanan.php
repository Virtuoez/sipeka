<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

// Permohonan sekarang diinput petugas Pelayanan di tablet: catat siapa yang menginput.
// Berkas tidak lagi diunggah; berkas fisik yang dibawa warga dicentang dan disimpan di data['berkas_dibawa'].
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permohonan', function (Blueprint $table) {
            $table->foreignId('dibuat_oleh')->nullable()->after('status')->constrained('users')->nullOnDelete();
        });

        // Nama persyaratan bawaan dulu untuk unggah foto ("Foto KTP"); sekarang berkas asli dibawa ("KTP").
        // Hanya label persyaratan yang diubah; template surat yang sudah diedit admin tidak disentuh.
        foreach (DB::table('layanan')->get(['id', 'persyaratan']) as $l) {
            $daftar = collect(json_decode($l->persyaratan ?? '[]', true))->map(function ($s) {
                $s['label'] = preg_replace('/^Foto (KTP|Kartu Keluarga|Buku Nikah|Akta Kelahiran)/', '$1', $s['label']);
                $s['key'] = Str::slug($s['label'], '_');

                return $s;
            });
            DB::table('layanan')->where('id', $l->id)->update(['persyaratan' => $daftar->values()->toJson()]);
        }
    }

    public function down(): void
    {
        Schema::table('permohonan', function (Blueprint $table) {
            $table->dropConstrainedForeignId('dibuat_oleh');
        });
    }
};
