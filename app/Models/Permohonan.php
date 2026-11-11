<?php

namespace App\Models;

use App\Models\Scopes\DataContoh;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class Permohonan extends Model
{
    protected $table = 'permohonan';

    protected $guarded = ['id'];

    public const STATUS = [
        'baru' => ['Dalam Antrean', 'primary'],
        'diproses' => ['Sedang Diproses', 'warning'],
        'ditolak' => ['Ditolak', 'danger'],
        'selesai' => ['Selesai', 'success'],
    ];

    protected static function booted(): void
    {
        // Tamu hanya melihat/membuat data contoh; petugas asli tidak melihat data contoh
        static::addGlobalScope(new DataContoh);
        static::creating(fn (self $p) => DataContoh::tandai($p));
    }

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'contoh' => 'boolean',
            'petugas_id' => 'integer',
            'tanggal_surat' => 'date',
            'tanggal_antrian' => 'date',
            'diambil_at' => 'datetime',
            'selesai_at' => 'datetime',
        ];
    }

    public function layanan(): BelongsTo
    {
        return $this->belongsTo(Layanan::class);
    }

    public function berkas(): HasMany
    {
        return $this->hasMany(Berkas::class);
    }

    public function petugas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'petugas_id');
    }

    /** Petugas Pelayanan yang menginput permohonan di tablet. */
    public function penginput(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function penduduk(): ?Penduduk
    {
        return Penduduk::where('nik', $this->nik)->first();
    }

    public function statusLabel(): string
    {
        return self::STATUS[$this->status][0] ?? $this->status;
    }

    public function statusWarna(): string
    {
        return self::STATUS[$this->status][1] ?? 'secondary';
    }

    /** "007" */
    public function nomorLabel(): string
    {
        return str_pad((string) $this->nomor_antrian, 3, '0', STR_PAD_LEFT);
    }

    /** "No. 007 · 09/11/2026 10:23" */
    public function antrianLengkap(): string
    {
        return 'No. '.$this->nomorLabel().' · '.$this->tanggal_antrian?->format('d/m/Y').' '.$this->created_at?->format('H:i');
    }

    /**
     * Simpan permohonan baru dengan nomor antrian harian (mulai dari 1 lagi setiap hari).
     * Kalau dua warga mengirim di detik yang sama, nomor kembar ditolak database lalu dicoba ulang.
     */
    public static function buatDenganAntrian(array $atribut): self
    {
        for ($coba = 1; ; $coba++) {
            try {
                return DB::transaction(function () use ($atribut) {
                    $hari = now()->startOfDay(); // disimpan Eloquent sebagai "Y-m-d 00:00:00", seragam untuk indeks unik
                    $nomor = (int) self::whereDate('tanggal_antrian', $hari)->max('nomor_antrian') + 1;

                    return self::create($atribut + [
                        'tanggal_antrian' => $hari,
                        'nomor_antrian' => $nomor,
                        // Data contoh berawalan "C" supaya kode & nama folder arsip tidak bentrok dengan data asli
                        'kode' => (DataContoh::aktif() ? 'C' : '').now()->format('ymd').'-'.str_pad((string) $nomor, 3, '0', STR_PAD_LEFT),
                    ]);
                });
            } catch (QueryException $e) {
                if ($coba >= 5) {
                    throw $e;
                }
                usleep(random_int(50, 200) * 1000);
            }
        }
    }

    /**
     * Ambil permohonan dari antrean (seperti menerima orderan). Atomik: kalau dua komputer
     * menekan "Ambil" bersamaan, hanya satu yang berhasil.
     */
    public function ambilOleh(User $user): bool
    {
        return self::whereKey($this->id)
            ->where('status', 'baru')
            ->whereNull('petugas_id')
            ->update(['status' => 'diproses', 'petugas_id' => $user->id, 'diambil_at' => now(), 'updated_at' => now()]) === 1;
    }

    /** Hanya jenis surat bagian Kasi ini (Kasi tanpa bidang & peran lain melihat semua). */
    public function scopeBagianKasi($query, User $user)
    {
        if ($user->isKasi() && $user->bidang) {
            $query->whereHas('layanan', fn ($l) => $l->where(
                fn ($w) => $w->whereNull('bidang')->orWhere('bidang', $user->bidang)
            ));
        }

        return $query;
    }

    /** Kembalikan ke antrean supaya bisa diambil komputer lain. */
    public function lepaskan(): void
    {
        $this->update(['status' => 'baru', 'petugas_id' => null, 'diambil_at' => null]);
    }

    // ---------- Hak akses ----------

    /** Boleh membuka detail & berkas lampiran. */
    public function bolehDilihat(User $user): bool
    {
        if ($user->punyaPeran('admin', 'pelayanan')) {
            return true;
        }

        // Kasi: yang masih menunggu di antrean, atau yang sedang ia kerjakan sendiri
        return ($this->status === 'baru' && ! $this->petugas_id && $this->layanan?->untukKasi($user))
            ||($this->status === 'diproses' && $this->petugas_id === $user->id);
    }

    /** Boleh mengedit & mencetak surat. */
    public function bolehDikerjakan(User $user): bool
    {
        if ($this->status === 'diproses') {
            return $this->petugas_id === $user->id;
        }

        // Revisi surat yang sudah selesai: hanya admin & pelayanan
        return $this->status === 'selesai' && $user->punyaPeran('admin', 'pelayanan');
    }

    public function bolehDitolak(User $user): bool
    {
        return match ($this->status) {
            'baru' => $user->punyaPeran('admin', 'pelayanan'),
            'diproses' => $this->petugas_id === $user->id || $user->punyaPeran('admin', 'pelayanan'),
            default => false,
        };
    }
}
