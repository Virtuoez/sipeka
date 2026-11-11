<?php

namespace Database\Seeders;

use App\Models\Layanan;
use Illuminate\Database\Seeder;

/**
 * Jenis surat + template bawaan, mengikuti format surat Kelurahan Sekumpul
 * (contoh dari folder kantor "kasi pemerintahan", "kasi kesos", "kasi pm").
 *
 * - `php artisan db:seed --class=LayananSeeder` MENIMPA semua template (termasuk yang sudah diedit).
 * - Migration memakai pasang(false): hanya menambah layanan baru & mengatur bidang Kasi; template lama hanya
 *   diganti kalau belum pernah diedit admin (dicocokkan dengan TEMPLATE_LAMA).
 */
class LayananSeeder extends Seeder
{
    /** md5 template bawaan versi sebelumnya (sebelum format kantor). Template yang sama persis aman diganti. */
    private const TEMPLATE_LAMA = [
        'feb54ad4a2c89d780bd348bb4be5435e', // domisili
        'c0d9d16aaca0c903bb481a2bb614faa2', // sktm
        'cbb19843d7c7e200b6c7a754f1130629', // usaha
        '69d3f91761eef73e85b3f02a73d09006', // pengantar-ktp
        '8b383aeba1070b666150d618caab5589', // pengantar-kk
        '809a35e029cfc01c667043b9dfa5dc6e', // kelahiran
        'ad6be19cceb737b6d454a29c04a3cf9c', // kematian
        '60780936b917fcc7a218d12110a509fb', // pindah
        '48840724c6f1f584835e8133fcfb34f6', // belum-menikah
    ];

    private const JABATAN_KASI = [
        'pemerintahan' => 'Kasi Pemerintahan',
        'kesos' => 'Kasi Kesejahteraan Sosial',
        'pm' => 'Kasi Pemberdayaan Masyarakat',
    ];

    private const PENUTUP = 'Demikian Surat Keterangan ini diberikan untuk dapat dipergunakan sebagaimana mestinya.';

    // ---------------- Potongan HTML ----------------

    /** Tabel "Label : nilai". */
    private function tabel(array $baris, int $kiri = 0): string
    {
        $html = '<table style="width: 100%; border-collapse: collapse;'.($kiri ? ' margin-left: '.$kiri.'px;' : '').'" width="100%"><tbody>';
        foreach ($baris as $label => $nilai) {
            $html .= '<tr><td style="width: 33%;" width="33%">'.$label.'</td><td style="width: 3%;" width="3%">:</td><td style="width: 64%;" width="64%">'.$nilai.'</td></tr>';
        }

        return $html.'</tbody></table><p style="margin: 0;">&nbsp;</p>';
    }

    private function alamat(): string
    {
        return '{{alamat}} RT. {{rt}} RW. {{rw}} Kel. {{nama_kelurahan}} Kec. {{kecamatan}} Kab. {{kabupaten}}';
    }

    /** Identitas warga, urutan seperti surat kantor. $kolom = daftar kunci yang ditampilkan. */
    private function dataDiri(array $kolom = ['nama', 'jk', 'ttl', 'kawin', 'agama', 'wn', 'nik', 'kerja', 'alamat']): string
    {
        $semua = [
            'nama' => ['Nama', '<strong>{{nama}}</strong>'],
            'jk' => ['Jenis Kelamin', '{{jenis_kelamin}}'],
            'ttl' => ['Tempat, Tanggal Lahir', '{{ttl}}'],
            'kawin' => ['Status Perkawinan', '{{status_perkawinan}}'],
            'agama' => ['Agama', '{{agama}}'],
            'wn' => ['Kewarganegaraan', '{{kewarganegaraan}}'],
            'nik' => ['NIK', '{{nik}}'],
            'kk' => ['Nomor KK', '{{no_kk}}'],
            'kerja' => ['Pekerjaan', '{{pekerjaan}}'],
            'alamat' => ['Alamat', $this->alamat()],
        ];

        return $this->tabel(collect($kolom)->mapWithKeys(fn ($k) => [$semua[$k][0] => $semua[$k][1]])->all(), 30);
    }

    private function judul(string $judul, bool $nomor = true): string
    {
        return '<p>{{kop_surat}}</p>'
            .'<p style="text-align: center; margin: 0;"><strong><u>'.mb_strtoupper($judul).'</u></strong></p>'
            .($nomor ? '<p style="text-align: center; margin: 0;">Nomor : {{nomor_surat}}</p>' : '')
            .'<p>&nbsp;</p>';
    }

    private function p(string $isi, string $rata = 'justify'): string
    {
        return '<p style="text-align: '.$rata.';">'.$isi.'</p>';
    }

    private function pembukaLurah(string $tambahan = 'menerangkan bahwa :'): string
    {
        return $this->p('Yang bertanda tangan di bawah ini {{jabatan_ttd}} Kecamatan {{kecamatan}} Kabupaten {{kabupaten}} '.$tambahan);
    }

    /** "Yang bertanda tangan di bawah ini atas nama Lurah, Kepala Seksi ..." + tabel nama/NIP/jabatan Kasi. */
    private function pembukaKasi(string $bidang): string
    {
        return $this->p('Yang bertanda tangan di bawah ini atas nama Lurah {{nama_kelurahan}}, '.self::JABATAN_KASI[$bidang].' Kelurahan {{nama_kelurahan}} Kecamatan {{kecamatan}} Kabupaten {{kabupaten}} :')
            .$this->tabel(['Nama' => '<strong>{{nama_kasi_'.$bidang.'}}</strong>', 'NIP' => '{{nip_kasi_'.$bidang.'}}', 'Jabatan' => self::JABATAN_KASI[$bidang]], 30);
    }

