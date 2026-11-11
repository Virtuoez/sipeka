<?php

namespace Tests\Feature;

use App\Models\Layanan;
use App\Models\Permohonan;
use App\Models\User;
use App\Services\ArsipService;
use App\Services\SuratService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Surat dicetak dari file Word kantor (resources/surat/{slug}.docx). */
class SuratWordTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function permohonan(Layanan $l, int $nomor = 1): Permohonan
    {
        $data = [
            'no_kk' => '6303000101900000', 'tempat_lahir' => 'Martapura', 'tanggal_lahir' => '1990-01-02',
            'jenis_kelamin' => 'Laki-laki', 'agama' => 'Islam', 'status_perkawinan' => 'Kawin', 'pekerjaan' => 'Wiraswasta',
            'kewarganegaraan' => 'WNI', 'alamat' => 'Jl. Contoh & Gang <1>', 'rt' => '001', 'rw' => '002',
        ];
        foreach ($l->isian ?? [] as $i) {
            $data[$i['key']] = $i['tipe'] === 'tanggal' ? '2026-03-18' : 'ISI '.$i['key'];
        }

        return Permohonan::create([
            'layanan_id' => $l->id, 'kode' => '261111-'.str_pad((string) $nomor, 3, '0', STR_PAD_LEFT),
            'nik' => '6303000101900001', 'nama' => 'Warga Uji', 'status' => 'baru', 'data' => $data,
            'tanggal_antrian' => today(), 'nomor_antrian' => $nomor,
            'nomor_surat' => '123 / KS-Pem / XI / 2026', 'tanggal_surat' => '2026-11-11',
        ])->load('layanan');
    }

    private function teksDocx(string $isi): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'uji');
        file_put_contents($tmp, $isi);
        $z = new \ZipArchive;
        $this->assertTrue($z->open($tmp) === true, 'docx tidak bisa dibuka');
        $teks = '';
        for ($i = 0; $i < $z->numFiles; $i++) {
            $n = $z->getNameIndex($i);
            if (preg_match('#^word/(document|header\d*|footer\d*)\.xml$#', $n)) {
                $xml = $z->getFromIndex($i);
                $this->assertNotFalse(simplexml_load_string($xml), "$n rusak");
                $teks .= html_entity_decode(strip_tags($xml), ENT_QUOTES | ENT_XML1, 'UTF-8');
            }
        }
        $z->close();
        @unlink($tmp);

        return $teks;
    }

    public function test_semua_template_word_terisi_tanpa_penanda_tersisa(): void
    {
        $slugs = array_map(fn ($f) => basename($f, '.docx'), glob(resource_path('surat/*.docx')));
        $this->assertCount(14, $slugs);

        foreach ($slugs as $i => $slug) {
            $l = Layanan::where('slug', $slug)->firstOrFail();
            $p = $this->permohonan($l, $i + 1);
            $teks = $this->teksDocx(app(SuratService::class)->docxDariWord($p, SuratService::templateWord($l)));

            $this->assertStringNotContainsString('${', $teks, "$slug: penanda tersisa");
            if ($slug !== 'pemekaran') { // pemekaran hanya memuat nama & NIK pelapor
                $this->assertStringContainsString('Jl. Contoh & Gang <1>', $teks, "$slug: alamat tidak masuk");
            }
            if (! in_array($slug, ['kelahiran'])) {
                $this->assertStringContainsString('WARGA UJI', $teks, "$slug: nama tidak masuk");
            }
        }
    }

    public function test_kematian_hari_dan_tanggal_angka(): void
    {
        $l = Layanan::where('slug', 'kematian')->firstOrFail();
        $teks = $this->teksDocx(app(SuratService::class)->docxDariWord($this->permohonan($l), SuratService::templateWord($l)));

        $this->assertStringContainsString('Rabu / 18-03-2026', $teks);
        $pem = Layanan::where('slug', 'pemekaran')->firstOrFail();
        $this->assertStringContainsString('an. ISI atas_nama_shm dan sudah balik nama ke pemegang hak an. ISI balik_nama_ke_pemegang_hak yang',
            $this->teksDocx(app(SuratService::class)->docxDariWord($this->permohonan($pem, 2), SuratService::templateWord($pem))));
        $this->assertStringContainsString('Martapura, 02-01-1990', $teks);
        $this->assertStringContainsString('123 / KS-Pem / XI / 2026', $teks);
    }

    public function test_kasi_selesai_mengarsipkan_word_kantor(): void
    {
        Storage::fake('local');
        $l = Layanan::where('slug', 'sktm')->firstOrFail();
        $p = $this->permohonan($l);
        $kasi = User::where('name', 'Kasi Kesos')->firstOrFail();

        $this->actingAs($kasi)->post("/admin/permohonan/{$p->id}/ambil")->assertRedirect();
        $this->actingAs($kasi)->get("/admin/permohonan/{$p->id}/surat")->assertOk()->assertSee('Unduh Surat (Word)')->assertSee('ISI keperluan');
        $this->actingAs($kasi)->get("/admin/permohonan/{$p->id}/unduh/pdf")->assertNotFound();

        $this->actingAs($kasi)->post("/admin/permohonan/{$p->id}/surat", [
            'nomor_surat' => '400.9/313/Kel.SKP', 'tanggal_surat' => '2026-11-11', 'aksi' => 'selesai',
        ])->assertRedirect();

        $p->refresh()->load('layanan');
        $this->assertSame('selesai', $p->status);
        $folder = ArsipService::disk()->files($p->folder_path);
        $this->assertCount(1, $folder);
        $this->assertStringEndsWith('.docx', $folder[0]);
        $this->assertStringContainsString('400.9/313/Kel.SKP', $this->teksDocx(ArsipService::disk()->get($folder[0])));
    }

    public function test_layanan_tanpa_word_tetap_pakai_template_html(): void
    {
        $this->assertNull(SuratService::templateWord(Layanan::where('slug', 'domisili')->first()));
        $this->assertNotNull(SuratService::templateWord(Layanan::where('slug', 'pemekaran')->first()));
    }
}
