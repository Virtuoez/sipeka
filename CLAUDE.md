# SIPEKA Sekumpul: catatan untuk asisten AI

Website layanan surat & pendataan penduduk Kelurahan Sekumpul (Kec. Martapura, Kab. Banjar, Kalsel).
Laravel 13, PHP 8.3 (Laragon di `C:\laragon`), database SQLite (`database/database.sqlite`),
Bootstrap 5 + TinyMCE 6 di `public/vendor` (tanpa npm/Vite). Bahasa UI & kode: Indonesia.
Pemilik proyek adalah pemula. Jelaskan dengan bahasa santai, langkah demi langkah.

## Bentuk sistem (keputusan pemilik, jangan dikembalikan)
- **Lokal saja, TIDAK online.** Server = PC kelurahan (`jalankan-jaringan.bat`). Tablet Pelayanan & 3 PC Kasi membuka lewat WiFi/LAN kantor.
- **Tidak ada halaman warga.** `/` → `/login`. Semua halaman butuh login petugas. (Dulu ada beranda/cek status/form publik; sudah dihapus.)
- **Masuk tanpa password untuk Pelayanan & Kasi**: `/login` = layar 2 tombol (`auth/pilih.blade.php`), Kasi lalu memilih
  PC 1/2/3 (tiap PC = 1 akun, WAJIB beda supaya klaim antrean tetap per komputer). `POST /masuk/{user}` →
  `Auth::login($user, remember: true)` (perangkat diingat), menolak akun admin. Admin tetap email+password di `/login/admin`.
  Akun Pelayanan/Kasi baru cukup nama (email & password acak dibuat otomatis). Pesan validasi Indonesia di `lang/id/validation.php`.
- **Tidak ada unggah berkas.** Warga membawa berkas asli. Petugas mencentang di formulir; disimpan di `permohonan.data['berkas_dibawa']`
  (daftar label). Berkas wajib divalidasi `accepted`. Tabel `berkas` hanya untuk unggahan versi lama.

- **"Aplikasi" = PWA**, bukan APK/Electron: `public/manifest.json`, `public/sw.js` (hanya menyimpan `offline.html` + ikon,
  TIDAK menyimpan data/halaman), `resources/views/partials/pwa.blade.php` (tombol `[data-pasang-aplikasi]`).
  Ikon dibuat `php tools/buat-ikon.php`. Di alamat LAN (http) perlu flag Chrome `unsafely-treat-insecure-origin-as-secure`.
  Kalau `sw.js` diubah, naikkan versi `CACHE`.
- Surat Pengantar RT/RW **opsional** di semua layanan (keputusan pemilik). Template bawaan tidak menyebut pengantar RT/RW.

## Menjalankan
- `jalankan-jaringan.bat` (PC kelurahan, host 0.0.0.0) / `jalankan.bat` (laptop, 127.0.0.1) → `tools/siapkan.ps1`:
  mencari PHP Laragon, membuat php.ini + ekstensi, `composer install`, `.env`, database, `storage:link`, `config:cache`,
  lalu menulis `storage/app/apache/httpd.conf` dan menjalankan **Apache Laragon + mod_php** (fallback: `artisan serve`).
- JANGAN kembali ke `artisan serve` untuk pemakaian kantor: server bawaan PHP melayani 1 permintaan per waktu, sehingga
  CSS/JS ditolak (ERR_CONNECTION_REFUSED) saat tablet + PC Kasi aktif, apalagi saat membuat PDF → tampilan rusak.
- Apache di Windows memakai thread, jadi `config:cache` WAJIB (baca `.env` per permintaan tidak thread-safe).
  Jangan memanggil `env()`/`getenv()` di luar folder `config/`. Ubah `.env` → jalankan ulang skrip.
- Halaman yang diperbarui otomatis (antrean Kasi, Cek Antrian, lencana menu) memakai `partials/segarkan.blade.php`:
  fetch HTML lalu ganti elemen `[data-segarkan]`, BUKAN `location.reload()` (reload penuh tiap 10 detik bikin kedip & membebani server).
  Mode jaringan mengisi env `SIPEKA_ALAMAT_LAN` dari adapter ber-gateway dengan IP lokal (lewati VirtualBox & VPN Radmin 26.x);
  `App\Support\Jaringan` menampilkannya di sidebar.
- PHP tidak ada di PATH. Pakai `C:\laragon\bin\php\php-8.*\php.exe`.

## Inti sistem
- `app/Services/SuratService.php`: isi penanda `{{nama}}` dll di template → PDF (dompdf) / Word (PhpWord).
  - `{{kop_surat}}` dipasang saat cetak. `{{nomor_surat}}`/`{{tanggal_surat}}` ditunda sampai `final()`.
  - Nilai warga di-escape. `Settings::setOutputEscapingEnabled(true)` WAJIB, kalau tidak, docx rusak saat ada `&`/`<`.
  - `keXhtml()` merapikan HTML untuk PhpWord dan membuang `<img>` non-data-URI (cegah baca file server).