    private function keperluan(string $pengantar = 'Surat Keterangan ini diberikan untuk keperluan :'): string
    {
        return $this->p($pengantar).$this->p('<strong>&ldquo; {{keperluan}} &rdquo;</strong>', 'center');
    }

    /** Kolom tanda tangan di kanan. */
    private function kolomKanan(string $isi): string
    {
        return '<p>&nbsp;</p><table style="width: 100%; border-collapse: collapse;" width="100%"><tbody><tr>'
            .'<td style="width: 50%;" width="50%">&nbsp;</td>'
            .'<td style="width: 50%;" width="50%">'.$isi.'</td>'
            .'</tr></tbody></table>';
    }

    private function dikeluarkan(): string
    {
        return '<p style="margin: 0;">Dikeluarkan di : {{kecamatan}}</p>'
            .'<p style="margin: 0;">Pada Tanggal &nbsp; : {{tanggal_surat}}</p><p>&nbsp;</p>';
    }

    private function namaTtd(string $nama, string $nip): string
    {
        return '<p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p>'
            .'<p style="text-align: center; margin: 0;"><strong><u>'.$nama.'</u></strong></p>'
            .'<p style="text-align: center; margin: 0;">NIP. '.$nip.'</p>';
    }

    private function ttdLurah(): string
    {
        return $this->kolomKanan($this->dikeluarkan()
            .'<p style="text-align: center; margin: 0;"><strong>{{jabatan_ttd}},</strong></p>'
            .$this->namaTtd('{{nama_lurah}}', '{{nip_lurah}}'));
    }

    private function ttdKasi(string $bidang): string
    {
        return $this->kolomKanan($this->dikeluarkan()
            .'<p style="text-align: center; margin: 0;">a.n. Lurah {{nama_kelurahan}}</p>'
            .'<p style="text-align: center; margin: 0;"><strong>'.self::JABATAN_KASI[$bidang].',</strong></p>'
            .$this->namaTtd('{{nama_kasi_'.$bidang.'}}', '{{nip_kasi_'.$bidang.'}}'));
    }

    /** Dua kolom tanda tangan berdampingan. */
    private function duaKolom(string $kiri, string $kanan): string
    {
        return '<p>&nbsp;</p><table style="width: 100%; border-collapse: collapse;" width="100%"><tbody><tr>'
            .'<td style="width: 50%; vertical-align: top;" width="50%">'.$kiri.'</td>'
            .'<td style="width: 50%; vertical-align: top;" width="50%">'.$kanan.'</td>'
            .'</tr></tbody></table>';
    }

    private function tengah(string $teks, bool $tebal = false): string
    {
        return '<p style="text-align: center; margin: 0;">'.($tebal ? '<strong>'.$teks.'</strong>' : $teks).'</p>';
    }

    // ---------------- Daftar jenis surat ----------------

