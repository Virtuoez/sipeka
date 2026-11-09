<?php

namespace Database\Seeders;

use App\Models\Layanan;
use Illuminate\Database\Seeder;

class LayananSeeder extends Seeder
{
    /** Baris tabel identitas: "Nama : {{nama}}" */
    private function identitas(array $baris): string
    {
        $html = '<table style="width: 100%; border-collapse: collapse; margin-left: 30px;" width="100%"><tbody>';
        foreach ($baris as $label => $penanda) {
            $html .= '<tr><td style="width: 32%;" width="32%">'.$label.'</td><td style="width: 3%;" width="3%">:</td><td style="width: 65%;" width="65%">'.$penanda.'</td></tr>';
        }

        return $html.'</tbody></table>';
    }

    private function dataDiri(): string
    {
        return $this->identitas([
            'Nama Lengkap' => '<strong>{{nama}}</strong>',
            'NIK' => '{{nik}}',
            'Tempat, Tgl. Lahir' => '{{ttl}}',
            'Jenis Kelamin' => '{{jenis_kelamin}}',
            'Agama' => '{{agama}}',
            'Status Perkawinan' => '{{status_perkawinan}}',
            'Pekerjaan' => '{{pekerjaan}}',
            'Kewarganegaraan' => '{{kewarganegaraan}}',
            'Alamat' => '{{alamat}} RT {{rt}} RW {{rw}}',
        ]);
    }

    private function surat(string $judul, string $isi, string $penutup = 'Demikian surat keterangan ini dibuat dengan sebenarnya untuk dapat dipergunakan sebagaimana mestinya.'): string
    {
        return '<p>{{kop_surat}}</p>'
            .'<p style="text-align: center; margin: 0;"><strong><u>'.mb_strtoupper($judul).'</u></strong></p>'
            .'<p style="text-align: center;">Nomor: {{nomor_surat}}</p>'
            .'<p>&nbsp;</p>'
            .'<p style="text-align: justify;">Yang bertanda tangan di bawah ini {{jabatan_ttd}}, Kecamatan {{kecamatan}}, Kabupaten {{kabupaten}}, dengan ini menerangkan bahwa:</p>'
            .$this->dataDiri()
            .'<p>&nbsp;</p>'
            .$isi
            .'<p style="text-align: justify;">'.$penutup.'</p>'
            .'<p>&nbsp;</p>'
            .$this->ttd();
    }

    private function ttd(): string
    {
        return '<table style="width: 100%; border-collapse: collapse;" width="100%"><tbody><tr>'
            .'<td style="width: 50%;" width="50%">&nbsp;</td>'
            .'<td style="width: 50%; text-align: center;" width="50%">'
            .'<p style="text-align: center; margin: 0;">{{nama_kelurahan}}, {{tanggal_surat}}</p>'
            .'<p style="text-align: center; margin: 0;">{{jabatan_ttd}}</p>'
            .'<p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p>'
            .'<p style="text-align: center; margin: 0;"><strong><u>{{nama_lurah}}</u></strong></p>'
            .'<p style="text-align: center; margin: 0;">NIP. {{nip_lurah}}</p>'
            .'</td></tr></tbody></table>';
    }

