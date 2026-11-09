<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'pin'])]
#[Hidden(['password', 'remember_token', 'pin'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'pin' => 'hashed',
        ];
    }

    public function punyaPin(): bool
    {
        return filled($this->pin);
    }

    public const PERAN = [
        'admin' => 'Admin',
        'pelayanan' => 'Pelayanan',
        'kasi' => 'Kasi',
    ];

    public function permohonanDikerjakan(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Permohonan::class, 'petugas_id');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isPelayanan(): bool
    {
        return $this->role === 'pelayanan';
    }

    public function isKasi(): bool
    {
        return $this->role === 'kasi';
    }

    /** Akun tamu: mencoba Pelayanan + Kasi, tapi hanya melihat & membuat DATA CONTOH. */
    public function isTamu(): bool
    {
        return $this->role === 'tamu';
    }

    public function punyaPeran(string ...$peran): bool
    {
        // Tamu dianggap Pelayanan sekaligus Kasi (datanya dibatasi ke data contoh oleh scope DataContoh)
        if ($this->isTamu()) {
            return (bool) array_intersect($peran, ['pelayanan', 'kasi']);
        }

        return in_array($this->role, $peran, true);
    }

    public function labelPeran(): string
    {
        return self::PERAN[$this->role] ?? ($this->isTamu() ? 'Tamu (Demo)' : $this->role);
    }
}
