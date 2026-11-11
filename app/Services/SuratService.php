<?php

namespace App\Services;

use App\Models\Layanan;
use App\Models\Pengaturan;
use App\Models\Permohonan;
use Barryvdh\DomPDF\Facade\Pdf;
use DOMDocument;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\Shared\Html;

/**
 * Mengisi template surat dengan data pemohon, lalu mengubahnya menjadi PDF atau Word (.docx).
 * Penanda di template ditulis {{nama_penanda}}, misalnya {{nama}}, {{nik}}, {{nomor_surat}}.
 */
class SuratService
{
    /** Penanda umum yang selalu tersedia, untuk ditampilkan di editor template. */
    public const PENANDA_UMUM = [
        'kop_surat' => 'Kop surat resmi (logo + nama kelurahan)',
        'nomor_surat' => 'Nomor surat',
        'tanggal_surat' => 'Tanggal surat (mis. 08 November 2026)',
        'nik' => 'NIK',
        'no_kk' => 'Nomor KK',
        'nama' => 'Nama lengkap',
        'tempat_lahir' => 'Tempat lahir',
        'tanggal_lahir' => 'Tanggal lahir',
        'ttl' => 'Tempat, tanggal lahir',
        'umur' => 'Umur (tahun)',
        'jenis_kelamin' => 'Jenis kelamin',
        'agama' => 'Agama',
        'status_perkawinan' => 'Status perkawinan',
        'pekerjaan' => 'Pekerjaan',
        'kewarganegaraan' => 'Kewarganegaraan',
        'alamat' => 'Alamat',
        'rt' => 'RT',
        'rw' => 'RW',
        'no_hp' => 'Nomor HP',
        'nama_kelurahan' => 'Nama kelurahan',
        'kecamatan' => 'Kecamatan',
        'kabupaten' => 'Kabupaten',
        'provinsi' => 'Provinsi',
        'nama_lurah' => 'Nama penandatangan',
        'nip_lurah' => 'NIP penandatangan',
        'jabatan_ttd' => 'Jabatan penandatangan',
        'nama_kasi_pemerintahan' => 'Nama Kasi Pemerintahan',
        'nip_kasi_pemerintahan' => 'NIP Kasi Pemerintahan',
        'nama_kasi_kesos' => 'Nama Kasi Kesos',
        'nip_kasi_kesos' => 'NIP Kasi Kesos',
        'nama_kasi_pm' => 'Nama Kasi PM',
        'nip_kasi_pm' => 'NIP Kasi PM',
    ];

    public static function tanggalIndo($tanggal): string
    {
        if (! $tanggal) {
            return '';
        }
        try {
            return Carbon::parse($tanggal)->locale('id')->translatedFormat('d F Y');
        } catch (\Throwable) {
            return (string) $tanggal;
        }
    }

