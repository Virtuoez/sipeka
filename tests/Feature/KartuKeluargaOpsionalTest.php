<?php

namespace Tests\Feature;

use App\Models\Layanan;
use App\Models\Permohonan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Berkas Kartu Keluarga opsional di semua layanan: banyak warga tidak membawa KK fisik. */
class KartuKeluargaOpsionalTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function kirim(array $berkas)
    {
        return $this->actingAs(User::where('role', 'pelayanan')->firstOrFail())->post('/admin/pengajuan/sktm', [
            'nik' => '6303000101900009', 'nama' => 'Warga Tanpa KK', 'tempat_lahir' => 'Martapura', 'tanggal_lahir' => '1990-01-02',
            'jenis_kelamin' => 'Laki-laki', 'agama' => 'Islam', 'status_perkawinan' => 'Kawin', 'pekerjaan' => 'Buruh',
            'kewarganegaraan' => 'WNI', 'alamat' => 'Jl. Contoh', 'rt' => '1', 'rw' => '2',
            'isian' => ['keperluan' => 'Berobat'], 'berkas' => $berkas,
        ]);
    }

    public function test_kartu_keluarga_tidak_wajib_di_semua_layanan(): void
    {
        foreach (Layanan::all() as $l) {
            foreach ($l->persyaratan ?? [] as $s) {
                if ($s['label'] === 'Kartu Keluarga') {
                    $this->assertFalse($s['wajib'], "{$l->slug}: Kartu Keluarga masih wajib");
                }
                if ($s['label'] === 'KTP') {
                    $this->assertTrue($s['wajib'], "{$l->slug}: KTP harus tetap wajib");
                }
            }
        }
    }

    public function test_permohonan_tanpa_kk_tetap_bisa_dibuat(): void
    {
        $this->kirim([])->assertSessionHasErrors('berkas.ktp');
        $this->assertSame(0, Permohonan::count());

        $this->kirim(['ktp' => '1'])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame(1, Permohonan::count());
    }

    public function test_migration_mengubah_data_lama(): void
    {
        $l = Layanan::where('slug', 'skbb')->firstOrFail();
        $l->update(['persyaratan' => [
            ['key' => 'ktp', 'label' => 'KTP', 'wajib' => true],
            ['key' => 'kartu_keluarga', 'label' => 'Kartu Keluarga', 'wajib' => true],
            ['key' => 'kartu_keluarga_lama', 'label' => 'Kartu Keluarga Lama', 'wajib' => true],
        ]]);

        (require database_path('migrations/2026_11_20_000100_kartu_keluarga_opsional.php'))->up();

        $wajib = collect(json_decode(DB::table('layanan')->where('id', $l->id)->value('persyaratan'), true))->pluck('wajib', 'key')->all();
        $this->assertSame(['ktp' => true, 'kartu_keluarga' => false, 'kartu_keluarga_lama' => true], $wajib);
    }
}
