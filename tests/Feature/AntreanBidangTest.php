<?php

namespace Tests\Feature;

use App\Models\Layanan;
use App\Models\Permohonan;
use App\Models\User;
use App\Services\ArsipService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Surat otomatis masuk ke antrean Kasi sesuai bidangnya (Pemerintahan / Kesos / PM). */
class AntreanBidangTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function kasi(string $nama): User
    {
        return User::where('name', $nama)->firstOrFail();
    }

    private function permohonan(string $slug, int $nomor): Permohonan
    {
        return Permohonan::create([
            'layanan_id' => Layanan::where('slug', $slug)->value('id'),
            'kode' => '261111-'.str_pad((string) $nomor, 3, '0', STR_PAD_LEFT),
            'nik' => '6303000000000'.str_pad((string) $nomor, 3, '0', STR_PAD_LEFT),
            'nama' => 'Warga '.$slug,
            'status' => 'baru',
            'data' => [],
            'tanggal_antrian' => today(),
            'nomor_antrian' => $nomor,
        ]);
    }

    public function test_akun_kasi_sudah_dinamai_per_bidang(): void
    {
        $this->assertSame('pemerintahan', $this->kasi('Kasi Pemerintahan')->bidang);
        $this->assertSame('kesos', $this->kasi('Kasi Kesos')->bidang);
        $this->assertSame('pm', $this->kasi('Kasi PM')->bidang);
        $this->assertSame(19, Layanan::count());
        $this->assertSame(0, Layanan::whereNull('bidang')->count());
    }

    public function test_antrean_hanya_berisi_surat_bidangnya(): void
    {
        $kematian = $this->permohonan('kematian', 1);
        $sktm = $this->permohonan('sktm', 2);
        $usaha = $this->permohonan('domisili-usaha', 3);

        $this->actingAs($this->kasi('Kasi Kesos'))->get('/admin/antrean')->assertOk()
            ->assertSee('Warga sktm')->assertDontSee('Warga kematian')->assertDontSee('Warga domisili-usaha');

        $this->actingAs($this->kasi('Kasi Pemerintahan'))->get('/admin/antrean')->assertOk()
            ->assertSee('Warga kematian')->assertDontSee('Warga sktm');

        $this->actingAs($this->kasi('Kasi PM'))->get('/admin/antrean')->assertOk()
            ->assertSee('Warga domisili-usaha')->assertDontSee('Warga kematian');

        // Kasi lain tidak bisa mengambil / membuka surat bidang lain
        $this->actingAs($this->kasi('Kasi Kesos'))->post("/admin/permohonan/{$kematian->id}/ambil")->assertForbidden();
        $this->actingAs($this->kasi('Kasi Kesos'))->get("/admin/permohonan/{$kematian->id}")->assertForbidden();
        $this->assertSame('baru', $kematian->fresh()->status);

        // Kasi bidangnya bisa
        $this->actingAs($this->kasi('Kasi Kesos'))->post("/admin/permohonan/{$sktm->id}/ambil")->assertRedirect();
        $this->assertSame('diproses', $sktm->fresh()->status);
    }

    public function test_kasi_tanpa_bidang_menerima_semua_surat(): void
    {
        $this->permohonan('kematian', 1);
        $this->permohonan('sktm', 2);
        $umum = User::create(['name' => 'Kasi Cadangan', 'email' => 'cadangan@sekumpul.local', 'password' => 'rahasia-panjang', 'role' => 'kasi']);

        $this->actingAs($umum)->get('/admin/antrean')->assertOk()->assertSee('Warga kematian')->assertSee('Warga sktm');
    }

    public function test_admin_bisa_mengganti_bidang_kasi_dan_layanan(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();
        $kasi = $this->kasi('Kasi PM');

        $this->actingAs($admin)->put("/admin/akun/{$kasi->id}/bidang", ['bidang' => 'kesos'])->assertRedirect();
        $this->assertSame('kesos', $kasi->fresh()->bidang);

        $this->actingAs($admin)->put("/admin/akun/{$kasi->id}/bidang", ['bidang' => 'ngawur'])->assertSessionHasErrors('bidang');
        $this->actingAs($admin)->get('/admin/akun')->assertOk()->assertSee('Surat Kasi Kesos');
        $this->actingAs($admin)->get('/admin/layanan')->assertOk()->assertSee('Kasi Pemerintahan');
    }

    public function test_arsip_masuk_folder_kasi(): void
    {
        $p = $this->permohonan('pemekaran', 1)->load('layanan');

        $this->assertStringStartsWith('arsip/Kasi Pemerintahan/11 - Keterangan Pemekaran/', ArsipService::folderPermohonan($p));
    }

    public function test_tiket_tombol_buat_surat_baru(): void
    {
        $p = $this->permohonan('sktm', 1);
        $pelayanan = User::where('role', 'pelayanan')->firstOrFail();

        $this->actingAs($pelayanan)->get("/admin/pengajuan/tiket/{$p->id}")->assertOk()
            ->assertSee('Buat Surat Baru')->assertDontSee('Layani Warga Berikutnya');
    }
}