    public static function romawi(int $n): string
    {
        return ['', 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'][$n] ?? (string) $n;
    }

    public static function nomorSuratBerikutnya(Layanan $layanan): string
    {
        $urut = Permohonan::whereYear('tanggal_surat', now()->year)->whereNotNull('nomor_surat')->count() + 1;

        return strtr(Pengaturan::ambil('format_nomor'), [
            '{kode}' => $layanan->kode_surat ?: '470',
            '{no}' => str_pad((string) $urut, 3, '0', STR_PAD_LEFT),
            '{bulan_romawi}' => self::romawi(now()->month),
            '{bulan}' => now()->format('m'),
            '{tahun}' => now()->format('Y'),
        ]);
    }

    /** Kumpulkan semua nilai penanda untuk satu permohonan. */
    public function nilaiPenanda(Permohonan $p): array
    {
        $d = $p->data ?? [];
        $s = Pengaturan::semua();

        $nilai = [
            'nomor_surat' => $p->nomor_surat ?: '....../......../......',
            'tanggal_surat' => self::tanggalIndo($p->tanggal_surat ?? now()),
            'nik' => $p->nik,
            'nama' => mb_strtoupper($p->nama),
            'no_hp' => $p->no_hp,
            'tanggal_lahir' => self::tanggalIndo($d['tanggal_lahir'] ?? null),
            'ttl' => trim(($d['tempat_lahir'] ?? '').', '.self::tanggalIndo($d['tanggal_lahir'] ?? null), ', '),
            'umur' => ! empty($d['tanggal_lahir']) ? (string) Carbon::parse($d['tanggal_lahir'])->age : '',
            'nama_kelurahan' => $s['nama_kelurahan'],
            'kecamatan' => $s['kecamatan'],
            'kabupaten' => $s['kabupaten'],
            'provinsi' => $s['provinsi'],
            'nama_lurah' => $s['nama_lurah'],
            'nip_lurah' => $s['nip_lurah'],
            'jabatan_ttd' => $s['jabatan_ttd'],
        ];
        foreach (array_keys(\App\Models\Layanan::BIDANG) as $b) {
            $nilai['nama_kasi_'.$b] = $s['nama_kasi_'.$b] ?? '';
            $nilai['nip_kasi_'.$b] = $s['nip_kasi_'.$b] ?? '';
        }

        foreach (['no_kk', 'tempat_lahir', 'jenis_kelamin', 'agama', 'status_perkawinan', 'pekerjaan', 'kewarganegaraan', 'alamat', 'rt', 'rw'] as $k) {
            $nilai[$k] = $d[$k] ?? '';
        }

        foreach ($p->layanan->isian ?? [] as $isian) {
            $v = $d[$isian['key']] ?? '';
            $nilai[$isian['key']] = $isian['tipe'] === 'tanggal' ? self::tanggalIndo($v) : $v;
        }

        return $nilai;
    }

    /**
     * Ganti {{penanda}} dengan nilai. Nilai di-escape agar aman.
     * Penanda di $tunda dibiarkan apa adanya untuk diisi belakangan.
     * {{kop_surat}} selalu dibiarkan, karena kop baru dipasang saat surat dicetak.
     */
    public function isiTemplate(string $html, array $nilai, array $tunda = []): string
    {
        $tunda[] = 'kop_surat';

        return preg_replace_callback('/\{\{\s*([a-z0-9_]+)\s*\}\}/i', function ($m) use ($nilai, $tunda) {
            $kunci = strtolower($m[1]);
            if (in_array($kunci, $tunda)) {
                return '{{'.$kunci.'}}';
            }

            return array_key_exists($kunci, $nilai) ? nl2br(e((string) $nilai[$kunci])) : $m[0];
        }, $html);
    }

    /** Draf surat untuk editor: data pemohon sudah terisi, nomor & tanggal surat masih berupa penanda. */
    public function renderUntuk(Permohonan $p): string
    {
        return $this->isiTemplate($p->layanan->template_html ?? '', $this->nilaiPenanda($p), ['nomor_surat', 'tanggal_surat']);
    }

    /** Isi akhir surat yang siap dicetak (nomor & tanggal surat sudah dimasukkan). */
    public function final(Permohonan $p): string
    {
        return $this->isiTemplate($p->surat_html ?? '', $this->nilaiPenanda($p));
    }

    public static function logoDataUri(): ?string
    {
        $logo = Pengaturan::ambil('logo');
        if (! $logo || ! Storage::disk('public')->exists($logo)) {
            return null;
        }
        $mime = Storage::disk('public')->mimeType($logo) ?: 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode(Storage::disk('public')->get($logo));
    }

    public function kopHtml(): string
    {
        $s = Pengaturan::semua();
        $logo = self::logoDataUri();
        $baris = '<p style="text-align: center; margin: 0; font-size: 14pt;"><strong>PEMERINTAH KABUPATEN '.e(mb_strtoupper($s['kabupaten'])).'</strong></p>'
            .'<p style="text-align: center; margin: 0; font-size: 14pt;"><strong>KECAMATAN '.e(mb_strtoupper($s['kecamatan'])).'</strong></p>'
            .'<p style="text-align: center; margin: 0; font-size: 16pt;"><strong>KELURAHAN '.e(mb_strtoupper($s['nama_kelurahan'])).'</strong></p>'
            .'<p style="text-align: center; margin: 0; font-size: 10pt;">'.e($s['alamat_kantor'])
            .($s['telepon'] ? ' Telp. '.e($s['telepon']) : '').($s['email'] ? ' Email: '.e($s['email']) : '').'</p>';

        if ($logo) {
            $isi = '<table style="width: 100%; border-collapse: collapse;" width="100%"><tr>'
                .'<td style="width: 15%; text-align: center; vertical-align: middle;" width="15%"><img src="'.$logo.'" width="75" height="85" style="width: 75px; height: 85px;" /></td>'
                .'<td style="width: 85%; vertical-align: middle;" width="85%">'.$baris.'</td>'
                .'</tr></table>';
        } else {
            $isi = $baris;
        }

        return $isi.'<hr style="border: 0; border-top: 3px solid #000; margin: 4px 0 12px 0;" />';
    }

    public function bungkusHtml(string $isi): string
    {
        $isi = str_replace(['{{kop_surat}}', '{{ kop_surat }}'], $this->kopHtml(), $isi);
        // Hilangkan <p> pembungkus kop yang ditambahkan editor.
        $isi = preg_replace('#<p>\s*(<table.*?</table><hr[^>]*>)\s*</p>#s', '$1', $isi);

        return '<!DOCTYPE html><html><head><meta charset="utf-8"><style>'
            .'@page { margin: 1.5cm 2cm 2cm 2.5cm; }'
            .'body { font-family: "Times New Roman", Times, serif; font-size: 12pt; line-height: 1.35; }'
            .'p { margin: 0 0 6pt 0; } table { border-collapse: collapse; } td { padding: 1px 3px; vertical-align: top; }'
            .'</style></head><body>'.$isi.'</body></html>';
    }

    public static function kertas(): string
    {
        return Pengaturan::ambil('kertas', 'A4') === 'F4' ? 'F4' : 'A4';
    }

    public function pdf(string $isi): string
    {
        // F4/Folio = 215 x 330 mm (dalam point)
        $ukuran = self::kertas() === 'F4' ? [0, 0, 609.45, 935.43] : 'a4';

        return Pdf::loadHTML($this->bungkusHtml($this->keXhtml($isi)))->setPaper($ukuran, 'portrait')->output();
    }

    public function docx(string $isi): string
    {
        // Wajib: tanpa ini karakter seperti & atau < di data warga membuat file Word rusak.
        Settings::setOutputEscapingEnabled(true);

        $word = new PhpWord;
        $word->setDefaultFontName('Times New Roman');
        $word->setDefaultFontSize(12);
        $word->setDefaultParagraphStyle(['spaceAfter' => 80, 'spacing' => 0]);

        $section = $word->addSection([
            'paperSize' => self::kertas() === 'F4' ? 'Folio' : 'A4',
            'marginTop' => 850, 'marginBottom' => 1134, 'marginLeft' => 1418, 'marginRight' => 1134,
        ]);

        $html = str_replace(['{{kop_surat}}', '{{ kop_surat }}'], $this->kopHtml(), $isi);
        Html::addHtml($section, $this->keXhtml($html), false, false);
        $this->izinkanTeksTurunBaris($section);

        $tmp = tempnam(sys_get_temp_dir(), 'surat');
        IOFactory::createWriter($word, 'Word2007')->save($tmp);
        $hasil = file_get_contents($tmp);
        @unlink($tmp);

        return $hasil;
    }

    /** PhpWord bawaannya melarang teks di sel tabel turun baris; alamat panjang jadi melebar. Matikan. */
    private function izinkanTeksTurunBaris($wadah): void
    {
        foreach ($wadah->getElements() as $el) {
            if ($el instanceof \PhpOffice\PhpWord\Element\Table) {
                foreach ($el->getRows() as $baris) {
                    foreach ($baris->getCells() as $sel) {
                        $sel->getStyle()->setNoWrap(false);
                        $this->izinkanTeksTurunBaris($sel);
                    }
                }
            }
        }
    }

    /**
     * PhpWord butuh HTML yang rapi (XHTML). Fungsi ini merapikan HTML dari editor,
     * dan membuang gambar yang bukan data:image supaya tidak bisa membaca file lain di server.
     */
    public function keXhtml(string $html): string
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8"><html><body>'.$html.'</body></html>', LIBXML_NONET);
        libxml_clear_errors();

        foreach (iterator_to_array($dom->getElementsByTagName('img')) as $img) {
            if (! str_starts_with($img->getAttribute('src'), 'data:image/')) {
                $img->parentNode->removeChild($img);
            }
        }
        // PhpWord tidak bisa menaruh tabel di dalam paragraf: angkat tabel keluar dari <p>.
        foreach (iterator_to_array($dom->getElementsByTagName('table')) as $tabel) {
            $induk = $tabel->parentNode;
            if ($induk && $induk->nodeName === 'p') {
                $induk->parentNode->insertBefore($tabel, $induk);
            }
        }

        $body = $dom->getElementsByTagName('body')->item(0);
        $xhtml = '';
        foreach ($body->childNodes as $anak) {
            $xhtml .= $dom->saveXML($anak);
        }

        return $xhtml;
    }
}