- `app/Services/ArsipService.php`: folder `storage/app/private/arsip/{folder layanan}/{tahun}/{MM - Bulan}/{YYMMDD-NNN}_{NAMA}/`
  berisi surat PDF + DOCX. `pathAbsolut()` menolak path di luar `arsip/`.
- Model: `Penduduk`, `Layanan` (persyaratan/isian JSON + template_html), `Permohonan` (data JSON, `dibuat_oleh` = petugas penginput),
  `Berkas`, `Pengaturan` (key-value, di-cache).
- Peran user (`users.role`): `admin` (semua), `pelayanan` (buat permohonan di tablet, cek antrian, pantau, tolak, tarik ke antrean,
  template, arsip, ubah penduduk), `kasi` (ambil & kerjakan surat; 1 akun per komputer Kasi).
  Middleware `peran:admin,pelayanan`. Hak akses per permohonan ada di `Permohonan::bolehDilihat/bolehDikerjakan/bolehDitolak`.
- Tablet Pelayanan: `Admin\PengajuanController` (pilih surat → form → `buatDenganAntrian` → layar tiket).
  `cariNik` mengembalikan JSON data penduduk untuk isi otomatis.
- Alur "orderan": status `baru` + `petugas_id` null = di antrean bersama. `Permohonan::ambilOleh()` memakai UPDATE
  atomik (`WHERE status='baru' AND petugas_id IS NULL`), jadi rebutan antar komputer hanya satu yang menang.
  Kasi hanya bisa membuka miliknya yang `diproses`. Setelah `selesai`, hilang dari daftarnya (hanya admin/pelayanan yang bisa membuka).
- Halaman awal per peran (`AuthController` + `DashboardController`): pelayanan → `admin.pengajuan.index`,
  kasi → `admin.antrean`, admin → dashboard. Cek status antrian untuk pelayanan: `admin.cek-antrian`.
- Nomor antrian: `tanggal_antrian` + `nomor_antrian` (unik berdua, mulai 1 setiap hari), dibuat `Permohonan::buatDenganAntrian()`.
  `kode` = `YYMMDD-NNN`, dipakai di nama folder arsip. Selalu query tanggal dengan `whereDate()`: Eloquent menyimpan
  `Y-m-d 00:00:00` di SQLite, dan dulu format campuran sempat menyebabkan nomor dobel.
- Seeder: `AkunPetugasSeeder` (1 pelayanan + 3 kasi, `firstOrCreate` jadi tidak menimpa password), `LayananSeeder`
  (9 layanan + template, MENIMPA template yang sudah diedit), `ContohPendudukSeeder` (data fiktif), akun admin di `DatabaseSeeder`.

## Versi Vercel (demo online)
- https://sipeka-iota.vercel.app, project Vercel `prj_X93tr0vx2oOjZKk6mpuNEJ6zDW0x` (tim `team_EvHstL2rrw30ea6ZV51MVpiC`), git-linked ke `Virtuoez/sipeka` main.
- Runtime `vercel-php@0.7.4`, region sin1. SQLite di runtime itu TERLALU TUA untuk Laravel 13 (`pragma_table_xinfo` gagal), jadi Vercel pakai Postgres Neon
  (`muddy-block-08939547`, org `org-noisy-cake-69642523`). Env Vercel: `DB_URL` = koneksi unpooled (WAJIB unpooled: `api/index.php` memakai
  `pg_advisory_lock`, yang tidak jalan lewat pooler). Kode harus kompatibel SQLite (lokal) DAN Postgres (Vercel): pakai `whereLike` untuk pencarian.
- PIN: `users.pin` (cast hashed). `AuthController::masuk` cek PIN + RateLimiter `pin:{id}` (5x / 15 menit, butuh cache
  PERSISTEN: Vercel `CACHE_STORE=database`, jangan `array`). `config('app.wajib_pin')` = env `SIPEKA_WAJIB_PIN`
  (true di vercel.json, false lokal). Password admin Vercel disinkronkan dari env `SIPEKA_ADMIN_PASSWORD` di `api/index.php`.
- Mode masuk: `config('app.mode_masuk')` = env `SIPEKA_MASUK` (`tombol` lokal / `google` Vercel). Mode google: `/login` = `auth/google.blade.php`,
  `POST /masuk/{user}` 404. `GoogleController` (Laravel Socialite) mencocokkan email Google (lower) dengan `users.email` role admin/pelayanan/kasi.
  Google OAuth TIDAK bisa di http://192.168.x.x, jadi PC kelurahan tetap mode tombol. Tes: `tests/Feature/MasukGoogleTest.php` (mock Socialite).
- Tamu (`role=tamu`, env `SIPEKA_TAMU`): `punyaPeran()` menganggap tamu = pelayanan + kasi; middleware `bukan-tamu` menutup template/ubah penduduk.
  Global scope `App\Models\Scopes\DataContoh` di Permohonan & Penduduk: tamu hanya `contoh=true`, akun lain hanya `contoh=false`
  (seeder/console pakai `withoutGlobalScope`). Unik antrian = (tanggal, nomor, contoh); kode contoh berawalan `C`; arsip contoh di `arsip-contoh/`
  (`ArsipService::root()`). Data contoh tamu dari hari sebelumnya dihapus saat tamu masuk.