    private function daftar(): array
    {
        $rt = 'Surat Pengantar RT/RW (opsional)';

        return [
            // ===== KASI PEMERINTAHAN =====
            [
                'slug' => 'domisili', 'nama' => 'Surat Keterangan Domisili', 'kode_surat' => '470', 'folder' => '01 - Keterangan Domisili', 'ikon' => 'house-door', 'bidang' => 'pemerintahan',
                'deskripsi' => 'Keterangan bahwa warga benar berdomisili di Kelurahan Sekumpul.',
                'persyaratan' => "KTP\nKartu Keluarga\n$rt",
                'isian' => "Keperluan | paragraf\nLama Tinggal | teks (opsional)",
                'html' => $this->judul('Surat Keterangan Domisili').$this->pembukaLurah().$this->dataDiri()
                    .$this->p('Yang bersangkutan memang benar Penduduk yang berdomisili di Kelurahan {{nama_kelurahan}} Kecamatan {{kecamatan}} Kabupaten {{kabupaten}}.')
                    .$this->keperluan().$this->p(self::PENUTUP).$this->ttdLurah(),
            ],
            [
                'slug' => 'pengantar-ktp', 'nama' => 'Surat Pengantar KTP-el', 'kode_surat' => '470', 'folder' => '04 - Pengantar KTP', 'ikon' => 'person-vcard', 'bidang' => 'pemerintahan',
                'deskripsi' => 'Pengantar untuk pembuatan, perubahan, atau cetak ulang KTP elektronik di Disdukcapil.',
                'persyaratan' => "Kartu Keluarga\nKTP Lama (opsional)\nSurat Kehilangan dari Kepolisian (opsional)",
                'isian' => "Jenis Permohonan KTP | teks\nAlasan | paragraf (opsional)",
                'html' => $this->judul('Surat Pengantar').$this->pembukaLurah().$this->dataDiri()
                    .$this->p('Yang bersangkutan adalah benar Penduduk Kelurahan {{nama_kelurahan}} dan bermaksud mengurus <strong>KTP Elektronik ({{jenis_permohonan_ktp}})</strong> pada Dinas Kependudukan dan Pencatatan Sipil Kabupaten {{kabupaten}}. {{alasan}}')
                    .$this->p('Demikian Surat Pengantar ini diberikan untuk dapat dipergunakan sebagaimana mestinya.').$this->ttdLurah(),
            ],
            [
                'slug' => 'pengantar-kk', 'nama' => 'Surat Pengantar Kartu Keluarga', 'kode_surat' => '470', 'folder' => '05 - Pengantar KK', 'ikon' => 'people', 'bidang' => 'pemerintahan',
                'deskripsi' => 'Pengantar untuk pembuatan KK baru, penambahan anggota, atau perubahan data KK.',
                'persyaratan' => "KTP\nKartu Keluarga Lama (opsional)\nBuku Nikah / Akta Perkawinan (opsional)\nAkta Kelahiran Anggota Baru (opsional)\n$rt",
                'isian' => "Jenis Perubahan KK | teks\nRincian Perubahan | paragraf",
                'html' => $this->judul('Surat Pengantar').$this->pembukaLurah().$this->dataDiri()
                    .$this->p('Yang bersangkutan adalah benar Penduduk Kelurahan {{nama_kelurahan}} dan bermaksud mengurus <strong>Kartu Keluarga ({{jenis_perubahan_kk}})</strong> dengan rincian: {{rincian_perubahan}}.')
                    .$this->p('Demikian Surat Pengantar ini diberikan untuk dapat dipergunakan sebagaimana mestinya.').$this->ttdLurah(),
            ],
            [
                'slug' => 'kelahiran', 'nama' => 'Surat Keterangan Kelahiran', 'kode_surat' => '474.1', 'folder' => '06 - Keterangan Kelahiran', 'ikon' => 'balloon-heart', 'bidang' => 'pemerintahan',
                'deskripsi' => 'Keterangan kelahiran anak, untuk pengurusan Akta Kelahiran. Data diri diisi data pelapor (orang tua).',
                'persyaratan' => "KTP Pelapor\nKartu Keluarga\nSurat Keterangan Lahir dari Bidan/Rumah Sakit\nBuku Nikah Orang Tua",
                'isian' => "Nama Anak | teks\nJenis Kelamin Anak | teks\nTempat Lahir Anak | teks\nTanggal Lahir Anak | tanggal\nAnak Ke | angka\nNama Ayah | teks\nNama Ibu | teks",
                'html' => $this->judul('Surat Keterangan Kelahiran').$this->pembukaLurah('menerangkan dengan sebenarnya bahwa :')
                    .$this->tabel(['Nama' => '<strong>{{nama_anak}}</strong>', 'Alamat Tinggal' => $this->alamat()], 30)
                    .$this->p('Adalah kelahiran pada :')
                    .$this->tabel(['Tempat Lahir' => '{{tempat_lahir_anak}}', 'Tgl, Bln, Thn' => '{{tanggal_lahir_anak}}', 'Jenis Kelamin' => '{{jenis_kelamin_anak}}', 'Anak Ke' => '{{anak_ke}}'], 30)
                    .$this->p('Dari pasangan suami isteri, yaitu :')
                    .$this->tabel(['Nama Ayah' => '{{nama_ayah}}', 'Nama Ibu' => '{{nama_ibu}}'], 30)
                    .$this->p('Surat keterangan kelahiran ini dibuat setelah diadakan penelitian terhadap bukti-bukti yang ada atas kelahiran yang bersangkutan, dan yang bersangkutan belum pernah dicatat dalam Pencatatan Sipil (Akta Kelahiran).')
                    .$this->p('Demikian surat keterangan ini dibuat sebagai salah satu persyaratan untuk dicatat dalam Pencatatan Sipil (Akta Kelahiran).')
                    .$this->ttdLurah(),
            ],
            [
                'slug' => 'kematian', 'nama' => 'Surat Keterangan Kematian', 'kode_surat' => '472', 'folder' => '07 - Keterangan Kematian', 'ikon' => 'flower1', 'bidang' => 'pemerintahan',
                'deskripsi' => 'Keterangan meninggal dunia, untuk pengurusan Akta Kematian. Data diri diisi data almarhum/almarhumah.',
                'persyaratan' => "KTP Almarhum/Almarhumah\nKartu Keluarga\nKTP Pelapor\nSurat Keterangan Kematian dari RS/Puskesmas (opsional)",
                'isian' => "Tanggal Meninggal | tanggal\nPukul | teks (opsional)\nTempat Meninggal | teks\nSebab Meninggal | teks\nNama Pelapor | teks\nNIK Pelapor | teks (opsional)\nHubungan Pelapor | teks\nAlamat Pelapor | paragraf (opsional)\nNo HP Pelapor | teks (opsional)",
                'html' => $this->judul('Surat Keterangan Kematian').$this->p('Yang bertanda tangan di bawah ini menerangkan bahwa :')
                    .$this->tabel(['Nama' => '<strong>{{nama}}</strong>', 'Tempat, Tgl. Lahir / Umur' => '{{ttl}} / {{umur}} Tahun', 'Jenis Kelamin' => '{{jenis_kelamin}}', 'Agama' => '{{agama}}', 'NIK' => '{{nik}}', 'Alamat Terakhir' => $this->alamat()], 30)
                    .$this->p('Telah meninggal dunia pada :')
                    .$this->tabel(['Tanggal' => '{{tanggal_meninggal}}', 'Pukul' => '{{pukul}}', 'Tempat Kematian' => '{{tempat_meninggal}}', 'Disebabkan Karena' => '{{sebab_meninggal}}'], 30)
                    .$this->p('Surat Keterangan ini dibuat atas dasar yang sebenarnya :')
                    .$this->tabel(['Nama yang Melapor' => '{{nama_pelapor}}', 'NIK' => '{{nik_pelapor}}', 'Hubungan dengan yang Meninggal' => '{{hubungan_pelapor}}', 'Alamat Pelapor' => '{{alamat_pelapor}}', 'No. HP' => '{{no_hp_pelapor}}'], 30)
                    .$this->ttdLurah(),
            ],
            [
                'slug' => 'pindah', 'nama' => 'Surat Keterangan Pindah', 'kode_surat' => '476', 'folder' => '08 - Keterangan Pindah', 'ikon' => 'truck', 'bidang' => 'pemerintahan',
                'deskripsi' => 'Pengantar pindah keluar dari Kelurahan Sekumpul.',
                'persyaratan' => "KTP\nKartu Keluarga\n$rt",
                'isian' => "Nama Kepala Keluarga | teks\nAlamat Tujuan | paragraf\nRT RW Tujuan | teks (opsional)\nKelurahan Tujuan | teks\nKecamatan Tujuan | teks\nKabupaten Kota Tujuan | teks\nProvinsi Tujuan | teks\nAlasan Pindah | teks\nJumlah Anggota yang Ikut Pindah | angka",
                'html' => $this->judul('Surat Keterangan Pindah').$this->p('Yang bertanda tangan di bawah ini, menerangkan Permohonan Pindah Penduduk WNI dengan data sebagai berikut :')
                    .$this->tabel(['NIK' => '{{nik}}', 'Nama Lengkap' => '<strong>{{nama}}</strong>', 'Nomor KK' => '{{no_kk}}', 'Nama Kepala Keluarga' => '{{nama_kepala_keluarga}}', 'Alamat Sekarang' => $this->alamat()], 30)
                    .$this->p('Alamat Tujuan Pindah :')
                    .$this->tabel(['Alamat' => '{{alamat_tujuan}}', 'RT/RW' => '{{rt_rw_tujuan}}', 'Desa/Kelurahan' => '{{kelurahan_tujuan}}', 'Kecamatan' => '{{kecamatan_tujuan}}', 'Kabupaten/Kota' => '{{kabupaten_kota_tujuan}}', 'Provinsi' => '{{provinsi_tujuan}}', 'Alasan Pindah' => '{{alasan_pindah}}', 'Jumlah Keluarga yang Pindah' => '{{jumlah_anggota_yang_ikut_pindah}} orang'], 30)
                    .$this->p('Adapun permohonan pindah Penduduk WNI yang bersangkutan sebagaimana terlampir.')
                    .$this->p('Demikian Surat Pengantar Pindah ini dibuat agar dapat digunakan sebagaimana mestinya.')
                    .$this->ttdKasi('pemerintahan'),
            ],
            [
                'slug' => 'orang-yang-sama', 'nama' => 'Surat Keterangan Orang Yang Sama', 'kode_surat' => '470', 'folder' => '10 - Keterangan Orang Yang Sama', 'ikon' => 'person-badge', 'bidang' => 'pemerintahan',
                'deskripsi' => 'Keterangan bahwa nama yang berbeda di KTP/KK/SHM/akta adalah orang yang sama (kesalahan penulisan nama).',
                'persyaratan' => "KTP\nKartu Keluarga\nDokumen yang Namanya Berbeda (SHM/Akta/Buku Nikah/Ijazah)\n$rt",
                'isian' => "Dasar Dokumen | paragraf\nNama pada Dokumen Lain | paragraf",
                'html' => $this->judul('Surat Keterangan')
                    .$this->p('Yang bertanda tangan di bawah ini atas nama Lurah {{nama_kelurahan}} '.self::JABATAN_KASI['pemerintahan'].' Kelurahan {{nama_kelurahan}} Kecamatan {{kecamatan}} Kabupaten {{kabupaten}} menerangkan dengan dasar {{dasar_dokumen}}, dengan ini menerangkan bahwa :')
                    .$this->dataDiri(['nama', 'ttl', 'jk', 'kawin', 'agama', 'wn', 'kerja', 'alamat'])
                    .$this->p('adalah <strong>orang yang sama</strong> dengan {{nama_pada_dokumen_lain}}.')
                    .$this->p('Hal tersebut terjadi karena adanya kesalahan/kekeliruan redaksional pada penulisan nama yang bersangkutan.')
                    .$this->p(self::PENUTUP).$this->ttdKasi('pemerintahan'),
            ],
            [
                'slug' => 'pemekaran', 'nama' => 'Surat Keterangan Pemekaran', 'kode_surat' => '593', 'folder' => '11 - Keterangan Pemekaran', 'ikon' => 'signpost-split', 'bidang' => 'pemerintahan',
                'deskripsi' => 'Keterangan bahwa tanah ber-SHM kini masuk wilayah Kelurahan Sekumpul (hasil pemekaran). Data diri diisi data pelapor.',
                'persyaratan' => "KTP Pelapor\nFotokopi Sertifikat Hak Milik (SHM)\n$rt",
                'isian' => "Nomor SHM | teks\nTanggal SHM | tanggal\nAtas Nama SHM | teks\nRT Lokasi Tanah | teks\nRW Lokasi Tanah | teks\nKelurahan Asal | teks",
                'html' => $this->judul('Surat Keterangan').$this->pembukaKasi('pemerintahan')
                    .$this->p('Sehubungan dengan permohonan dari pelapor atas nama <strong>{{nama}}</strong> dengan NIK e-KTP {{nik}} dengan ini kami menerangkan bahwa sebidang tanah dengan Sertifikat Hak Milik Nomor : <strong>{{nomor_shm}}</strong> yang dikeluarkan tanggal {{tanggal_shm}} an. {{atas_nama_shm}} yang diterbitkan oleh Kantor Pertanahan Kabupaten {{kabupaten}}, berdasarkan PERDA No. 7 Tahun 2008 sekarang berada di wilayah RT. {{rt_lokasi_tanah}} RW. {{rw_lokasi_tanah}} Kelurahan {{nama_kelurahan}} Kecamatan {{kecamatan}} Kabupaten {{kabupaten}} yang merupakan hasil pemekaran dari Kelurahan {{kelurahan_asal}}.')
                    .$this->p(self::PENUTUP).$this->ttdKasi('pemerintahan'),
            ],
            [
                'slug' => 'skbb', 'nama' => 'Surat Keterangan Berkelakuan Baik (SKBB)', 'kode_surat' => '300', 'folder' => '12 - Keterangan Berkelakuan Baik', 'ikon' => 'patch-check', 'bidang' => 'pemerintahan',
                'deskripsi' => 'Keterangan berkelakuan baik dan tidak terlibat tindak pidana (sekolah, pondok, kerja).',
                'persyaratan' => "KTP\nKartu Keluarga\n$rt",
                'isian' => 'Keperluan | paragraf',
                'html' => $this->judul('Surat Keterangan Berkelakuan Baik')
                    .$this->p('Yang bertanda tangan di bawah ini '.self::JABATAN_KASI['pemerintahan'].' Kelurahan {{nama_kelurahan}} Kecamatan {{kecamatan}} Kabupaten {{kabupaten}} menerangkan bahwa :')
                    .$this->dataDiri(['nama', 'ttl', 'jk', 'kawin', 'agama', 'wn', 'nik', 'alamat'])
                    .$this->p('Yang bersangkutan memang benar Penduduk Kelurahan {{nama_kelurahan}} Kecamatan {{kecamatan}} Kabupaten {{kabupaten}} dan mempunyai <strong>Kelakuan yang Baik</strong> serta tidak terlibat Tindak Pidana.')
                    .$this->keperluan('Surat Keterangan ini diberikan guna keperluan :').$this->p(self::PENUTUP).$this->ttdKasi('pemerintahan'),
            ],
            [
                'slug' => 'harga-tanah', 'nama' => 'Surat Keterangan Harga Tanah', 'kode_surat' => '593', 'folder' => '13 - Keterangan Harga Tanah', 'ikon' => 'cash-coin', 'bidang' => 'pemerintahan',
                'deskripsi' => 'Taksiran harga tanah per meter di wilayah RT tertentu (mis. untuk keringanan BPHTB). Data diri diisi data pelapor.',
                'persyaratan' => "KTP Pelapor\nFotokopi Sertifikat / Bukti Kepemilikan Tanah (opsional)\n$rt",
                'isian' => "RT Lokasi Tanah | teks\nRW Lokasi Tanah | teks\nHarga Terendah per Meter | teks\nHarga Tertinggi per Meter | teks\nKeperluan | paragraf",
                'html' => $this->judul('Surat Keterangan Harga Tanah').$this->pembukaKasi('pemerintahan')
                    .$this->p('Berdasarkan permintaan pelapor an. <strong>{{nama}}</strong> yang beralamat di {{alamat}} RT. {{rt}} RW. {{rw}} dan keterangan dari Ketua RT. {{rt_lokasi_tanah}}/{{rw_lokasi_tanah}}, dengan ini menerangkan bahwa tanah di wilayah RT. {{rt_lokasi_tanah}} RW. {{rw_lokasi_tanah}} Kelurahan {{nama_kelurahan}} Kecamatan {{kecamatan}} memiliki taksiran harga tanah &plusmn; <strong>{{harga_terendah_per_meter}} s/d {{harga_tertinggi_per_meter}}</strong> per meter.')
                    .$this->p('Surat Keterangan Harga Tanah ini dibuat sebagai bahan persyaratan untuk {{keperluan}}.')
                    .$this->p('Demikian Surat Keterangan ini dibuat sebagai bahan selanjutnya, dan apabila di kemudian hari terdapat kesalahan akan dilakukan perbaikan sebagaimana mestinya.')
                    .$this->ttdKasi('pemerintahan'),
            ],
            [
                'slug' => 'hibah', 'nama' => 'Surat Pernyataan Hibah', 'kode_surat' => '593', 'folder' => '14 - Pernyataan Hibah', 'ikon' => 'gift', 'bidang' => 'pemerintahan',
                'deskripsi' => 'Pernyataan menghibahkan sebidang tanah. Data diri diisi data pihak yang menghibahkan.',
                'persyaratan' => "KTP Pemberi Hibah\nKTP Penerima Hibah\nKartu Keluarga\nFotokopi Sertifikat / Segel Tanah\nKTP Saksi (opsional)\n$rt",
                'isian' => "Letak Tanah | paragraf\nNama Penerima Hibah | teks\nTempat Lahir Penerima | teks\nTanggal Lahir Penerima | tanggal\nNIK Penerima | teks\nJenis Kelamin Penerima | teks\nPekerjaan Penerima | teks\nAlamat Penerima | paragraf\nBatas Utara | teks (opsional)\nBatas Timur | teks (opsional)\nBatas Selatan | teks (opsional)\nBatas Barat | teks (opsional)\nSaksi 1 | teks\nSaksi 2 | teks (opsional)\nSaksi 3 | teks (opsional)",
                'html' => $this->judul('Surat Pernyataan Hibah', false).$this->p('Yang bertanda tangan di bawah ini :')
                    .$this->dataDiri(['nama', 'ttl', 'nik', 'jk', 'kerja', 'alamat'])
                    .$this->p('(Selaku Pihak yang Menghibahkan)', 'center')
                    .$this->p('dengan ini menyatakan dengan sebenarnya dan dalam keadaan sadar bahwa Saya <strong>TELAH MENGHIBAHKAN</strong> sebidang tanah yang terletak di {{letak_tanah}}, kepada :')
                    .$this->tabel(['Nama' => '<strong>{{nama_penerima_hibah}}</strong>', 'Tempat, Tanggal Lahir' => '{{tempat_lahir_penerima}}, {{tanggal_lahir_penerima}}', 'NIK' => '{{nik_penerima}}', 'Jenis Kelamin' => '{{jenis_kelamin_penerima}}', 'Pekerjaan' => '{{pekerjaan_penerima}}', 'Alamat' => '{{alamat_penerima}}'], 30)
                    .$this->p('(Selaku Pihak Penerima Hibah)', 'center')
                    .$this->p('adapun batas tanah tersebut sebagai berikut :')
                    .$this->tabel(['Sebelah Utara' => '{{batas_utara}}', 'Sebelah Timur' => '{{batas_timur}}', 'Sebelah Selatan' => '{{batas_selatan}}', 'Sebelah Barat' => '{{batas_barat}}'], 30)
                    .$this->p('mengenai asal tanah di atas adalah :')
                    .'<ol><li>Tanah tersebut berasal dari milik Saya sendiri.</li><li>Tanah tersebut tidak dalam sengketa, baik batas maupun kepemilikannya.</li><li>Tanah tersebut tidak dijadikan sesuatu jaminan piutang.</li><li>Setelah tanah dihibahkan maka Saya tidak berhak lagi atas tanah tersebut, demikian pula ahli waris Saya.</li><li>Apabila di kemudian hari pernyataan ini tidak benar, maka Saya bersedia dituntut sesuai dengan Perundang-undangan yang berlaku.</li></ol>'
                    .$this->p('Demikian Surat Pernyataan Hibah ini Saya buat dengan sebenarnya untuk diketahui dan dipergunakan sebagaimana mestinya.')
                    .$this->duaKolom(
                        $this->tengah('&nbsp;').$this->tengah('Penerima Hibah,').'<p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p>'.$this->tengah('<u>{{nama_penerima_hibah}}</u>', true),
                        $this->tengah('{{kecamatan}}, {{tanggal_surat}}').$this->tengah('Yang Menghibahkan,').'<p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p>'.$this->tengah('<u>{{nama}}</u>', true)
                    )
                    .'<p>&nbsp;</p><p>Saksi-saksi :</p>'
                    .$this->tabel(['1. {{saksi_1}}' => '( ............................ )', '2. {{saksi_2}}' => '( ............................ )', '3. {{saksi_3}}' => '( ............................ )'], 30)
                    .'<p>&nbsp;</p>'.$this->tengah('Mengetahui :').'<p style="text-align: center; margin: 0;">Nomor : {{nomor_surat}}</p>'
                    .$this->duaKolom(
                        $this->tengah('Camat {{kecamatan}},', true).'<p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p>'.$this->tengah('( ................................ )'),
                        $this->tengah('{{jabatan_ttd}},', true).$this->namaTtd('{{nama_lurah}}', '{{nip_lurah}}')
                    ),
            ],

            // ===== KASI KESOS =====
            [
                'slug' => 'sktm', 'nama' => 'Surat Keterangan Tidak Mampu', 'kode_surat' => '400.9', 'folder' => '02 - SKTM', 'ikon' => 'heart', 'bidang' => 'kesos',
                'deskripsi' => 'Untuk keringanan biaya sekolah, berobat, atau bantuan sosial (KIP, KKS, BPJS).',
                'persyaratan' => "KTP\nKartu Keluarga\nSurat Pernyataan Tidak Mampu (opsional)\n$rt",
                'isian' => 'Keperluan | paragraf',
                'html' => $this->judul('Surat Keterangan Tidak Mampu').$this->pembukaLurah()
                    .$this->dataDiri(['nama', 'jk', 'ttl', 'nik', 'kk', 'kerja', 'agama', 'wn', 'alamat'])
                    .$this->p('Berdasarkan Surat Keterangan dari Ketua RT setempat dan Surat Pernyataan yang bersangkutan, bahwa yang bersangkutan adalah benar Penduduk Kelurahan {{nama_kelurahan}} dan ekonomi keluarganya tergolong <strong>tidak mampu</strong>.')
                    .$this->keperluan().$this->p('Demikian Surat Keterangan ini diberikan untuk dapat diketahui dan dipergunakan sebagaimana mestinya.').$this->ttdLurah(),
            ],
            [
                'slug' => 'belum-menikah', 'nama' => 'Surat Keterangan Belum Menikah', 'kode_surat' => '400.9', 'folder' => '09 - Keterangan Belum Menikah', 'ikon' => 'person-check', 'bidang' => 'kesos',
                'deskripsi' => 'Keterangan berstatus belum pernah menikah.',
                'persyaratan' => "KTP\nKartu Keluarga\n$rt",
                'isian' => 'Keperluan | paragraf',
                'html' => $this->judul('Surat Keterangan Belum Menikah').$this->pembukaLurah()
                    .$this->dataDiri(['nama', 'jk', 'ttl', 'agama', 'wn', 'nik', 'kk', 'kerja', 'alamat'])
                    .$this->p('Berdasarkan surat keterangan dari Ketua RT setempat, yang bersangkutan adalah benar Penduduk Kelurahan {{nama_kelurahan}} Kecamatan {{kecamatan}} dan sampai saat ini benar <strong>Belum Menikah</strong>.')
                    .$this->keperluan().$this->p(self::PENUTUP).$this->ttdLurah(),
            ],
            [
                'slug' => 'belum-bekerja', 'nama' => 'Surat Keterangan Belum Bekerja', 'kode_surat' => '400.9', 'folder' => '15 - Keterangan Belum Bekerja', 'ikon' => 'briefcase', 'bidang' => 'kesos',
                'deskripsi' => 'Keterangan belum/tidak memiliki pekerjaan (mis. untuk KPR, beasiswa, melamar kerja).',
                'persyaratan' => "KTP\nKartu Keluarga\n$rt",
                'isian' => 'Keperluan | paragraf',
                'html' => $this->judul('Surat Keterangan Belum Bekerja').$this->pembukaLurah().$this->dataDiri()
                    .$this->p('Yang bersangkutan memang benar Penduduk Kelurahan {{nama_kelurahan}} Kecamatan {{kecamatan}} dan benar selama ini yang bersangkutan <strong>Belum / Tidak Memiliki Pekerjaan</strong>.')
                    .$this->keperluan('Surat Keterangan ini diperlukan untuk keperluan :')
                    .$this->p('Demikian Surat Keterangan ini diberikan untuk dapat diketahui dan dipergunakan sebagaimana mestinya.').$this->ttdLurah(),
            ],
            [
                'slug' => 'duda-janda', 'nama' => 'Surat Keterangan Kedudaan / Kejandaan', 'kode_surat' => '400.9', 'folder' => '16 - Keterangan Duda-Janda', 'ikon' => 'person-x', 'bidang' => 'kesos',
                'deskripsi' => 'Keterangan berstatus duda atau janda (cerai hidup/cerai mati) dan belum menikah lagi.',
                'persyaratan' => "KTP\nKartu Keluarga\nAkta Cerai / Akta Kematian Pasangan\n$rt",
                'isian' => "Status Duda atau Janda | teks\nKeterangan Perceraian | teks\nKeperluan | paragraf",
                'html' => $this->judul('Surat Keterangan').$this->pembukaLurah()
                    .$this->dataDiri(['nama', 'jk', 'ttl', 'agama', 'wn', 'nik', 'kk', 'kerja', 'alamat'])
                    .$this->p('Berdasarkan surat keterangan dari Ketua RT setempat, yang bersangkutan adalah benar Penduduk Kelurahan {{nama_kelurahan}} Kecamatan {{kecamatan}} dan sampai saat ini benar berstatus <strong>{{status_duda_atau_janda}}</strong> ({{keterangan_perceraian}}) serta belum menikah lagi.')
                    .$this->keperluan().$this->p(self::PENUTUP).$this->ttdLurah(),
            ],
            [
                'slug' => 'tidak-memiliki-rumah', 'nama' => 'Surat Pernyataan Tidak Memiliki Rumah', 'kode_surat' => '400.10', 'folder' => '17 - Pernyataan Tidak Memiliki Rumah', 'ikon' => 'house-dash', 'bidang' => 'kesos',
                'deskripsi' => 'Pernyataan warga tidak memiliki rumah sendiri, diketahui Lurah (mis. untuk KPR subsidi).',
                'persyaratan' => "KTP\nKartu Keluarga\nMaterai 10.000\n$rt",
                'isian' => '',
                'html' => $this->judul('Surat Pernyataan Tidak Memiliki Rumah', false).$this->p('Saya yang bertanda tangan di bawah ini :')
                    .$this->dataDiri(['nama', 'ttl', 'jk', 'wn', 'nik', 'alamat'])
                    .$this->p('Menyatakan bahwa sampai dengan surat pernyataan ini dibuat, benar saya <strong>tidak memiliki hak kepemilikan atas rumah</strong> (tidak memiliki rumah sendiri).')
                    .$this->p('Demikian Surat Pernyataan ini saya buat dengan sebenar-benarnya agar dapat digunakan sebagaimana mestinya.')
                    .$this->duaKolom(
                        '<p style="margin: 0;">Tanggal : {{tanggal_surat}}</p><p style="margin: 0;">Nomor &nbsp;&nbsp;: {{nomor_surat}}</p><p>&nbsp;</p>'
                            .$this->tengah('Mengetahui').$this->tengah('{{jabatan_ttd}},', true).$this->namaTtd('{{nama_lurah}}', '{{nip_lurah}}'),
                        $this->tengah('Yang Membuat Pernyataan,').'<p>&nbsp;</p>'.$this->tengah('<small>Materai 10.000</small>').'<p>&nbsp;</p><p>&nbsp;</p>'.$this->tengah('<u>{{nama}}</u>', true)
                    ),
            ],

            // ===== KASI PM =====
            [
                'slug' => 'usaha', 'nama' => 'Surat Keterangan Usaha', 'kode_surat' => '500', 'folder' => '03 - Keterangan Usaha', 'ikon' => 'shop', 'bidang' => 'pm',
                'deskripsi' => 'Keterangan memiliki usaha, biasanya untuk pengajuan KUR/pinjaman bank.',
                'persyaratan' => "KTP\nKartu Keluarga\nFoto Tempat Usaha (opsional)\n$rt",
                'isian' => "Nama Usaha | teks\nJenis Usaha | teks\nAlamat Usaha | paragraf\nMulai Usaha Sejak | teks\nKeperluan | paragraf",
                'html' => $this->judul('Surat Keterangan Usaha').$this->pembukaKasi('pm').$this->p('Dengan ini menerangkan bahwa :').$this->dataDiri()
                    .$this->p('Berdasarkan Surat Pengantar Ketua RT setempat, yang bersangkutan benar memiliki usaha sebagai berikut :')
                    .$this->tabel(['Nama Usaha' => '<strong>{{nama_usaha}}</strong>', 'Jenis Usaha' => '{{jenis_usaha}}', 'Alamat Usaha' => '{{alamat_usaha}}', 'Berdiri Sejak' => '{{mulai_usaha_sejak}}'], 30)
                    .$this->keperluan().$this->p(self::PENUTUP).$this->ttdKasi('pm'),
            ],
            [
                'slug' => 'domisili-usaha', 'nama' => 'Surat Keterangan Domisili Usaha', 'kode_surat' => '400.10', 'folder' => '18 - Keterangan Domisili Usaha', 'ikon' => 'shop-window', 'bidang' => 'pm',
                'deskripsi' => 'Keterangan tempat/alamat usaha berada di Kelurahan Sekumpul (mis. pangkalan LPG, toko).',
                'persyaratan' => "KTP\nKartu Keluarga\nFoto Tempat Usaha (opsional)\n$rt",
                'isian' => "Jenis Usaha | teks\nAlamat Usaha | paragraf",
                'html' => $this->judul('Surat Keterangan Domisili Usaha').$this->pembukaKasi('pm').$this->p('Dengan ini menerangkan bahwa :')
                    .$this->tabel(['Nama' => '<strong>{{nama}}</strong>', 'NIK' => '{{nik}}', 'Alamat' => $this->alamat()], 30)
                    .$this->p('Berdasarkan Surat Pengantar Ketua RT setempat, bahwa yang bersangkutan mempunyai usaha <strong>{{jenis_usaha}}</strong> yang beralamat di {{alamat_usaha}} Kelurahan {{nama_kelurahan}} Kecamatan {{kecamatan}} Kabupaten {{kabupaten}}.')
                    .$this->p(self::PENUTUP).$this->ttdKasi('pm'),
            ],
            [
                'slug' => 'penghasilan', 'nama' => 'Surat Keterangan Penghasilan', 'kode_surat' => '400.10', 'folder' => '19 - Keterangan Penghasilan', 'ikon' => 'wallet2', 'bidang' => 'pm',
                'deskripsi' => 'Keterangan jenis usaha/pekerjaan dan penghasilan rata-rata (mis. untuk KIP, beasiswa).',
                'persyaratan' => "KTP\nKartu Keluarga\n$rt",
                'isian' => "Jenis Usaha atau Pekerjaan | teks\nLama Usaha atau Kerja | teks\nPenghasilan per Bulan | teks\nTempat Usaha atau Kerja | paragraf\nKeperluan | paragraf",
                'html' => $this->judul('Surat Keterangan Penghasilan').$this->pembukaLurah()
                    .$this->dataDiri(['nama', 'ttl', 'jk', 'wn', 'nik', 'alamat'])
                    .$this->p('Berdasarkan keterangan Pemohon dan pengantar Ketua RT setempat bahwa yang bersangkutan memiliki usaha / pekerjaan dan penghasilan sebagai berikut :')
                    .$this->tabel(['Jenis Usaha / Pekerjaan' => '<strong>{{jenis_usaha_atau_pekerjaan}}</strong>', 'Lama Usaha / Kerja' => '{{lama_usaha_atau_kerja}}', 'Penghasilan Rata-rata' => '{{penghasilan_per_bulan}} / bulan', 'Tempat Usaha / Kerja' => '{{tempat_usaha_atau_kerja}}', 'Keperluan' => '{{keperluan}}'], 30)
                    .$this->p('Demikian Surat Keterangan Penghasilan ini kami buat dengan sebenar-benarnya untuk digunakan sebagaimana mestinya.')
                    .$this->duaKolom(
                        $this->tengah('Mengetahui,').$this->tengah('a.n. Lurah {{nama_kelurahan}}').$this->tengah(self::JABATAN_KASI['pm'].',', true).$this->namaTtd('{{nama_kasi_pm}}', '{{nip_kasi_pm}}'),
                        $this->tengah('{{kecamatan}}, {{tanggal_surat}}').$this->tengah('&nbsp;').$this->tengah('Pemohon,').'<p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p>'.$this->tengah('<u>{{nama}}</u>', true)
                    ),
            ],
        ];
    }

