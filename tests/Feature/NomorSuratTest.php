<?php

namespace Tests\Feature;

use App\Models\Layanan;
use App\Models\Permohonan;
use App\Services\SuratService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Nomor surat mengikuti buku nomor kantor per Kasi. */
class NomorSuratTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function layanan(string $slug): Layanan
    {
        return Layanan::where('slug', $slug)->firstOrFail();
    }

    private function sudahBernomor(string $slug, string $nomor, int $urut): void
    {
        Permohonan::create([
            'layanan_id' => $this->layanan($slug)->id, 'kode' => '261111-'.str_pad((string) $urut, 3, '0', STR_PAD_LEFT),
            'nik' => '6303000000000001', 'nama' => 'Warga', 'status' => 'selesai', 'data' => [],
            'tanggal_antrian' => today(), 'nomor_antrian' => $urut,
            'nomor_surat' => $nomor, 'tanggal_surat' => now(),
        ]);
    }

    public function test_ambil_urut_dari_nomor_kantor(): void
    {
        $this->assertSame(294, SuratService::ambilUrut('294 / KS-Pem / XI / 2026', '{no} / KS-Pem / {bulan_romawi} / {tahun}'));
        $this->assertSame(167, SuratService::ambilUrut('167/ KS-Pem / VI / 2026', '{no} / KS-Pem / {bulan_romawi} / {tahun}'));
        $this->assertSame(29, SuratService::ambilUrut('472. M / 029 / KS- IV / 2026', '472. M / {no} / KS - {bulan_romawi} / {tahun}'));
        $this->assertSame(310, SuratService::ambilUrut('400.9/310/Kel.Skp', '400.9/{no}/Kel.SKP'));
        $this->assertSame(0, SuratService::ambilUrut('ngawur', '400.9/{no}/Kel.SKP'));
    }

    public function test_nomor_awal_sesuai_format_kantor(): void
    {
        $bulan = SuratService::romawi(now()->month);
        $tahun = now()->year;

        $this->assertSame("001 / KS-Pem / $bulan / $tahun", SuratService::nomorSuratBerikutnya($this->layanan('orang-yang-sama')));
        $this->assertSame("472. M / 001 / KS - $bulan / $tahun", SuratService::nomorSuratBerikutnya($this->layanan('kematian')));
        $this->assertSame('400.9/001/Kel.SKP', SuratService::nomorSuratBerikutnya($this->layanan('sktm')));
        $this->assertSame('400.10/001/Kel.SKP', SuratService::nomorSuratBerikutnya($this->layanan('penghasilan')));
    }

    public function test_nomor_lanjut_dari_terakhir_per_buku(): void
    {
        // Kasi mengetik nomor buku kantor sekali
        $this->sudahBernomor('pemekaran', '294 / SK-Pem / XI / 2026', 1);
        $this->sudahBernomor('kematian', '472. M / 068 / KS - XI / 2026', 2);
        $this->sudahBernomor('belum-bekerja', '400.9/312/Kel.SKP', 3);

        $bulan = SuratService::romawi(now()->month);
        $tahun = now()->year;

        // Satu buku Kasi Pemerintahan: lanjut walau jenis suratnya beda
        $this->assertSame("295 / SKKB / KS - $bulan / $tahun", SuratService::nomorSuratBerikutnya($this->layanan('skbb')));
        // Kematian punya buku sendiri
        $this->assertSame("472. M / 069 / KS - $bulan / $tahun", SuratService::nomorSuratBerikutnya($this->layanan('kematian')));
        $this->assertSame('400.9/313/Kel.SKP', SuratService::nomorSuratBerikutnya($this->layanan('sktm')));
        // Buku Kasi PM belum dipakai
        $this->assertSame('400.10/001/Kel.SKP', SuratService::nomorSuratBerikutnya($this->layanan('domisili-usaha')));
    }
}
