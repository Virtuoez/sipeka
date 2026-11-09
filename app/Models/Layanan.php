<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Layanan extends Model
{
    protected $table = 'layanan';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'persyaratan' => 'array',
            'isian' => 'array',
            'aktif' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function permohonan(): HasMany
    {
        return $this->hasMany(Permohonan::class);
    }

    /**
     * Ubah teks "satu baris satu item" dari form admin menjadi daftar persyaratan.
     * Baris yang diakhiri "(opsional)" dianggap tidak wajib.
     */
    public static function parsePersyaratan(?string $teks): array
    {
        return collect(preg_split('/\r?\n/', (string) $teks))
            ->map(fn ($baris) => trim($baris))
            ->filter()
            ->map(function ($baris) {
                $wajib = ! Str::endsWith(Str::lower($baris), '(opsional)');
                $label = trim(preg_replace('/\(opsional\)$/i', '', $baris));

                return ['key' => Str::slug($label, '_'), 'label' => $label, 'wajib' => $wajib];
            })
            ->unique('key')->values()->all();
    }

    /**
     * Format baris isian: "Label | tipe" (tipe: teks, paragraf, tanggal, angka). Tambah "(opsional)" bila tidak wajib.
     */
    public static function parseIsian(?string $teks): array
    {
        return collect(preg_split('/\r?\n/', (string) $teks))
            ->map(fn ($baris) => trim($baris))
            ->filter()
            ->map(function ($baris) {
                $wajib = ! Str::contains(Str::lower($baris), '(opsional)');
                $baris = trim(preg_replace('/\(opsional\)/i', '', $baris));
                [$label, $tipe] = array_pad(array_map('trim', explode('|', $baris, 2)), 2, 'teks');
                $tipe = in_array($tipe, ['teks', 'paragraf', 'tanggal', 'angka']) ? $tipe : 'teks';

                return ['key' => Str::slug($label, '_'), 'label' => $label, 'tipe' => $tipe, 'wajib' => $wajib];
            })
            ->unique('key')->values()->all();
    }

    public function persyaratanTeks(): string
    {
        return collect($this->persyaratan ?? [])
            ->map(fn ($p) => $p['label'].($p['wajib'] ? '' : ' (opsional)'))
            ->implode("\n");
    }

    public function isianTeks(): string
    {
        return collect($this->isian ?? [])
            ->map(fn ($i) => $i['label'].' | '.$i['tipe'].($i['wajib'] ? '' : ' (opsional)'))
            ->implode("\n");
    }
}
