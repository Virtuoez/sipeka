<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('petugas')->after('password'); // admin | petugas
        });

        // Data penduduk Kelurahan Sekumpul
        Schema::create('penduduk', function (Blueprint $table) {
            $table->id();
            $table->string('nik', 16)->unique();
            $table->string('no_kk', 16)->nullable()->index();
            $table->string('nama');
            $table->string('tempat_lahir')->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->string('jenis_kelamin', 20)->nullable();
            $table->string('agama', 30)->nullable();
            $table->string('status_perkawinan', 30)->nullable();
            $table->string('pekerjaan')->nullable();
            $table->string('pendidikan')->nullable();
            $table->string('golongan_darah', 5)->nullable();
            $table->string('kewarganegaraan', 10)->default('WNI');
            $table->string('hubungan_keluarga')->nullable();
            $table->text('alamat')->nullable();
            $table->string('rt', 3)->nullable()->index();
            $table->string('rw', 3)->nullable();
            $table->string('no_hp', 20)->nullable();
            $table->string('status', 20)->default('aktif'); // aktif | pindah | meninggal
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });

        // Jenis layanan surat + template suratnya
        Schema::create('layanan', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('nama');
            $table->string('kode_surat', 30)->nullable();   // dipakai di nomor surat, mis. 474
            $table->string('folder');                       // nama folder arsip
            $table->string('ikon')->default('file-earmark-text');
            $table->text('deskripsi')->nullable();
            $table->json('persyaratan')->nullable();        // [{key,label,wajib}]
            $table->json('isian')->nullable();              // [{key,label,tipe,wajib}]
            $table->longText('template_html')->nullable();
            $table->boolean('aktif')->default(true);
            $table->unsignedInteger('urutan')->default(0);
            $table->timestamps();
        });

        // Permohonan surat dari warga
        Schema::create('permohonan', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 30)->unique();
            $table->foreignId('layanan_id')->constrained('layanan');
            $table->string('nik', 16)->index();
            $table->string('nama');
            $table->string('no_hp', 20)->nullable();
            $table->json('data');                           // identitas + isian layanan
            $table->string('status', 20)->default('baru'); // baru | diproses | ditolak | selesai
            $table->text('catatan')->nullable();
            $table->string('nomor_surat')->nullable();
            $table->date('tanggal_surat')->nullable();
            $table->longText('surat_html')->nullable();
            $table->string('folder_path')->nullable();
            $table->foreignId('petugas_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('selesai_at')->nullable();
            $table->timestamps();
        });

        // Berkas lampiran (foto KTP, KK, dll)
        Schema::create('berkas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permohonan_id')->constrained('permohonan')->cascadeOnDelete();
            $table->string('jenis');
            $table->string('label');
            $table->string('path');
            $table->string('nama_asli')->nullable();
            $table->string('mime', 100)->nullable();
            $table->unsignedBigInteger('ukuran')->default(0);
            $table->timestamps();
        });

        Schema::create('pengaturan', function (Blueprint $table) {
            $table->string('kunci')->primary();
            $table->text('nilai')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengaturan');
        Schema::dropIfExists('berkas');
        Schema::dropIfExists('permohonan');
        Schema::dropIfExists('layanan');
        Schema::dropIfExists('penduduk');
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('role'));
    }
};
