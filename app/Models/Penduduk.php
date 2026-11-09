<?php

namespace App\Models;

use App\Models\Scopes\DataContoh;
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

    protected static function booted(): void
    {
        // Tamu hanya melihat penduduk contoh; petugas asli tidak melihat penduduk contoh
        static::addGlobalScope(new DataContoh);
        static::creating(fn (self $p) => DataContoh::tandai($p));
    }

    protected function casts(): array
    {
        return ['tanggal_lahir' => 'date', 'contoh' => 'boolean'];
    }

    public function getUmurAttribute(): ?int
    {
        return $this->tanggal_lahir?->age;
    }
}
