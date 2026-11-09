<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Berkas extends Model
{
    protected $table = 'berkas';

    protected $guarded = ['id'];

    public function permohonan(): BelongsTo
    {
        return $this->belongsTo(Permohonan::class);
    }

    public function isGambar(): bool
    {
        return str_starts_with((string) $this->mime, 'image/');
    }
}
