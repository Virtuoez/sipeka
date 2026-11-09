<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

// Website hanya untuk petugas di jaringan kantor: alamat utama langsung ke layar pilih peran
Route::redirect('/', '/login');

// ---------- Masuk ----------
// Petugas: cukup pilih Pelayanan / Kasi (tanpa password). Admin: email + password.
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'pilih'])->name('login');
    Route::post('/masuk/{user}', [AuthController::class, 'masuk'])->name('masuk');
    Route::get('/login/admin', [AuthController::class, 'formAdmin'])->name('login.admin');
    Route::post('/login/admin', [AuthController::class, 'loginAdmin']);
});
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// ---------- Panel petugas ----------
// Peran: admin (semua), pelayanan (pantau & verifikasi, template, arsip), kasi (ambil & kerjakan surat)
Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', Admin\DashboardController::class)->name('dashboard');

    // Antrean ala "orderan": diambil satu komputer, hilang dari komputer lain
    Route::get('/antrean', [Admin\PermohonanController::class, 'antrean'])->middleware('peran:admin,kasi')->name('antrean');
    // Meja pelayanan (tablet): pilih surat → isi formulir bersama warga → nomor antrian → masuk antrean Kasi
    Route::controller(Admin\PengajuanController::class)->middleware('peran:admin,pelayanan')->prefix('pengajuan')->name('pengajuan.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/cari-nik', 'cariNik')->name('cari-nik');
        Route::get('/tiket/{permohonan}', 'tiket')->name('tiket');
        Route::get('/{layanan}', 'form')->name('form');
        Route::post('/{layanan}', 'simpan')->name('simpan');
    });
    // Cek cepat status nomor antrian (meja pelayanan)
    Route::get('/cek-antrian', [Admin\PermohonanController::class, 'cekAntrian'])->middleware('peran:admin,pelayanan')->name('cek-antrian');

    Route::controller(Admin\PermohonanController::class)->prefix('permohonan')->name('permohonan.')->group(function () {
        Route::get('/', 'index')->middleware('peran:admin,pelayanan')->name('index');
        Route::post('/{permohonan}/ambil', 'ambil')->middleware('peran:admin,kasi')->name('ambil');
        Route::post('/{permohonan}/lepas', 'lepas')->name('lepas');
        // Akses per permohonan dicek di controller (Kasi hanya miliknya sendiri)
        Route::get('/{permohonan}', 'show')->name('show');
        Route::post('/{permohonan}/tolak', 'tolak')->name('tolak');
        Route::get('/{permohonan}/surat', 'surat')->name('surat');
        Route::post('/{permohonan}/surat', 'simpanSurat')->name('simpan-surat');
        Route::post('/{permohonan}/surat/reset', 'resetSurat')->name('reset-surat');
        Route::get('/{permohonan}/unduh/{format}', 'unduh')->name('unduh');
        Route::get('/{permohonan}/zip', 'zip')->name('zip');
        Route::post('/{permohonan}/ke-penduduk', 'keDataPenduduk')->name('ke-penduduk');
        Route::delete('/{permohonan}', 'destroy')->middleware('peran:admin')->name('destroy');
    });
    Route::get('/berkas/{berkas}', [Admin\PermohonanController::class, 'berkas'])->name('berkas');


    // Admin & pelayanan
    Route::middleware('peran:admin,pelayanan')->group(function () {
        Route::get('/penduduk/template-csv', [Admin\PendudukController::class, 'templateCsv'])->name('penduduk.template-csv');
        Route::post('/penduduk/import', [Admin\PendudukController::class, 'import'])->name('penduduk.import');
        Route::resource('penduduk', Admin\PendudukController::class)->only(['create', 'store', 'edit', 'update'])->parameters(['penduduk' => 'penduduk']);

        Route::get('/arsip', [Admin\ArsipController::class, 'index'])->name('arsip.index');
        Route::get('/arsip/file', [Admin\ArsipController::class, 'file'])->name('arsip.file');
        Route::get('/arsip/zip', [Admin\ArsipController::class, 'zip'])->name('arsip.zip');

        Route::resource('layanan', Admin\LayananController::class)->except('show')->parameters(['layanan' => 'layanan']);
        Route::get('/layanan/{layanan}/template', [Admin\LayananController::class, 'template'])->name('layanan.template');
        Route::put('/layanan/{layanan}/template', [Admin\LayananController::class, 'simpanTemplate'])->name('layanan.simpan-template');
        Route::post('/layanan/{layanan}/pratinjau/{format}', [Admin\LayananController::class, 'pratinjau'])->name('layanan.pratinjau');
    });

    // Khusus admin
    Route::middleware('peran:admin')->group(function () {
        Route::delete('/penduduk/{penduduk}', [Admin\PendudukController::class, 'destroy'])->name('penduduk.destroy');

        Route::get('/akun-saya', [Admin\AkunController::class, 'saya'])->name('akun.saya');
        Route::put('/akun-saya', [Admin\AkunController::class, 'gantiPassword'])->name('akun.password');

        Route::get('/pengaturan', [Admin\PengaturanController::class, 'edit'])->name('pengaturan');
        Route::put('/pengaturan', [Admin\PengaturanController::class, 'update'])->name('pengaturan.update');

        Route::get('/akun', [Admin\AkunController::class, 'index'])->name('akun.index');
        Route::post('/akun', [Admin\AkunController::class, 'store'])->name('akun.store');
        Route::delete('/akun/{user}', [Admin\AkunController::class, 'destroy'])->name('akun.destroy');
    });

    // Data penduduk: semua peran boleh melihat. Didaftarkan paling akhir supaya
    // /penduduk/create dan /penduduk/template-csv tidak tertangkap sebagai /penduduk/{penduduk}.
    Route::resource('penduduk', Admin\PendudukController::class)->only(['index', 'show'])->parameters(['penduduk' => 'penduduk']);
});
