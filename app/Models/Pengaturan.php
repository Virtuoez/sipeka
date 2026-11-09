<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Pengaturan extends Model
{
    protected $table = 'pengaturan';

    protected $primaryKey = 'kunci';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $guarded = [];

    public const BAWAAN = [
        'nama_kelurahan' => 'Sekumpul',
        'kecamatan' => 'Martapura',
        'kabupaten' => 'Banjar',
        'provinsi' => 'Kalimantan Selatan',
        'alamat_kantor' => 'Jl. Sekumpul, Kec. Martapura, Kab. Banjar, Kalimantan Selatan',
        'telepon' => '',
        'email' => '',
        'jam_layanan' => 'Senin - Kamis 08.00 - 15.00 WITA, Jumat 08.00 - 11.00 WITA',
        'nama_lurah' => 'NAMA LURAH',
        'nip_lurah' => '19xxxxxxxxxxxxxxxx',
        'jabatan_ttd' => 'Lurah Sekumpul',
        'format_nomor' => '{kode}/{no}/KEL-SKP/{bulan_romawi}/{tahun}',
        'kertas' => 'A4',
        'logo' => '',
        'pengumuman' => 'Pengajuan surat dapat dilakukan secara online. Surat yang sudah selesai dapat diambil di Kantor Kelurahan Sekumpul dengan membawa berkas asli.',
    ];

    public static function semua(): array
    {
        return Cache::rememberForever('pengaturan', function () {
            $tersimpan = self::query()->pluck('nilai', 'kunci')->all();

            return array_merge(self::BAWAAN, array_filter($tersimpan, fn ($v) => $v !== null));
        });
    }

    public static function ambil(string $kunci, $bawaan = null)
    {
        return self::semua()[$kunci] ?? $bawaan;
    }

    public static function simpan(array $data): void
    {
        foreach ($data as $kunci => $nilai) {
            self::updateOrCreate(['kunci' => $kunci], ['nilai' => $nilai]);
        }
        Cache::forget('pengaturan');
    }
}
