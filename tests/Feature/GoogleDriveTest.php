<?php

namespace Tests\Feature;

use App\Models\Pengaturan;
use App\Models\User;
use App\Services\ArsipService;
use App\Services\GoogleDrive;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Arsip surat disalin ke Google Drive kantor (API Google dipalsukan dengan Http::fake). */
class GoogleDriveTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private array $driveFile = [];

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        config(['services.google.client_id' => 'uji-id', 'services.google.client_secret' => 'uji-rahasia']);

        $n = 0;
        Http::fake(function (HttpRequest $r) use (&$n) {
            $url = $r->url();
            if (str_contains($url, 'oauth2.googleapis.com/token')) {
                return Http::response(['access_token' => 'akses-uji', 'refresh_token' => 'segar-uji', 'expires_in' => 3600, 'scope' => 'https://www.googleapis.com/auth/drive.file openid email']);
            }
            if (str_contains($url, 'userinfo')) {
                return Http::response(['email' => 'kantor@example.com']);
            }
            if (str_contains($url, 'upload/drive/v3/files')) {
                $id = 'file'.(++$n);
                // Simpan isi file (bagian kedua multipart) untuk diunduh lagi
                preg_match('/\r\n\r\n(.*)\r\n--sipeka[0-9a-f]+--$/s', explode("\r\n\r\n", $r->body(), 2)[1] ?? '', $m);
                $this->driveFile[$id] = $m[1] ?? $r->body();

                return Http::response(['id' => $id]);
            }
            if (preg_match('#drive/v3/files/(\w+)\?alt=media#', $url, $m)) {
                return isset($this->driveFile[$m[1]]) ? Http::response($this->driveFile[$m[1]]) : Http::response('', 404);
            }
            if (str_contains($url, '/permissions')) {
                return Http::response(['id' => 'izin']);
            }
            if (str_contains($url, 'drive/v3/files')) {
                return Http::response(['id' => 'folder'.(++$n)]);
            }

            return Http::response('', 404);
        });
    }

    private function sambungkan(string $bidang = 'pemerintahan'): void
    {
        Pengaturan::simpan([
            "drive_token_$bidang" => Crypt::encryptString('segar-uji'),
            "drive_email_$bidang" => "kasi.$bidang@example.com",
            "drive_root_$bidang" => "akar-$bidang",
        ]);
    }

    public function test_tanpa_drive_arsip_tetap_tersimpan_dan_terdaftar(): void
    {
        $path = 'arsip/Kasi Kesos/09 - SKTM/2026/11 - November/261112-001_WARGA_UJI/SURAT_SKTM_WARGA_UJI.xlsx';
        app(ArsipService::class)->simpan($path, 'isi-xlsx');

        Storage::disk('local')->assertExists($path);
        $this->assertNull(DB::table('arsip_file')->where('path', $path)->value('drive_id'));
        Http::assertNothingSent();

        $admin = User::where('role', 'admin')->firstOrFail();
        $this->actingAs($admin)->get('/admin/arsip?path='.urlencode('arsip/Kasi Kesos'))->assertOk()->assertSee('09 - SKTM');
    }

    public function test_arsip_disalin_ke_drive_dan_tetap_bisa_dibuka_saat_file_server_hilang(): void
    {
        $this->sambungkan();
        $folder = 'arsip/Kasi Pemerintahan/01 - Belum Menikah/2026/11 - November/261112-002_WARGA_UJI';
        $path = $folder.'/SURAT_Belum_Menikah_WARGA_UJI.docx';
        app(ArsipService::class)->simpan($path, 'ISI-SURAT-WORD');

        $id = DB::table('arsip_file')->where('path', $path)->value('drive_id');
        $this->assertNotNull($id);
        // Folder dibuat bertingkat sesuai arsip
        $this->assertTrue(DB::table('drive_folder')->where('path', $folder)->exists());
        $this->assertTrue(DB::table('drive_folder')->where('path', 'arsip/Kasi Pemerintahan/01 - Belum Menikah')->exists());

        // Seperti /tmp Vercel yang terhapus: file server hilang, daftar & isi tetap dari Drive
        Storage::disk('local')->deleteDirectory('arsip');
        $admin = User::where('role', 'admin')->firstOrFail();
        $this->actingAs($admin)->get('/admin/arsip?path='.urlencode($folder))->assertOk()->assertSee('SURAT_Belum_Menikah_WARGA_UJI.docx');
        $r = $this->actingAs($admin)->get('/admin/arsip/file?unduh=1&path='.urlencode($path))->assertOk();
        $this->assertSame('ISI-SURAT-WORD', $r->getContent());

        // ZIP juga mengambil dari Drive
        $this->actingAs($admin)->get('/admin/arsip/zip?path='.urlencode($folder))->assertOk();
    }

    public function test_arsip_data_contoh_dan_bidang_belum_tersambung_tidak_dikirim(): void
    {
        $this->sambungkan('pemerintahan');
        app(ArsipService::class)->simpan('arsip-contoh/Kasi Pemerintahan/x/SURAT.docx', 'contoh');
        // Drive Kasi Pemerintahan tersambung, tapi surat Kasi Kesos tidak boleh masuk ke sana
        app(ArsipService::class)->simpan('arsip/Kasi Kesos/09 - SKTM/x/SURAT.xlsx', 'kesos');
        Http::assertNothingSent();
        $this->assertNull(DB::table('arsip_file')->where('path', 'arsip/Kasi Kesos/09 - SKTM/x/SURAT.xlsx')->value('drive_id'));
    }

    public function test_tiap_kasi_punya_drive_sendiri(): void
    {
        $this->sambungkan('pemerintahan');
        $this->sambungkan('kesos');
        app(ArsipService::class)->simpan('arsip/Kasi Kesos/09 - SKTM/2026/x/SURAT.xlsx', 'kesos');
        app(ArsipService::class)->simpan('arsip/Kasi Pemerintahan/01 - Belum Menikah/2026/y/SURAT.docx', 'pem');

        // Folder bidang dibuat di bawah akar Drive masing-masing Kasi
        Http::assertSent(fn (HttpRequest $r) => str_contains($r->url(), 'drive/v3/files?fields=id') && ($r['name'] ?? '') === '09 - SKTM' && $r['parents'] === ['akar-kesos']);
        Http::assertSent(fn (HttpRequest $r) => str_contains($r->url(), 'drive/v3/files?fields=id') && ($r['name'] ?? '') === '01 - Belum Menikah' && $r['parents'] === ['akar-pemerintahan']);
    }

    public function test_sinkronkan_menyalin_arsip_lama(): void
    {
        Storage::disk('local')->put('arsip/Kasi Kesos/lama/SURAT_LAMA.pdf', 'pdf-lama');
        $this->sambungkan('kesos');

        $kasi = User::where('name', 'Kasi Kesos')->firstOrFail();
        $this->actingAs($kasi)->post('/admin/drive/kesos/sinkron')->assertRedirect()->assertSessionHas('ok');
        $this->assertNotNull(DB::table('arsip_file')->where('path', 'arsip/Kasi Kesos/lama/SURAT_LAMA.pdf')->value('drive_id'));
    }

    public function test_kasi_menyambungkan_drive_sendiri_lewat_google(): void
    {
        $kasi = User::where('name', 'Kasi Kesos')->firstOrFail();
        $this->actingAs($kasi)->get('/admin/drive')->assertOk()->assertSee('Sambungkan Google Drive Kasi Kesos')->assertDontSee('Kasi Pemerintahan');
        // Kasi tidak bisa menyambungkan Drive bidang lain
        $this->actingAs($kasi)->get('/admin/drive/pemerintahan/sambung')->assertForbidden();

        $r = $this->actingAs($kasi)->get('/admin/drive/kesos/sambung')->assertRedirect();
        $this->assertStringContainsString('drive.file', urldecode($r->headers->get('Location')));
        $state = session('drive_state');

        // State salah ditolak
        $this->actingAs($kasi)->withSession(['drive_state' => $state])->get('/admin/drive/kembali?code=x&state=salah')
            ->assertRedirect('/admin/drive')->assertSessionHasErrors('drive');
        $this->assertFalse(GoogleDrive::untuk('kesos')->terhubung());

        $this->actingAs($kasi)->withSession(['drive_state' => $state])->get('/admin/drive/kembali?code=kode-uji&state='.$state['kode'])
            ->assertRedirect('/admin/drive')->assertSessionHas('ok');
        $this->assertTrue(GoogleDrive::untuk('kesos')->terhubung());
        $this->assertFalse(GoogleDrive::untuk('pemerintahan')->terhubung());
        $this->assertSame('kantor@example.com', GoogleDrive::untuk('kesos')->email());
        // Token tersimpan terenkripsi
        $this->assertNotSame('segar-uji', Pengaturan::ambil('drive_token_kesos'));

        // Admin melihat ketiga bidang
        $admin = User::where('role', 'admin')->firstOrFail();
        $this->actingAs($admin)->get('/admin/drive')->assertOk()->assertSee('kantor@example.com')->assertSee('Sambungkan Google Drive Kasi PM');
        // Pelayanan tidak punya menu ini
        $this->actingAs(User::where('role', 'pelayanan')->firstOrFail())->get('/admin/drive')->assertForbidden();

        $this->actingAs($kasi)->delete('/admin/drive/kesos')->assertRedirect();
        $this->assertFalse(GoogleDrive::untuk('kesos')->terhubung());
    }
}
