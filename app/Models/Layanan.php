<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Layanan extends Model
{
    protected $table = 'layanan';

    protected $guarded = ['id'];

    /**
     * Bidang Kasi yang mengerjakan jenis surat ini (sesuai folder arsip kantor: kasi pemerintahan / kasi kesos / kasi pm).
     * Permohonan hanya muncul di antrean Kasi dengan bidang yang sama. Layanan tanpa bidang muncul di semua Kasi.
     */
    public const BIDANG = [
        'pemerintahan' => 'Kasi Pemerintahan',
        'kesos' => 'Kasi Kesos',
        'pm' => 'Kasi PM',
    ];

    public function labelBidang(): string
    {
        return self::BIDANG[$this->bidang] ?? 'Semua Kasi';
    }

    /** Apakah akun ini boleh mengambil surat jenis ini dari antrean. */
    public function untukKasi(User $user): bool
    {
        return ! $user->isKasi() || ! $user->bidang || ! $this->bidang || $this->bidang === $user->bidang;
    }

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
                // "Label | pilihan: A; B; C" → daftar pilihan (nomor urut pilihan tersedia sebagai {key}_kode)
                if (preg_match('/^pilihan\s*:(.*)$/i', $tipe, $m)) {
                    $pilihan = array_values(array_filter(array_map('trim', explode(';', $m[1])), 'strlen'));
                    if ($pilihan) {
                        return ['key' => Str::slug($label, '_'), 'label' => $label, 'tipe' => 'pilihan', 'pilihan' => $pilihan, 'wajib' => $wajib];
                    }
                }
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
            ->map(fn ($i) => $i['label'].' | '.$i['tipe'].($i['tipe'] === 'pilihan' ? ': '.implode('; ', $i['pilihan'] ?? []) : '').($i['wajib'] ? '' : ' (opsional)'))
            ->implode("\n");
    }
}
