<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role'])]
#[Hidden(['password', 'remember_token'])]
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
        ];
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

    public function punyaPeran(string ...$peran): bool
    {
        return in_array($this->role, $peran, true);
    }

    public function labelPeran(): string
    {
        return self::PERAN[$this->role] ?? $this->role;
    }
}
