<?php

namespace Tests\Feature;

use App\Models\Pengaturan;
use App\Models\User;
use App\Services\ArsipService;
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

    private function sambungkan(): void
    {
        Pengaturan::simpan(['drive_token' => Crypt::encryptString('segar-uji'), 'drive_email' => 'kantor@example.com', 'drive_root' => 'akar']);
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
        $this->assertTrue(DB::table('drive_folder')->where('path', 'arsip/Kasi Pemerintahan')->exists());

        // Seperti /tmp Vercel yang terhapus: file server hilang, daftar & isi tetap dari Drive
        Storage::disk('local')->deleteDirectory('arsip');
        $admin = User::where('role', 'admin')->firstOrFail();
        $this->actingAs($admin)->get('/admin/arsip?path='.urlencode($folder))->assertOk()->assertSee('SURAT_Belum_Menikah_WARGA_UJI.docx');
        $r = $this->actingAs($admin)->get('/admin/arsip/file?unduh=1&path='.urlencode($path))->assertOk();
        $this->assertSame('ISI-SURAT-WORD', $r->getContent());

        // ZIP juga mengambil dari Drive
        $this->actingAs($admin)->get('/admin/arsip/zip?path='.urlencode($folder))->assertOk();
    }

    public function test_arsip_data_contoh_tidak_dikirim_ke_drive(): void
    {
        $this->sambungkan();
        app(ArsipService::class)->simpan('arsip-contoh/Kasi Kesos/x/SURAT.docx', 'contoh');
        Http::assertNothingSent();
    }

    public function test_sinkronkan_menyalin_arsip_lama(): void
    {
        Storage::disk('local')->put('arsip/Kasi Kesos/lama/SURAT_LAMA.pdf', 'pdf-lama');
        $this->sambungkan();

        $admin = User::where('role', 'admin')->firstOrFail();
        $this->actingAs($admin)->post('/admin/drive/sinkron')->assertRedirect()->assertSessionHas('ok');
        $this->assertNotNull(DB::table('arsip_file')->where('path', 'arsip/Kasi Kesos/lama/SURAT_LAMA.pdf')->value('drive_id'));
    }

    public function test_menyambungkan_drive_lewat_google(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();
        $this->actingAs($admin)->get('/admin/drive')->assertOk()->assertSee('Sambungkan Google Drive kantor');

        $r = $this->actingAs($admin)->get('/admin/drive/sambung')->assertRedirect();
        $this->assertStringContainsString('drive.file', urldecode($r->headers->get('Location')));
        $state = session('drive_state');

        // State salah ditolak
        $this->actingAs($admin)->withSession(['drive_state' => $state])->get('/admin/drive/kembali?code=x&state=salah')
            ->assertRedirect('/admin/drive')->assertSessionHasErrors('drive');
        $this->assertFalse(\App\Services\GoogleDrive::terhubung());

        $this->actingAs($admin)->withSession(['drive_state' => $state])->get('/admin/drive/kembali?code=kode-uji&state='.$state)
            ->assertRedirect('/admin/drive')->assertSessionHas('ok');
        $this->assertTrue(\App\Services\GoogleDrive::terhubung());
        $this->assertSame('kantor@example.com', \App\Services\GoogleDrive::email());
        // Token tersimpan terenkripsi
        $this->assertNotSame('segar-uji', Pengaturan::ambil('drive_token'));
        $this->actingAs($admin)->get('/admin/drive')->assertOk()->assertSee('kantor@example.com');

        // Kasi tidak boleh membuka menu ini
        $kasi = User::where('role', 'kasi')->firstOrFail();
        $this->actingAs($kasi)->get('/admin/drive')->assertForbidden();

        $this->actingAs($admin)->delete('/admin/drive')->assertRedirect();
        $this->assertFalse(\App\Services\GoogleDrive::terhubung());
    }

    public function test_bagikan_folder_ke_email_google_kasi(): void
    {
        $this->sambungkan();
        User::where('name', 'Kasi Kesos')->update(['email' => 'kasi.kesos@example.com']);
        $admin = User::where('role', 'admin')->firstOrFail();

        $this->actingAs($admin)->post('/admin/drive/bagikan')->assertRedirect()->assertSessionHas('ok');
        Http::assertSent(fn (HttpRequest $r) => str_contains($r->url(), '/permissions') && $r['emailAddress'] === 'kasi.kesos@example.com');
    }
}