- Kasi boleh membuka Arsip Berkas (permintaan pemilik).

## Kasi per bidang (permintaan pemilik, dari folder kantor `kasi pemerintahan` / `kasi kesos` / `kasi pm`)
- `Layanan::BIDANG` (pemerintahan/kesos/pm). `layanan.bidang` = Kasi yang mengerjakan; `users.bidang` = bidang akun Kasi.
  Akun: Kasi Pemerintahan, Kasi Kesos, Kasi PM (dulu Kasi PC 1/2/3; diganti nama oleh migration `kasi_per_bidang`).
- Antrean & lencana memakai scope `Permohonan::bagianKasi($user)`; `ambil()` dan `bolehDilihat()` cek `Layanan::untukKasi()`.
  Kasi tanpa bidang / layanan tanpa bidang = semua. Admin mengatur bidang di Akun Petugas & form Layanan.
- Arsip: `arsip/{Kasi Bidang}/{folder layanan}/{tahun}/{bulan}/...`.
- 19 template mengikuti format surat kantor (contoh asli di `D:\projek sekumpul\kasi *`, BERISI DATA WARGA ASLI, jangan di-commit).
  Penanda penandatangan Kasi: `{{nama_kasi_pemerintahan}}`, `{{nip_kasi_kesos}}`, dst (isi di Profil Kelurahan).
  `LayananSeeder::pasang(false)` (dipakai migration) hanya menimpa template yang md5-nya cocok `TEMPLATE_LAMA` (belum diedit admin).
  Kalau template bawaan diubah lagi: tambahkan md5 versi sekarang ke `TEMPLATE_LAMA` + buat migration yang memanggil `pasang(false)`.
- Nomor surat: `layanan.format_nomor` (mis. `{no} / KS-Pem / {bulan_romawi} / {tahun}`, `472. M / {no} / ...`, `400.9/{no}/Kel.SKP`)
  + `layanan.buku_nomor` (pemerintahan / kematian / kesos / pm). `SuratService::nomorSuratBerikutnya` = nomor TERBESAR di buku itu
  tahun ini + 1 (`ambilUrut` membaca {no} dari nomor jadi), jadi Kasi cukup sekali mengetik nomor buku kantor.
- **Surat dari file Word kantor** (keputusan pemilik: hasil harus persis surat kantor): `resources/surat/{slug}.docx` (13 jenis;
  domisili, pengantar KTP/KK, usaha belum punya contoh kantor → tetap template HTML). Dibuat dari surat asli di folder kantor
  dengan Word COM: data warga & nama/NIP pejabat diganti `${penanda}`, scan KTP dibuang, info dokumen dihapus. Skrip pembuatnya
  TIDAK di repo (berisi data asli). `SuratService::templateWord()` (unggahan admin di `storage/app/private/template-surat/` menimpa
  bawaan) → `docxDariWord()` (PhpWord TemplateProcessor; kosong → "-"). Penanda tambahan Word: `ttl_angka`, `{isian_tanggal}_angka`,
  `{isian_tanggal}_hari`, `nama_camat`/`nip_camat` (Profil Kelurahan). Layanan Word: tidak ada PDF; arsip = .docx (atau Word
  yang diedit & diunggah Kasi saat Selesai). Halaman Kasi: `admin/permohonan/surat-word.blade.php`. Tes: `SuratWordTest`.
- **Excel kantor**: `resources/surat/pindah.xlsx` (Formulir Permohonan Pindah + Surat Keterangan Pindah) dan `sktm.xlsx`
  (lembar FORM disembunyikan, lembar "SKTM print"). `templateWord()` juga mengembalikan .xlsx (urutan: unggahan, bawaan; docx dulu).
  `xlsxDariExcel()` mengganti `${penanda}` langsung di XML (sharedStrings/sheet), membuang hasil rumus lama, dan
  `fullCalcOnLoad="1"` supaya Excel menghitung ulang rumus lembar surat. `nilaiExcel()`: `p{n}_nik/nama/ket/no` dari isian
  "Anggota yang Pindah" (baris "NIK - Nama - Ket"), `jumlah_pengikut`, `nama_fasilitator_puskesos` (Profil Kelurahan).
- Isian tipe `pilihan`: `Label | pilihan: A; B; C` → select di formulir; nilai = teks pilihan, `{key}_kode` = nomor urut (kode formulir pindah).
- Contoh tampilan per template: `resources/surat/contoh/{slug}.pdf` (data fiktif, dibuat dari Word/Excel), tampil di halaman Template.
- Logo kop bawaan `resources/img/logo-kabupaten-banjar.png` (dari surat kantor) bila Profil Kelurahan belum punya logo.
- `.neon`, `neon.ts`, perubahan `package.json`/`package-lock.json` dari `neon init` sengaja TIDAK di-commit (alat lokal).

## Data sensitif
`storage/app/private/arsip` dan `database.sqlite` berisi NIK/KK warga. Jangan di-commit, jangan diunggah ke layanan luar.
