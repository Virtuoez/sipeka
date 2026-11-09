<?php

namespace Database\Seeders;

use App\Models\Penduduk;
use App\Models\Scopes\DataContoh;
use Illuminate\Database\Seeder;

/** Data CONTOH (fiktif) untuk uji coba. Hapus lewat panel admin sebelum dipakai sungguhan. */
class ContohPendudukSeeder extends Seeder
{
    public function run(): void
    {
        $kk1 = '6303000000000001';
        $kk2 = '6303000000000002';
        $kk3 = '6303000000000003';

        $data = [
            [$kk1, '6303000101800001', 'Ahmad Fauzi', 'Martapura', '1980-01-01', 'Laki-laki', 'Kawin', 'Wiraswasta', 'SMA/Sederajat', 'Kepala Keluarga', '001', '001'],
            [$kk1, '6303000202850002', 'Siti Aminah', 'Banjarmasin', '1985-02-02', 'Perempuan', 'Kawin', 'Mengurus Rumah Tangga', 'SMA/Sederajat', 'Istri', '001', '001'],
            [$kk1, '6303000303100003', 'Muhammad Rizki', 'Martapura', '2010-03-03', 'Laki-laki', 'Belum Kawin', 'Pelajar/Mahasiswa', 'SMP/Sederajat', 'Anak', '001', '001'],
            [$kk2, '6303000404750004', 'Hj. Rahmah', 'Martapura', '1975-04-04', 'Perempuan', 'Cerai Mati', 'Pedagang', 'SD/Sederajat', 'Kepala Keluarga', '002', '001'],
            [$kk2, '6303000505000005', 'Nurul Hidayah', 'Martapura', '2000-05-05', 'Perempuan', 'Belum Kawin', 'Karyawan Swasta', 'Diploma IV/S1', 'Anak', '002', '001'],
            [$kk3, '6303000606900006', 'Abdul Hadi', 'Barabai', '1990-06-06', 'Laki-laki', 'Kawin', 'Pegawai Negeri Sipil', 'Diploma IV/S1', 'Kepala Keluarga', '003', '002'],
            [$kk3, '6303000707920007', 'Fitriani', 'Martapura', '1992-07-07', 'Perempuan', 'Kawin', 'Guru', 'Diploma IV/S1', 'Istri', '003', '002'],
            [$kk3, '6303000808200008', 'Zahra Aulia', 'Martapura', '2020-08-08', 'Perempuan', 'Belum Kawin', 'Belum/Tidak Bekerja', 'Tidak/Belum Sekolah', 'Anak', '003', '002'],
        ];

        foreach ($data as [$kk, $nik, $nama, $tempat, $tgl, $jk, $kawin, $kerja, $didik, $hub, $rt, $rw]) {
            // Data contoh: hanya terlihat oleh akun tamu (lihat Scopes\DataContoh)
            Penduduk::withoutGlobalScope(DataContoh::class)->updateOrCreate(['nik' => $nik], [
                'contoh' => true,
                'no_kk' => $kk, 'nama' => $nama, 'tempat_lahir' => $tempat, 'tanggal_lahir' => $tgl,
                'jenis_kelamin' => $jk, 'agama' => 'Islam', 'status_perkawinan' => $kawin, 'pekerjaan' => $kerja,
                'pendidikan' => $didik, 'hubungan_keluarga' => $hub, 'kewarganegaraan' => 'WNI',
                'alamat' => 'Jl. Sekumpul Gg. Contoh No. '.(int) $rt, 'rt' => $rt, 'rw' => $rw,
                'status' => 'aktif', 'keterangan' => 'DATA CONTOH',
            ]);
        }
    }
}
