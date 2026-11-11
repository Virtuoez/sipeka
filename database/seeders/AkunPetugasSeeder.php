<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Akun awal: 1 Pelayanan + 3 Kasi (satu akun per komputer Kasi).
 * Aman dijalankan ulang: akun yang sudah ada TIDAK diubah (password yang sudah diganti tetap).
 * WAJIB ganti password tiap akun lewat menu "Ganti Password" setelah login pertama.
 *
 *   php artisan db:seed --class=AkunPetugasSeeder
 */
class AkunPetugasSeeder extends Seeder
{
    public function run(): void
    {
        $akun = [
            ['pelayanan@sekumpul.test', 'Admin Pelayanan', 'pelayanan', null],
            // Satu akun per bidang Kasi (sesuai folder kantor). Surat otomatis masuk ke Kasi bidangnya.
            ['kasi1@sekumpul.test', 'Kasi Pemerintahan', 'kasi', 'pemerintahan'],
            ['kasi2@sekumpul.test', 'Kasi Kesos', 'kasi', 'kesos'],
            ['kasi3@sekumpul.test', 'Kasi PM', 'kasi', 'pm'],
        ];

        foreach ($akun as [$email, $nama, $peran, $bidang]) {
            User::firstOrCreate(['email' => $email], [
                'name' => $nama,
                'role' => $peran,
                'bidang' => $bidang,
                'password' => 'sekumpul123',
            ]);
        }
    }
}