    public function run(): void
    {
        $this->pasang(timpaTemplate: true);
    }

    /**
     * Tambah/perbarui jenis surat.
     * $timpaTemplate = false: template yang sudah diedit admin dibiarkan, hanya bidang Kasi & urutan yang diatur.
     */
    public function pasang(bool $timpaTemplate): void
    {
        foreach ($this->daftar() as $i => $l) {
            $nilai = [
                'nama' => $l['nama'],
                'kode_surat' => $l['kode_surat'],
                'ikon' => $l['ikon'],
                'deskripsi' => $l['deskripsi'],
                'persyaratan' => Layanan::parsePersyaratan($l['persyaratan']),
                'isian' => Layanan::parseIsian($l['isian']),
                'template_html' => $l['html'],
            ];
            $atur = ['bidang' => $l['bidang'], 'urutan' => $i + 1];

            $ada = Layanan::where('slug', $l['slug'])->first();
            if (! $ada) {
                Layanan::create(['slug' => $l['slug'], 'folder' => $l['folder'], 'aktif' => true] + $nilai + $atur);

                continue;
            }

            $belumDiedit = in_array(md5((string) $ada->template_html), self::TEMPLATE_LAMA, true);
            // Folder arsip layanan lama tidak diubah, supaya arsip lama & baru tetap satu folder
            $ada->update(($timpaTemplate || $belumDiedit ? $nilai : []) + $atur);
        }
    }
}
