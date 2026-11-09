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
            ['pelayanan@sekumpul.test', 'Admin Pelayanan', 'pelayanan'],
            ['kasi1@sekumpul.test', 'Kasi PC 1', 'kasi'],
            ['kasi2@sekumpul.test', 'Kasi PC 2', 'kasi'],
            ['kasi3@sekumpul.test', 'Kasi PC 3', 'kasi'],
        ];

        foreach ($akun as [$email, $nama, $peran]) {
            User::firstOrCreate(['email' => $email], [
                'name' => $nama,
                'role' => $peran,
                'password' => 'sekumpul123',
            ]);
        }
    }
}
