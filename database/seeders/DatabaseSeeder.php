<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Akun admin awal. WAJIB ganti password lewat menu "Akun Saya" setelah login pertama.
        User::updateOrCreate(['email' => 'admin@sekumpul.test'], [
            'name' => 'Admin Kelurahan',
            'password' => 'sekumpul123',
            'role' => 'admin',
        ]);

        $this->call([
            AkunPetugasSeeder::class,
            LayananSeeder::class,
            ContohPendudukSeeder::class,
        ]);
    }
}
