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
            $data[$i['key']] = match ($i['tipe']) {
                'tanggal' => '2026-03-18',
                'pilihan' => end($i['pilihan']),
                default => 'ISI '.$i['key'],
            };
        }

        $p = Permohonan::create([
            'layanan_id' => $l->id, 'kode' => '261111-'.str_pad((string) $nomor, 3, '0', STR_PAD_LEFT),
            'nik' => '6303000101900001', 'nama' => 'Warga Uji', 'status' => 'baru', 'data' => $data,
            'tanggal_antrian' => today(), 'nomor_antrian' => $nomor,
            'nomor_surat' => '123 / KS-Pem / XI / 2026', 'tanggal_surat' => '2026-11-11',
        ])->load('layanan');
        $p->update(['folder_path' => ArsipService::folderPermohonan($p)]); // seperti PengajuanController

        return $p;
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
            if (! in_array($slug, ['pemekaran', 'domisili'])) { // pemekaran: hanya nama & NIK pelapor; domisili: alamat dari isian (diuji terpisah)
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

    private function teksXlsx(string $isi): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'uji');
        file_put_contents($tmp, $isi);
        $z = new \ZipArchive;
        $this->assertTrue($z->open($tmp) === true, 'xlsx tidak bisa dibuka');
        $teks = '';
        for ($i = 0; $i < $z->numFiles; $i++) {
            $n = $z->getNameIndex($i);
            if (preg_match('#^xl/(sharedStrings|workbook|worksheets/sheet\d+)\.xml$#', $n)) {
                $xml = $z->getFromIndex($i);
                $this->assertNotFalse(simplexml_load_string($xml), "$n rusak");
                $teks .= html_entity_decode(strip_tags(str_replace('</t>', '</t>|', $xml)), ENT_QUOTES | ENT_XML1, 'UTF-8');
            }
            if ($n === 'xl/workbook.xml') {
                $this->assertStringContainsString('fullCalcOnLoad="1"', $z->getFromIndex($i));
            }
        }
        $z->close();
        @unlink($tmp);

        return $teks;
    }

    public function test_template_excel_pindah_dan_sktm(): void
    {
        $pindah = Layanan::where('slug', 'pindah')->firstOrFail();
        $this->assertSame('xlsx', SuratService::ekstensiTemplate($pindah));
        $this->assertSame('{no} / 476.P / PEM-KS / {bulan_romawi} / {tahun}', $pindah->format_nomor);

        $p = $this->permohonan($pindah);
        $p->data = array_merge($p->data, ['anggota_yang_pindah' => "6303000000000011 - Anak Satu - Anak\n6303000000000012 | Anak Dua | Cucu"]);
        $teks = $this->teksXlsx(app(SuratService::class)->isiTemplateKantor($p, SuratService::templateWord($pindah)));

        $this->assertStringNotContainsString('${', $teks);
        $this->assertStringContainsString('|6|', $teks, 'kode alasan pindah "Keluarga" = 6');
        $this->assertStringContainsString('ANAK DUA', $teks);
        $this->assertStringContainsString('Jl. Contoh & Gang <1> RT. 001 RW. 002', $teks);
        $this->assertStringContainsString('Martapura, 11 November 2026', $teks);

        // Formulir Pelayanan: pilihan alasan pindah tampil sebagai daftar pilihan
        $pelayanan = User::where('role', 'pelayanan')->firstOrFail();
        $this->actingAs($pelayanan)->get('/admin/pengajuan/pindah')->assertOk()
            ->assertSee('name="isian[alasan_pindah]"', false)->assertSee('6. Keluarga')->assertSee('Anggota yang Pindah');

        $sktm = Layanan::where('slug', 'sktm')->firstOrFail();
        $teks = $this->teksXlsx(app(SuratService::class)->isiTemplateKantor($this->permohonan($sktm, 2), SuratService::templateWord($sktm)));
        $this->assertStringNotContainsString('${', $teks);
        $this->assertStringContainsString('WARGA UJI', $teks);
        $this->assertStringContainsString('ISI keperluan', $teks);
    }

    public function test_kasi_selesai_mengarsipkan_file_kantor(): void
    {
        Storage::fake('local');
        $kasi = User::where('name', 'Kasi Kesos')->firstOrFail();

        // Word (belum menikah) dan Excel (SKTM)
        foreach ([['belum-menikah', 'docx', 'Word'], ['sktm', 'xlsx', 'Excel']] as $i => [$slug, $ext, $app]) {
            $p = $this->permohonan(Layanan::where('slug', $slug)->firstOrFail(), $i + 1);
            $this->actingAs($kasi)->post("/admin/permohonan/{$p->id}/ambil")->assertRedirect();
            $this->actingAs($kasi)->get("/admin/permohonan/{$p->id}/surat")->assertOk()->assertSee("Unduh Surat ($app)")->assertSee('ISI keperluan');
            $this->actingAs($kasi)->get("/admin/permohonan/{$p->id}/unduh/pdf")->assertNotFound();
            $this->actingAs($kasi)->get("/admin/permohonan/{$p->id}/unduh/$ext")->assertOk();

            $nomor = '400.9/31'.$i.'/Kel.SKP';
            $this->actingAs($kasi)->post("/admin/permohonan/{$p->id}/surat", [
                'nomor_surat' => $nomor, 'tanggal_surat' => '2026-11-11', 'aksi' => 'selesai',
            ])->assertRedirect();

            $p->refresh()->load('layanan');
            $this->assertSame('selesai', $p->status);
            $folder = ArsipService::disk()->files($p->folder_path);
            $this->assertCount(1, $folder, "$slug: ".implode(', ', $folder));
            $this->assertStringEndsWith(".$ext", $folder[0]);
            $isi = ArsipService::disk()->get($folder[0]);
            $this->assertStringContainsString($nomor, $ext === 'xlsx' ? $this->teksXlsx($isi) : $this->teksDocx($isi));
        }
    }

    public function test_menu_layanan_dan_template_menampilkan_word_kantor(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();
        $pem = Layanan::where('slug', 'pemekaran')->firstOrFail();

        $this->actingAs($admin)->get('/admin/layanan')->assertOk()->assertSee('Word kantor')->assertSee('Excel kantor')->assertSee('Template website');
        $this->actingAs($admin)->get('/admin/layanan/pindah/template')->assertOk()->assertSee('dicetak dari file Excel')->assertSee('contoh-word');
        $this->actingAs($admin)->get("/admin/layanan/{$pem->slug}/template")->assertOk()
            ->assertSee('dicetak dari file Word')->assertSee('contoh-word')->assertDontSee('Pratinjau PDF');
        // Nama pejabat dari Profil Kelurahan masuk ke surat Word
        \App\Models\Pengaturan::simpan(['nama_kasi_pemerintahan' => 'KASI UJI, S.AP', 'nip_kasi_pemerintahan' => '19780000 000000 1 000']);
        $this->actingAs($admin)->get("/admin/layanan/{$pem->slug}/template")->assertSee('KASI UJI, S.AP');
        $teks = $this->teksDocx(app(SuratService::class)->docxDariWord($this->permohonan($pem, 9), SuratService::templateWord($pem)));
        $this->assertStringContainsString('KASI UJI, S.AP', $teks);
        $this->assertStringContainsString('19780000 000000 1 000', $teks);
        $this->actingAs($admin)->get("/admin/layanan/{$pem->slug}/contoh-word")->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->actingAs($admin)->get('/admin/layanan/usaha/template')->assertOk()->assertSee('Pratinjau PDF');
    }

    public function test_layanan_tanpa_word_tetap_pakai_template_html(): void
    {
        $this->assertNull(SuratService::templateWord(Layanan::where('slug', 'usaha')->first()));
        $this->assertNotNull(SuratService::templateWord(Layanan::where('slug', 'pemekaran')->first()));
    }

    public function test_domisili_penduduk_dari_word_kantor(): void
    {
        $l = Layanan::where('slug', 'domisili')->firstOrFail();
        $this->assertSame('docx', SuratService::ekstensiTemplate($l));
        $this->assertSame('pemerintahan', $l->bidang);
        $this->assertSame('domisili', $l->buku_nomor);
        $this->assertSame(['alamat_ktp', 'alamat_domisili', 'keperluan'], collect($l->isian)->pluck('key')->all());

        // Isian alamat diisi: dipakai apa adanya
        $teks = $this->teksDocx(app(SuratService::class)->docxDariWord($this->permohonan($l), SuratService::templateWord($l)));
        $this->assertStringContainsString('SURAT KETERANGAN DOMISILI PENDUDUK', $teks);
        $this->assertStringContainsString('ISI alamat_ktp', $teks);
        $this->assertStringContainsString('ISI alamat_domisili Kel. Sekumpul', $teks);
        $this->assertStringContainsString('Martapura, 02-01-1990', $teks);

        // Isian alamat kosong: pakai alamat penduduk
        $p = $this->permohonan($l, 2);
        $p->data = array_merge($p->data, ['alamat_ktp' => '', 'alamat_domisili' => '']);
        $teks = $this->teksDocx(app(SuratService::class)->docxDariWord($p, SuratService::templateWord($l)));
        $this->assertStringNotContainsString('${', $teks);
        $this->assertStringContainsString('Jl. Contoh & Gang <1> RT. 001 RW. 002 Kel. Sekumpul', $teks);

        // Nomor surat lanjut dari buku Domisili kantor ("299 / DP/ KS - XI / 2026" → 300)
        $p->update(['nomor_surat' => '299 / DP/ KS - XI / 2026', 'tanggal_surat' => now()]);
        $this->assertStringStartsWith('300 / DP/ KS - ', SuratService::nomorSuratBerikutnya($l));

        // Antrean: hanya Kasi Pemerintahan yang bisa mengambil
        $p3 = $this->permohonan($l, 3);
        $this->actingAs(User::where('name', 'Kasi Kesos')->firstOrFail())->get('/admin/antrean')->assertOk()->assertDontSee("/admin/permohonan/{$p3->id}/ambil");
        $this->actingAs(User::where('name', 'Kasi Pemerintahan')->firstOrFail())->get('/admin/antrean')->assertOk()->assertSee("/admin/permohonan/{$p3->id}/ambil");
    }
}
