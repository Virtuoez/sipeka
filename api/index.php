<?php

/*
 * Pintu masuk SIPEKA di Vercel (runtime komunitas vercel-php).
 * Hanya dipakai di Vercel; di PC kelurahan website dijalankan Apache lewat jalankan-jaringan.bat.
 *
 * Database: Postgres (Neon) dari Vercel Marketplace. Integrasinya mengisi DATABASE_URL;
 * di sini disalin ke DB_URL yang dibaca config/database.php.
 * Folder proyek di Vercel hanya-baca, jadi storage Laravel (cache view, file surat) ada di /tmp
 * dan bisa hilang kapan saja. Data permohonan & penduduk aman karena ada di Postgres.
 */

// Laravel membaca sebagian variabel dari $_ENV/$_SERVER, yang bisa kosong (variables_order tanpa "E")
$salin = ['LARAVEL_STORAGE_PATH' => 'LARAVEL_STORAGE_PATH'];
if (! getenv('DB_URL')) {
    $salin['DB_URL'] = getenv('DATABASE_URL') !== false ? 'DATABASE_URL' : 'POSTGRES_URL';
}
foreach ($salin as $tujuan => $sumber) {
    if (($nilai = getenv($sumber)) !== false && $nilai !== '') {
        putenv("$tujuan=$nilai");
        $_ENV[$tujuan] = $_SERVER[$tujuan] = $nilai;
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

// Sekali per instance: jalankan migrasi terbaru, dan isi data awal hanya kalau database masih kosong
// (data yang sudah ada TIDAK ditimpa). Kunci advisory Postgres mencegah beberapa instance bermigrasi bersamaan.
$siap = "$tmp/database.siap";
if (! file_exists($siap)) {
    require __DIR__.'/../vendor/autoload.php';
    $app = require __DIR__.'/../bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    $db = $app->make('db')->connection();

    try {
        $db->select('select pg_advisory_lock(7310)');
        try {
            if ($kernel->call('migrate', ['--force' => true]) !== 0) {
                throw new RuntimeException('Migrasi database SIPEKA gagal: '.$kernel->output());
            }
            if (App\Models\User::query()->count() === 0
                && $kernel->call('db:seed', ['--force' => true]) !== 0) {
                throw new RuntimeException('Data awal SIPEKA gagal diisi: '.$kernel->output());
            }
        } finally {
            $db->select('select pg_advisory_unlock(7310)');
        }
        touch($siap);
    } catch (Throwable $e) {
        // Log Vercel memotong pesan panjang: catat ringkasannya supaya penyebabnya terbaca
        $ringkas = 'SIPEKA-DB-GAGAL '.get_class($e).': '.mb_substr($e->getMessage(), 0, 600)
            .' @ '.$e->getFile().':'.$e->getLine();
        for ($p = $e->getPrevious(); $p; $p = $p->getPrevious()) {
            $ringkas .= ' | SEBAB '.get_class($p).': '.mb_substr($p->getMessage(), 0, 400);
        }
        error_log($ringkas);
        throw $e;
    }
}

require __DIR__.'/../public/index.php';
