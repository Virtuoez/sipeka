<?php

namespace Tests\Feature;

use App\Models\Layanan;
use App\Models\Pengaturan;
use App\Models\Permohonan;
use App\Models\User;
use App\Services\SuratService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Kasi Kesos & Kasi PM digabung: migration 2026_11_19_000100_gabung_kasi_kesos_pm pada data lama. */
class GabungKasiKesosPmTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function jalankanMigration(): void
    {
        (require database_path('migrations/2026_11_19_000100_gabung_kasi_kesos_pm.php'))->up();
    }

    public function test_data_lama_kasi_pm_dan_kesos_digabung(): void
    {
        Storage::fake('local');
        $disk = Storage::disk('local');

        // Keadaan sebelum digabung
        DB::table('users')->where('name', 'Kasi Kesos & PM')->update(['name' => 'Kasi Kesos']);
        $pm = User::create(['name' => 'Kasi PM', 'email' => 'kasi3@sekumpul.test', 'password' => 'rahasia-panjang', 'role' => 'kasi', 'bidang' => 'pm']);
        DB::table('layanan')->whereIn('slug', ['usaha', 'domisili-usaha', 'penghasilan'])->update(['bidang' => 'pm']);
        Pengaturan::simpan(['nama_kasi_pm' => 'BUDI PM, S.Sos', 'nip_kasi_pm' => '19800101 200501 1 001']);

        $usaha = Layanan::where('slug', 'domisili-usaha')->firstOrFail();
        $folderPm = 'arsip/Kasi PM/18 - Keterangan Domisili Usaha/2026/11 - November/261101-001_WARGA';
        $folderKesos = 'arsip/Kasi Kesos/02 - SKTM/2026/11 - November/261101-002_WARGA';
        $disk->put("$folderPm/SURAT.docx", 'isi-pm');
        $disk->put("$folderKesos/SURAT.xlsx", 'isi-kesos');
        $disk->put('arsip/Kasi Pemerintahan/lain/SURAT.docx', 'tetap');
        $p = Permohonan::withoutGlobalScopes()->create([
            'layanan_id' => $usaha->id, 'kode' => '261101-001', 'nik' => '6303000000000001', 'nama' => 'Warga',
            'status' => 'selesai', 'data' => [], 'tanggal_antrian' => today(), 'nomor_antrian' => 1, 'folder_path' => $folderPm,
        ]);
        DB::table('arsip_file')->insert([
            ['path' => "$folderPm/SURAT.docx", 'ukuran' => 6, 'drive_id' => 'drive-pm', 'created_at' => now(), 'updated_at' => now()],
            ['path' => "$folderKesos/SURAT.xlsx", 'ukuran' => 9, 'drive_id' => 'drive-kesos', 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('drive_folder')->insert([
            ['path' => 'arsip/Kasi PM/18 - Keterangan Domisili Usaha', 'drive_id' => 'f-pm', 'created_at' => now(), 'updated_at' => now()],
            ['path' => 'arsip/Kasi Kesos/02 - SKTM', 'drive_id' => 'f-kesos', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->jalankanMigration();

        // Akun & layanan
        $this->assertSame('kesos', User::where('name', 'Kasi Kesos & PM')->value('bidang'));
        $this->assertSame('kesos', $pm->fresh()->bidang);
        $this->assertSame(0, Layanan::where('bidang', 'pm')->count());

        // Nama Kasi PM dipakai Kasi gabungan (Kasi Kesos belum diisi), juga di surat Domisili Usaha
        $this->assertSame('BUDI PM, S.Sos', Pengaturan::ambil('nama_kasi_kesos'));
        $p->setRelation('layanan', $usaha);
        $this->assertSame('BUDI PM, S.Sos', app(SuratService::class)->nilaiPenanda($p)['nama_kasi_pm']);

        // File arsip pindah ke folder gabungan
        $baruPm = 'arsip/Kasi Kesos & PM/18 - Keterangan Domisili Usaha/2026/11 - November/261101-001_WARGA';
        $baruKesos = 'arsip/Kasi Kesos & PM/02 - SKTM/2026/11 - November/261101-002_WARGA';
        $this->assertSame('isi-pm', $disk->get("$baruPm/SURAT.docx"));
        $this->assertSame('isi-kesos', $disk->get("$baruKesos/SURAT.xlsx"));
        $this->assertFalse($disk->exists('arsip/Kasi PM'));
        $this->assertFalse($disk->exists('arsip/Kasi Kesos'));
        $this->assertSame('tetap', $disk->get('arsip/Kasi Pemerintahan/lain/SURAT.docx'));

        // Path di database ikut, salinan Drive Kasi PM ditandai belum tersalin ke Drive gabungan
        $this->assertSame($baruPm, Permohonan::withoutGlobalScopes()->find($p->id)->folder_path);
        $this->assertNull(DB::table('arsip_file')->where('path', "$baruPm/SURAT.docx")->value('drive_id'));
        $this->assertSame('drive-kesos', DB::table('arsip_file')->where('path', "$baruKesos/SURAT.xlsx")->value('drive_id'));
        $this->assertSame(['arsip/Kasi Kesos & PM/02 - SKTM'], DB::table('drive_folder')->pluck('path')->all());

        // Aman dijalankan dua kali
        $this->jalankanMigration();
        $this->assertSame('isi-pm', $disk->get("$baruPm/SURAT.docx"));
    }

    public function test_menu_profil_dan_akun_memakai_nama_gabungan(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();

        $this->actingAs($admin)->get('/admin/pengaturan')->assertOk()
            ->assertSee('Nama Kasi Kesos & PM')->assertDontSee('Nama Kasi PM');
        $this->actingAs($admin)->get('/admin/layanan')->assertOk()->assertSee('Kasi Kesos & PM')->assertDontSee('Kasi PM<');
        $this->actingAs(User::where('name', 'Kasi Kesos & PM')->firstOrFail())->get('/admin/antrean')->assertOk()
            ->assertSee('khusus surat Kasi Kesos & PM');
    }
}
