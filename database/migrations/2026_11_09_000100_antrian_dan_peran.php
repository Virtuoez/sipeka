<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permohonan', function (Blueprint $table) {
            $table->date('tanggal_antrian')->nullable()->after('kode');
            $table->unsignedInteger('nomor_antrian')->nullable()->after('tanggal_antrian');
            $table->timestamp('diambil_at')->nullable()->after('petugas_id');
        });

        // Isi nomor antrian untuk permohonan lama (urut per tanggal masuk)
        $per = [];
        foreach (DB::table('permohonan')->orderBy('created_at')->orderBy('id')->get(['id', 'created_at']) as $p) {
            $tgl = substr($p->created_at, 0, 10);
            $per[$tgl] = ($per[$tgl] ?? 0) + 1;
            DB::table('permohonan')->where('id', $p->id)->update([
                'tanggal_antrian' => $tgl.' 00:00:00', // format sama dengan yang ditulis Eloquent

                'nomor_antrian' => $per[$tgl],
                'kode' => date('ymd', strtotime($tgl)).'-'.str_pad($per[$tgl], 3, '0', STR_PAD_LEFT),
            ]);
        }

        Schema::table('permohonan', function (Blueprint $table) {
            $table->unique(['tanggal_antrian', 'nomor_antrian']);
        });

        // Peran lama "petugas" menjadi "pelayanan"
        DB::table('users')->where('role', 'petugas')->update(['role' => 'pelayanan']);
    }

    public function down(): void
    {
        Schema::table('permohonan', function (Blueprint $table) {
            $table->dropUnique(['tanggal_antrian', 'nomor_antrian']);
            $table->dropColumn(['tanggal_antrian', 'nomor_antrian', 'diambil_at']);
        });
        DB::table('users')->whereIn('role', ['pelayanan', 'kasi'])->update(['role' => 'petugas']);
    }
};
