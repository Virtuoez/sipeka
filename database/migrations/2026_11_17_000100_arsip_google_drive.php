<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Daftar file arsip + salinannya di Google Drive kantor.
 * Di Vercel folder storage hanya sementara (/tmp), jadi daftar arsip dibaca dari tabel ini
 * dan isi file diambil dari Google Drive bila salinan lokal sudah hilang.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('arsip_file', function (Blueprint $t) {
            $t->id();
            $t->string('path', 500)->unique();
            $t->unsignedInteger('ukuran')->default(0);
            $t->string('drive_id')->nullable();
            $t->timestamps();
        });

        Schema::create('drive_folder', function (Blueprint $t) {
            $t->id();
            $t->string('path', 500)->unique();
            $t->string('drive_id');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drive_folder');
        Schema::dropIfExists('arsip_file');
    }
};
