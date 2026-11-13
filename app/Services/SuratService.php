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
use PhpOffice\PhpWord\TemplateProcessor;

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

    public static function formatNomor(?Layanan $layanan): string
    {
        return $layanan?->format_nomor ?: Pengaturan::ambil('format_nomor');
    }

    /**
     * Nomor surat berikutnya.
     * - Layanan dengan buku nomor: lanjut dari nomor TERBESAR yang sudah dipakai di buku itu tahun ini
     *   (jadi kalau Kasi sekali mengetik nomor buku kantor, mis. 295, berikutnya otomatis 296).
     * - Tanpa buku nomor: nomor urut semua surat tahun ini (cara lama).
     */
    public static function nomorSuratBerikutnya(Layanan $layanan): string
    {
        $tahunIni = Permohonan::whereYear('tanggal_surat', now()->year)->whereNotNull('nomor_surat');

        if ($layanan->buku_nomor) {
            $terakhir = 0;
            $tahunIni->whereHas('layanan', fn ($q) => $q->where('buku_nomor', $layanan->buku_nomor))
                ->with('layanan')->get(['id', 'layanan_id', 'nomor_surat'])
                ->each(function ($p) use (&$terakhir) {
                    $terakhir = max($terakhir, self::ambilUrut($p->nomor_surat, self::formatNomor($p->layanan)));
                });
            $urut = $terakhir + 1;
        } else {
            $urut = $tahunIni->count() + 1;
        }

        return strtr(self::formatNomor($layanan), [
            '{kode}' => $layanan->kode_surat ?: '470',
            '{no}' => str_pad((string) $urut, 3, '0', STR_PAD_LEFT),
            '{bulan_romawi}' => self::romawi(now()->month),
            '{bulan}' => now()->format('m'),
            '{tahun}' => now()->format('Y'),
        ]);
    }

    /** Ambil angka {no} dari nomor surat jadi, mis. "295 / KS-Pem / XI / 2026" → 295. Spasi boleh beda. */
    public static function ambilUrut(?string $nomor, string $format): int
    {
        if (! $nomor || ! str_contains($format, '{no}')) {
            return 0;
        }
        $pola = '';
        foreach (preg_split('/(\{[a-z_]+\})/', $format, -1, PREG_SPLIT_DELIM_CAPTURE) as $bagian) {
            $pola .= match (true) {
                $bagian === '{no}' => '(\d+)',
                (bool) preg_match('/^\{[a-z_]+\}$/', $bagian) => '.*?',
                default => str_replace(' ', '\s*', preg_quote(preg_replace('/\s+/', ' ', $bagian), '/')),
            };
        }

        return preg_match('/^\s*'.$pola.'\s*$/iu', $nomor, $m) ? (int) $m[1] : 0;
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
        $balik = trim((string) ($d['balik_nama_ke_pemegang_hak'] ?? ''));
        $nilai['balik_nama'] = $balik !== '' ? ' dan sudah balik nama ke pemegang hak an. '.$balik : '';
        $nilai['nama_camat'] = $s['nama_camat'] ?? '';
        $nilai['nip_camat'] = $s['nip_camat'] ?? '';

        foreach ($p->layanan->isian ?? [] as $isian) {
            $v = $d[$isian['key']] ?? '';
            $nilai[$isian['key']] = $isian['tipe'] === 'tanggal' ? self::tanggalIndo($v) : $v;
            if ($isian['tipe'] === 'pilihan') {
                // Nomor urut pilihan, mis. Alasan Pindah "Keluarga" → 6 (formulir pindah kantor memakai kode angka)
                $i = array_search($v, $isian['pilihan'] ?? [], true);
                $nilai[$isian['key'].'_kode'] = $i === false ? '' : (string) ($i + 1);
            }
        }

        // Surat Domisili: Alamat KTP / Alamat Domisili kosong → alamat penduduk (warga yang KTP-nya sudah Sekumpul)
        $jalanRt = trim($nilai['alamat'].' RT. '.($nilai['rt'] ?: '-').' RW. '.($nilai['rw'] ?: '-'));
        if (array_key_exists('alamat_ktp', $nilai) && trim((string) $nilai['alamat_ktp']) === '') {
            $nilai['alamat_ktp'] = $jalanRt.' Kel. '.$s['nama_kelurahan'].' Kec. '.$s['kecamatan'].' Kab. '.$s['kabupaten'];
        }
        if (array_key_exists('alamat_domisili', $nilai) && trim((string) $nilai['alamat_domisili']) === '') {
            $nilai['alamat_domisili'] = $jalanRt;
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

    // ---------------- Template Word kantor ----------------

    /** Folder (disk local) untuk template Word yang diunggah admin, menimpa bawaan resources/surat. */
    public const FOLDER_TEMPLATE_WORD = 'template-surat';

    /** Jenis file template kantor yang didukung: Word & Excel. */
    public const EKSTENSI_TEMPLATE = ['docx', 'xlsx'];

    public const MIME = [
        'pdf' => 'application/pdf',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ];

    /** Daftar ${penanda} di file Excel (urut kemunculan di sharedStrings / sel teks). */
    public static function penandaExcel(string $template): array
    {
        $zip = new \ZipArchive;
        if ($zip->open($template) !== true) {
            return [];
        }
        $semua = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            if (preg_match('#^xl/(sharedStrings\.xml|worksheets/sheet\d+\.xml)$#', $zip->getNameIndex($i))
                && preg_match_all('/\$\{([a-z0-9_]+)\}/i', $zip->getFromIndex($i), $m)) {
                array_push($semua, ...array_map('strtolower', $m[1]));
            }
        }
        $zip->close();

        return array_values(array_unique($semua));
    }

    /**
     * Path file kantor (Word .docx / Excel .xlsx) yang dipakai layanan ini: unggahan admin, lalu bawaan
     * resources/surat/{slug}.docx|xlsx. Null = pakai template HTML.
     */
    public static function templateWord(?Layanan $layanan): ?string
    {
        if (! $layanan?->slug || ! preg_match('/^[a-z0-9\-_]+$/i', $layanan->slug)) {
            return null;
        }
        foreach ([Storage::disk('local')->path(self::FOLDER_TEMPLATE_WORD), resource_path('surat')] as $folder) {
            foreach (self::EKSTENSI_TEMPLATE as $ext) {
                if (is_file($f = $folder.'/'.$layanan->slug.'.'.$ext)) {
                    return $f;
                }
            }
        }

        return null;
    }

    /** "docx" atau "xlsx" untuk template kantor layanan ini (null = tidak ada). */
    public static function ekstensiTemplate(?Layanan $layanan): ?string
    {
        $f = self::templateWord($layanan);

        return $f ? strtolower(pathinfo($f, PATHINFO_EXTENSION)) : null;
    }

    /** Isi template kantor (Word/Excel) dengan data permohonan. Hasil = isi file. */
    public function isiTemplateKantor(Permohonan $p, string $template): string
    {
        return str_ends_with(strtolower($template), '.xlsx') ? $this->xlsxDariExcel($p, $template) : $this->docxDariWord($p, $template);
    }

    /**
     * Isi file Excel kantor: ganti ${penanda} di teks sel (sharedStrings & sel teks), lalu Excel menghitung ulang
     * rumus saat file dibuka (lembar surat mengambil data dari lembar formulir).
     */
    public function xlsxDariExcel(Permohonan $p, string $template): string
    {
        $nilai = $this->nilaiExcel($p);
        $tmp = tempnam(sys_get_temp_dir(), 'surat');
        copy($template, $tmp);

        $zip = new \ZipArchive;
        $zip->open($tmp);
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $nama = $zip->getNameIndex($i);
            if (! preg_match('#^xl/(sharedStrings\.xml|worksheets/sheet\d+\.xml|workbook\.xml)$#', $nama)) {
                continue;
            }
            $xml = $zip->getFromIndex($i);
            $xml = preg_replace_callback('/\$\{([a-z0-9_]+)\}/i', function ($m) use ($nilai) {
                $k = strtolower($m[1]);
                $v = trim(preg_replace('/\s+/u', ' ', (string) ($nilai[$k] ?? '')));
                if ($v === '' && ! preg_match('/^p\d+_/', $k)) {
                    $v = $k === 'nama_fasilitator_puskesos' ? '…………………….' : '-';
                }

                return htmlspecialchars($v, ENT_XML1 | ENT_QUOTES, 'UTF-8');
            }, $xml);
            if (str_starts_with($nama, 'xl/worksheets/')) {
                // Buang hasil rumus lama supaya tidak tampil data template; Excel menghitung ulang
                $xml = preg_replace('#(<f>[^<]*</f>|<f [^>]*/>|<f [^>]*>[^<]*</f>)<v>[^<]*</v>#', '$1', $xml);
            }
            if ($nama === 'xl/workbook.xml') {
                $xml = preg_match('/<calcPr\b[^>]*fullCalcOnLoad=/', $xml) ? $xml
                    : preg_replace('/<calcPr\b/', '<calcPr fullCalcOnLoad="1"', $xml, 1);
            }
            $zip->addFromString($nama, $xml);
        }
        $zip->close();
        $hasil = file_get_contents($tmp);
        @unlink($tmp);

        return $hasil;
    }

    /** Nilai untuk template Excel: seperti Word + daftar pengikut pindah (p1_nik, p1_nama, ...). */
    public function nilaiExcel(Permohonan $p): array
    {
        $nilai = $this->nilaiWord($p);
        $nilai['nama_fasilitator_puskesos'] = Pengaturan::ambil('nama_fasilitator_puskesos', '');

        // "Anggota yang Pindah": satu orang per baris, "NIK - Nama - Keterangan". Kosong = pemohon sendiri.
        $baris = array_values(array_filter(array_map('trim', preg_split('/\r?\n/', (string) (($p->data ?? [])['anggota_yang_pindah'] ?? '')))));
        $orang = array_map(function ($b) {
            $bagian = array_map('trim', preg_split('/\s+[-|;]\s+|\t|\s*\|\s*/', $b));

            return ['nik' => $bagian[0] ?? '', 'nama' => mb_strtoupper($bagian[1] ?? ''), 'ket' => $bagian[2] ?? ''];
        }, $baris) ?: [['nik' => $p->nik, 'nama' => mb_strtoupper((string) $p->nama), 'ket' => '']];
        foreach ($orang as $i => $o) {
            $n = $i + 1;
            $nilai["p{$n}_no"] = (string) $n;
            $nilai["p{$n}_nik"] = $o['nik'];
            $nilai["p{$n}_nama"] = $o['nama'];
            $nilai["p{$n}_ket"] = $o['ket'] !== '' ? $o['ket'] : '-';
        }
        $nilai['jumlah_pengikut'] = (string) count($orang);

        return $nilai;
    }

    public static function tanggalAngka($tanggal): string
    {
        try {
            return $tanggal ? Carbon::parse($tanggal)->format('d-m-Y') : '';
        } catch (\Throwable) {
            return (string) $tanggal;
        }
    }

    public static function hari($tanggal): string
    {
        try {
            return $tanggal ? Carbon::parse($tanggal)->locale('id')->translatedFormat('l') : '';
        } catch (\Throwable) {
            return '';
        }
    }

    /** Nilai penanda untuk template Word: penanda biasa + variasi tanggal (dd-mm-yyyy, hari) + kalimat tambahan. */
    public function nilaiWord(Permohonan $p): array
    {
        $d = $p->data ?? [];
        $s = Pengaturan::semua();
        $nilai = $this->nilaiPenanda($p);

        $nilai['tanggal_lahir_angka'] = self::tanggalAngka($d['tanggal_lahir'] ?? null);
        $nilai['ttl_angka'] = trim(($d['tempat_lahir'] ?? '').', '.$nilai['tanggal_lahir_angka'], ', ');
        foreach ($p->layanan->isian ?? [] as $isian) {
            if ($isian['tipe'] === 'tanggal') {
                $nilai[$isian['key'].'_angka'] = self::tanggalAngka($d[$isian['key']] ?? null);
                $nilai[$isian['key'].'_hari'] = self::hari($d[$isian['key']] ?? null);
            }
        }
        // Word: "${atas_nama_shm}${balik_nama}" — spasi di awal nilai bisa hilang, jadi kalimat balik nama digabung ke atas nama
        if (isset($nilai['atas_nama_shm'])) {
            $nilai['atas_nama_shm'] .= $nilai['balik_nama'];
        }
        $nilai['balik_nama'] = '';

        return $nilai;
    }

    /** Isi file Word kantor dengan data permohonan. Hasil .docx (isi file). */
    public function docxDariWord(Permohonan $p, string $template): string
    {
        // Wajib: tanpa ini karakter & atau < di data warga membuat file Word rusak.
        Settings::setOutputEscapingEnabled(true);

        $tp = new TemplateProcessor($template);
        $nilai = $this->nilaiWord($p);
        foreach (array_unique($tp->getVariables()) as $penanda) {
            $isi = trim(preg_replace('/\s+/u', ' ', (string) ($nilai[$penanda] ?? '')));
            if ($isi === '' && $penanda !== 'balik_nama') {
                // Kosong: tulis seperti surat kantor ("-" atau titik-titik untuk batas tanah)
                $isi = str_starts_with($penanda, 'batas_') ? '…… berbatasan dengan tanah ………………………….' : '-';
            }
            $tp->setValue($penanda, $isi);
        }

        $tmp = tempnam(sys_get_temp_dir(), 'surat');
        $tp->saveAs($tmp);
        $hasil = file_get_contents($tmp);
        @unlink($tmp);

        return $hasil;
    }

    public static function logoDataUri(): ?string
    {
        $logo = Pengaturan::ambil('logo');
        if (! $logo || ! Storage::disk('public')->exists($logo)) {
            // Belum ada logo unggahan: pakai logo Kabupaten Banjar bawaan (dari surat kantor).
            return 'data:image/png;base64,'.base64_encode(file_get_contents(resource_path(self::LOGO_BAWAAN)));
        }
        $mime = Storage::disk('public')->mimeType($logo) ?: 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode(Storage::disk('public')->get($logo));
    }

    public const LOGO_BAWAAN = 'img/logo-kabupaten-banjar.png';

    public function kopHtml(): string
    {
        $s = Pengaturan::semua();
        $logo = self::logoDataUri();
        // Ukuran huruf mengikuti kop surat kantor (16 / 18 / 24 / 11 pt).
        $baris = '<p style="text-align: center; margin: 0; font-size: 16pt;"><strong>PEMERINTAH KABUPATEN '.e(mb_strtoupper($s['kabupaten'])).'</strong></p>'
            .'<p style="text-align: center; margin: 0; font-size: 18pt;"><strong>KECAMATAN '.e(mb_strtoupper($s['kecamatan'])).'</strong></p>'
            .'<p style="text-align: center; margin: 0; font-size: 24pt;"><strong>KELURAHAN '.e(mb_strtoupper($s['nama_kelurahan'])).'</strong></p>'
            .'<p style="text-align: center; margin: 0; font-size: 11pt;">'.e($s['alamat_kantor'])
            .($s['telepon'] ? ' Telp. '.e($s['telepon']) : '').($s['email'] ? ' Email: '.e($s['email']) : '').'</p>';

        if ($logo) {
            $isi = '<table style="width: 100%; border-collapse: collapse;" width="100%"><tr>'
                .'<td style="width: 15%; text-align: center; vertical-align: middle;" width="15%"><img src="'.$logo.'" width="76" height="100" style="width: 76px; height: 100px;" /></td>'
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
