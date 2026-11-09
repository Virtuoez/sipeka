<?php

namespace App\Support;

/** Alamat website di jaringan kantor, supaya tablet & komputer lain tahu harus membuka apa. */
class Jaringan
{
    /** @return string[] contoh: ["http://192.168.1.10:8000"] */
    public static function alamat(): array
    {
        // Diisi tools/siapkan.ps1 (jalankan-jaringan.bat) dari adapter yang tersambung ke router
        if ($dariSkrip = config('app.alamat_lan')) {
            return [$dariSkrip];
        }

        // Cadangan: semua IP jaringan lokal (bisa ikut menampilkan adapter virtual)
        $port = request()->getPort();

        return collect(gethostbynamel(gethostname()) ?: [])
            ->filter(fn ($a) => preg_match('/^(10|172\.(1[6-9]|2\d|3[01])|192\.168)\./', $a))
            ->map(fn ($a) => "http://$a".($port == 80 ? '' : ":$port"))
            ->values()->all();
    }
}
