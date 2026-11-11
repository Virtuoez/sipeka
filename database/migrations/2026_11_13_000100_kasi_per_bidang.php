<?php

use Database\Seeders\LayananSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * Pembagian surat per bidang Kasi, mengikuti folder kantor:
 *   kasi pemerintahan / kasi kesos / kasi pm
 * - layanan.bidang: Kasi mana yang mengerjakan jenis surat itu (null = semua Kasi)
 * - users.bidang: bidang akun Kasi (null = menerima semua jenis surat)
 * - Akun "Kasi PC 1/2/3" diganti nama menjadi Kasi Pemerintahan / Kasi Kesos / Kasi PM
 * - Jenis surat baru dari folder kantor ditambahkan (template yang sudah diedit admin tidak ditimpa)
 */
return new class extends Migration
{
    private const AKUN = [
        'Kasi PC 1' => ['Kasi Pemerintahan', 'pemerintahan'],
        'Kasi PC 2' => ['Kasi Kesos', 'kesos'],
        'Kasi PC 3' => ['Kasi PM', 'pm'],
    ];

    public function up(): void
    {
        Schema::table('layanan', function (Blueprint $table) {
            $table->string('bidang', 20)->nullable()->after('folder');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->string('bidang', 20)->nullable()->after('role');
        });

        foreach (self::AKUN as $lama => [$baru, $bidang]) {
            // Lewati kalau nama baru sudah dipakai akun lain (kolom name unik di form Akun Petugas)
            if (DB::table('users')->where('name', $baru)->exists()) {
                continue;
            }
            DB::table('users')->where('role', 'kasi')->where('name', $lama)
                ->update(['name' => $baru, 'bidang' => $bidang, 'updated_at' => now()]);
        }

        (new LayananSeeder)->pasang(timpaTemplate: false);
    }

    public function down(): void
    {
        foreach (self::AKUN as $lama => [$baru]) {
            DB::table('users')->where('role', 'kasi')->where('name', $baru)->update(['name' => $lama]);
        }
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('bidang'));
        Schema::table('layanan', fn (Blueprint $table) => $table->dropColumn('bidang'));
    }
};