    public function run(): void
    {
        $data = [
            [
                'slug' => 'domisili', 'nama' => 'Surat Keterangan Domisili', 'kode_surat' => '470', 'folder' => '01 - Keterangan Domisili', 'ikon' => 'house-door',
                'deskripsi' => 'Keterangan bahwa warga benar berdomisili di Kelurahan Sekumpul.',
                'persyaratan' => "KTP\nKartu Keluarga\nSurat Pengantar RT/RW (opsional)",
                'isian' => "Keperluan | paragraf\nLama Tinggal | teks (opsional)",
                'isi' => '<p style="text-align: justify;">Orang tersebut di atas adalah benar warga yang berdomisili di Kelurahan {{nama_kelurahan}}, Kecamatan {{kecamatan}}, Kabupaten {{kabupaten}}. Surat keterangan ini diberikan untuk keperluan: <strong>{{keperluan}}</strong>.</p>',
            ],
            [
                'slug' => 'sktm', 'nama' => 'Surat Keterangan Tidak Mampu', 'kode_surat' => '400', 'folder' => '02 - SKTM', 'ikon' => 'heart',
                'deskripsi' => 'Untuk keringanan biaya sekolah, berobat, atau bantuan sosial.',
                'persyaratan' => "KTP\nKartu Keluarga\nSurat Pengantar RT/RW (opsional)\nFoto Rumah (opsional)",
                'isian' => "Keperluan | paragraf\nPenghasilan per Bulan | teks",
                'isi' => '<p style="text-align: justify;">Berdasarkan data kependudukan dan keterangan yang bersangkutan, orang tersebut di atas benar warga Kelurahan {{nama_kelurahan}} yang tergolong keluarga <strong>tidak mampu</strong> dengan penghasilan rata-rata {{penghasilan_per_bulan}} per bulan. Surat keterangan ini diberikan untuk keperluan: <strong>{{keperluan}}</strong>.</p>',
            ],
            [
                'slug' => 'usaha', 'nama' => 'Surat Keterangan Usaha', 'kode_surat' => '500', 'folder' => '03 - Keterangan Usaha', 'ikon' => 'shop',
                'deskripsi' => 'Keterangan memiliki usaha, biasanya untuk pengajuan KUR/pinjaman bank.',
                'persyaratan' => "KTP\nKartu Keluarga\nFoto Tempat Usaha\nSurat Pengantar RT/RW (opsional)",
                'isian' => "Nama Usaha | teks\nJenis Usaha | teks\nAlamat Usaha | paragraf\nMulai Usaha Sejak | teks\nKeperluan | paragraf",
                'isi' => '<p style="text-align: justify;">Orang tersebut di atas benar memiliki usaha sebagai berikut:</p>'
                    .$this->identitas(['Nama Usaha' => '<strong>{{nama_usaha}}</strong>', 'Jenis Usaha' => '{{jenis_usaha}}', 'Alamat Usaha' => '{{alamat_usaha}}', 'Berdiri Sejak' => '{{mulai_usaha_sejak}}'])
                    .'<p>&nbsp;</p><p style="text-align: justify;">Surat keterangan ini diberikan untuk keperluan: <strong>{{keperluan}}</strong>.</p>',
            ],
            [
                'slug' => 'pengantar-ktp', 'nama' => 'Surat Pengantar KTP-el', 'kode_surat' => '470', 'folder' => '04 - Pengantar KTP', 'ikon' => 'person-vcard',
                'deskripsi' => 'Pengantar untuk pembuatan, perubahan, atau cetak ulang KTP elektronik di Disdukcapil.',
                'persyaratan' => "Kartu Keluarga\nKTP Lama (opsional)\nSurat Kehilangan dari Kepolisian (opsional)",
                'isian' => "Jenis Permohonan KTP | teks\nAlasan | paragraf (opsional)",
                'isi' => '<p style="text-align: justify;">Orang tersebut di atas adalah benar warga Kelurahan {{nama_kelurahan}} dan bermaksud mengurus <strong>KTP Elektronik ({{jenis_permohonan_ktp}})</strong> pada Dinas Kependudukan dan Pencatatan Sipil Kabupaten {{kabupaten}}. Keterangan: {{alasan}}</p>',
                'penutup' => 'Demikian surat pengantar ini dibuat untuk dapat dipergunakan sebagaimana mestinya.',
            ],
            [
                'slug' => 'pengantar-kk', 'nama' => 'Surat Pengantar Kartu Keluarga', 'kode_surat' => '470', 'folder' => '05 - Pengantar KK', 'ikon' => 'people',
                'deskripsi' => 'Pengantar untuk pembuatan KK baru, penambahan anggota, atau perubahan data KK.',
                'persyaratan' => "KTP\nKartu Keluarga Lama (opsional)\nBuku Nikah / Akta Perkawinan (opsional)\nAkta Kelahiran Anggota Baru (opsional)\nSurat Pengantar RT/RW (opsional)",
                'isian' => "Jenis Perubahan KK | teks\nRincian Perubahan | paragraf",
                'isi' => '<p style="text-align: justify;">Orang tersebut di atas adalah benar warga Kelurahan {{nama_kelurahan}} dan bermaksud mengurus <strong>Kartu Keluarga ({{jenis_perubahan_kk}})</strong> dengan rincian: {{rincian_perubahan}}.</p>',
                'penutup' => 'Demikian surat pengantar ini dibuat untuk dapat dipergunakan sebagaimana mestinya.',
            ],
            [
                'slug' => 'kelahiran', 'nama' => 'Surat Keterangan Kelahiran', 'kode_surat' => '474.1', 'folder' => '06 - Keterangan Kelahiran', 'ikon' => 'balloon-heart',
                'deskripsi' => 'Keterangan kelahiran anak, untuk pengurusan Akta Kelahiran. Data diri diisi data pelapor (orang tua).',
                'persyaratan' => "KTP Pelapor\nKartu Keluarga\nSurat Keterangan Lahir dari Bidan/Rumah Sakit\nBuku Nikah Orang Tua",
                'isian' => "Nama Anak | teks\nJenis Kelamin Anak | teks\nTempat Lahir Anak | teks\nTanggal Lahir Anak | tanggal\nAnak Ke | angka\nNama Ayah | teks\nNama Ibu | teks",
                'isi' => '<p style="text-align: justify;">Adalah benar orang tua/pelapor dari seorang anak yang telah lahir dengan keterangan:</p>'
                    .$this->identitas(['Nama Anak' => '<strong>{{nama_anak}}</strong>', 'Jenis Kelamin' => '{{jenis_kelamin_anak}}', 'Tempat, Tgl. Lahir' => '{{tempat_lahir_anak}}, {{tanggal_lahir_anak}}', 'Anak Ke' => '{{anak_ke}}', 'Nama Ayah' => '{{nama_ayah}}', 'Nama Ibu' => '{{nama_ibu}}'])
                    .'<p>&nbsp;</p>',
            ],
            [
                'slug' => 'kematian', 'nama' => 'Surat Keterangan Kematian', 'kode_surat' => '474.3', 'folder' => '07 - Keterangan Kematian', 'ikon' => 'flower1',
                'deskripsi' => 'Keterangan meninggal dunia, untuk pengurusan Akta Kematian. Data diri diisi data almarhum/almarhumah.',
                'persyaratan' => "KTP Almarhum/Almarhumah\nKartu Keluarga\nKTP Pelapor\nSurat Keterangan Kematian dari RS/Puskesmas (opsional)",
                'isian' => "Tanggal Meninggal | tanggal\nTempat Meninggal | teks\nSebab Meninggal | teks\nNama Pelapor | teks\nHubungan Pelapor | teks",
                'isi' => '<p style="text-align: justify;">Telah <strong>meninggal dunia</strong> pada tanggal {{tanggal_meninggal}} di {{tempat_meninggal}} karena {{sebab_meninggal}}. Keterangan ini dibuat berdasarkan laporan dari {{nama_pelapor}} ({{hubungan_pelapor}}).</p>',
            ],
            [
                'slug' => 'pindah', 'nama' => 'Surat Keterangan Pindah', 'kode_surat' => '475', 'folder' => '08 - Keterangan Pindah', 'ikon' => 'truck',
                'deskripsi' => 'Pengantar pindah keluar dari Kelurahan Sekumpul.',
                'persyaratan' => "KTP\nKartu Keluarga\nSurat Pengantar RT/RW (opsional)",
                'isian' => "Alamat Tujuan | paragraf\nAlasan Pindah | teks\nJumlah Anggota yang Ikut Pindah | angka",
                'isi' => '<p style="text-align: justify;">Orang tersebut di atas bermaksud <strong>pindah</strong> ke alamat {{alamat_tujuan}} dengan alasan {{alasan_pindah}}, beserta {{jumlah_anggota_yang_ikut_pindah}} orang anggota keluarga.</p>',
            ],
            [
                'slug' => 'belum-menikah', 'nama' => 'Surat Keterangan Belum Menikah', 'kode_surat' => '474.2', 'folder' => '09 - Keterangan Belum Menikah', 'ikon' => 'person-check',
                'deskripsi' => 'Keterangan berstatus belum pernah menikah.',
                'persyaratan' => "KTP\nKartu Keluarga\nSurat Pengantar RT/RW (opsional)",
                'isian' => 'Keperluan | paragraf',
                'isi' => '<p style="text-align: justify;">Berdasarkan data kependudukan dan keterangan yang bersangkutan, orang tersebut di atas sampai saat ini benar <strong>belum pernah menikah</strong>. Surat keterangan ini diberikan untuk keperluan: <strong>{{keperluan}}</strong>.</p>',
            ],
        ];

        foreach ($data as $i => $l) {
            Layanan::updateOrCreate(['slug' => $l['slug']], [
                'nama' => $l['nama'],
                'kode_surat' => $l['kode_surat'],
                'folder' => $l['folder'],
                'ikon' => $l['ikon'],
                'deskripsi' => $l['deskripsi'],
                'persyaratan' => Layanan::parsePersyaratan($l['persyaratan']),
                'isian' => Layanan::parseIsian($l['isian']),
                'template_html' => isset($l['penutup']) ? $this->surat($l['nama'], $l['isi'], $l['penutup']) : $this->surat($l['nama'], $l['isi']),
                'aktif' => true,
                'urutan' => $i + 1,
            ]);
        }
    }
}
