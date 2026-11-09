<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Penduduk extends Model
{
    protected $table = 'penduduk';

    protected $guarded = ['id'];

    public const KOLOM = [
        'nik', 'no_kk', 'nama', 'tempat_lahir', 'tanggal_lahir', 'jenis_kelamin', 'agama',
        'status_perkawinan', 'pekerjaan', 'pendidikan', 'golongan_darah', 'kewarganegaraan',
        'hubungan_keluarga', 'alamat', 'rt', 'rw', 'no_hp', 'status', 'keterangan',
    ];

    public const AGAMA = ['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu', 'Kepercayaan'];

    public const STATUS_KAWIN = ['Belum Kawin', 'Kawin', 'Cerai Hidup', 'Cerai Mati'];

    public const PENDIDIKAN = [
        'Tidak/Belum Sekolah', 'Belum Tamat SD', 'SD/Sederajat', 'SMP/Sederajat', 'SMA/Sederajat',
        'Diploma I/II', 'Diploma III', 'Diploma IV/S1', 'S2', 'S3',
    ];

    public const HUBUNGAN = ['Kepala Keluarga', 'Istri', 'Suami', 'Anak', 'Menantu', 'Cucu', 'Orang Tua', 'Mertua', 'Famili Lain', 'Lainnya'];

    protected function casts(): array
    {
        return ['tanggal_lahir' => 'date'];
    }

    public function getUmurAttribute(): ?int
    {
        return $this->tanggal_lahir?->age;
    }
}
