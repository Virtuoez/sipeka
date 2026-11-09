<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/*
 * Akun tamu (demo) + pemisahan DATA CONTOH.
 * Permohonan & penduduk bertanda contoh=true hanya terlihat oleh tamu; petugas asli tidak melihatnya
 * (lihat App\Models\Scopes\DataContoh). Nomor antrian dihitung terpisah untuk data contoh.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permohonan', function (Blueprint $table) {
            $table->boolean('contoh')->default(false)->after('status');
        });
        Schema::table('penduduk', function (Blueprint $table) {
            $table->boolean('contoh')->default(false)->after('status');
        });

        Schema::table('permohonan', function (Blueprint $table) {
            $table->dropUnique(['tanggal_antrian', 'nomor_antrian']);
            $table->unique(['tanggal_antrian', 'nomor_antrian', 'contoh']);
        });

        // Penduduk fiktif dari ContohPendudukSeeder menjadi data contoh
        DB::table('penduduk')->where('keterangan', 'DATA CONTOH')->update(['contoh' => true]);

        if (! DB::table('users')->where('role', 'tamu')->exists()) {
            DB::table('users')->insert([
                'name' => 'Tamu (Demo)',
                'email' => 'tamu@sekumpul.local',
                'password' => Hash::make(Str::random(40)),   // tidak dipakai: tamu masuk lewat tombol
                'role' => 'tamu',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('permohonan')->where('contoh', true)->delete();
        Schema::table('permohonan', function (Blueprint $table) {
            $table->dropUnique(['tanggal_antrian', 'nomor_antrian', 'contoh']);
            $table->unique(['tanggal_antrian', 'nomor_antrian']);
        });
        Schema::table('permohonan', fn (Blueprint $table) => $table->dropColumn('contoh'));
        Schema::table('penduduk', fn (Blueprint $table) => $table->dropColumn('contoh'));
        DB::table('users')->where('role', 'tamu')->delete();
    }
};
