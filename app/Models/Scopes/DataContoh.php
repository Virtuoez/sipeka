<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * Memisahkan DATA CONTOH dari data asli warga:
 * akun tamu hanya melihat baris contoh=true, semua akun lain hanya contoh=false.
 * Dipasang di Permohonan & Penduduk, jadi semua halaman/query Eloquent ikut tersaring.
 * Seeder & perintah konsol (tanpa login) bekerja di data asli; pakai withoutGlobalScope(DataContoh::class) bila perlu.
 */
class DataContoh implements Scope
{
    public static function aktif(): bool
    {
        return (bool) Auth::user()?->isTamu();
    }

    public function apply(Builder $builder, Model $model): void
    {
        $builder->where($model->qualifyColumn('contoh'), self::aktif());
    }

    /** Baris baru otomatis ditandai contoh kalau dibuat oleh tamu. */
    public static function tandai(Model $model): void
    {
        if ($model->getAttribute('contoh') === null) {
            $model->setAttribute('contoh', self::aktif());
        }
    }
}
