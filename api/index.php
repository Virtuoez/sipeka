<?php

/*
 * Pintu masuk SIPEKA di Vercel (runtime komunitas vercel-php).
 * Hanya dipakai di Vercel; di PC kelurahan website dijalankan Apache lewat jalankan-jaringan.bat.
 *
 * Vercel bersifat serverless: folder proyek hanya-baca, satu-satunya tempat menulis adalah /tmp,
 * dan /tmp bisa kosong lagi kapan saja. Karena itu storage & database SQLite dibuat di /tmp,
 * lalu diisi data awal (layanan, template, akun, penduduk contoh) setiap kali masih kosong.
 * Data yang diinput di versi Vercel TIDAK permanen.
 */

// Laravel membaca LARAVEL_STORAGE_PATH dari $_ENV/$_SERVER, yang bisa kosong (variables_order tanpa "E")
foreach (['LARAVEL_STORAGE_PATH'] as $kunci) {
    if (($nilai = getenv($kunci)) !== false) {
        $_ENV[$kunci] = $_SERVER[$kunci] = $nilai;
    }
}

$tmp = '/tmp/sipeka';
foreach ([
    'storage/app/private', 'storage/app/public', 'storage/framework/cache/data',
    'storage/framework/sessions', 'storage/framework/views', 'storage/logs', 'storage/fonts',
] as $folder) {
    if (! is_dir("$tmp/$folder")) {
        @mkdir("$tmp/$folder", 0777, true);
    }
}

// Database disiapkan sekali per /tmp. Beberapa permintaan pertama bisa datang bersamaan, jadi pakai kunci:
// hanya satu yang menyiapkan, sisanya menunggu. Penanda "siap" baru ditulis kalau migrasi + data awal sukses,
// sehingga database setengah jadi (mis. gagal di tengah) akan disiapkan ulang pada permintaan berikutnya.
$database = "$tmp/database.sqlite";
$siap = "$tmp/database.siap";
if (! file_exists($siap)) {
    $kunci = fopen("$tmp/database.lock", 'c');
    flock($kunci, LOCK_EX);
    try {
        if (! file_exists($siap)) {
            @unlink($database);
            touch($database);

            require __DIR__.'/../vendor/autoload.php';
            $app = require __DIR__.'/../bootstrap/app.php';
            try {
                $kode = $app->make(Illuminate\Contracts\Console\Kernel::class)
                    ->call('migrate', ['--force' => true, '--seed' => true]);
            } catch (Throwable $e) {
                // Log Vercel memotong pesan panjang: catat ringkasannya dulu supaya penyebabnya terbaca
                $ringkas = 'SIPEKA-DB-GAGAL '.get_class($e).': '.mb_substr($e->getMessage(), 0, 600)
                    .' @ '.$e->getFile().':'.$e->getLine();
                for ($p = $e->getPrevious(); $p; $p = $p->getPrevious()) {
                    $ringkas .= "\nSEBAB ".get_class($p).': '.mb_substr($p->getMessage(), 0, 400);
                }
                error_log($ringkas);
                http_response_code(500);
                header('Content-Type: text/plain; charset=utf-8');
                echo $ringkas;
                exit;
            }
            if ($kode !== 0) {
                throw new RuntimeException('Menyiapkan database SIPEKA gagal (kode '.$kode.').');
            }
            touch($siap);
        }
    } finally {
        flock($kunci, LOCK_UN);
        fclose($kunci);
    }
}

require __DIR__.'/../public/index.php';
