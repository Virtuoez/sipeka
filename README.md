# SIPEKA Sekumpul

Sistem Pelayanan Kependudukan Kelurahan Sekumpul (Kec. Martapura, Kab. Banjar, Kalimantan Selatan).

Website **lokal** untuk petugas kelurahan. Tidak online di internet: berjalan di **PC kelurahan**, lalu dibuka lewat WiFi/LAN kantor dari:

- **Tablet Pelayanan**: memilih jenis surat yang diminta warga, mengisi formulir bersama warga, mencentang berkas fisik yang dibawa, lalu memberi **nomor antrian**.
- **3 komputer Kasi**: mengambil permohonan dari antrean (seperti orderan), membuat surat dari template, lalu mengunduh **PDF** atau **Word**.

Warga tidak membuka website ini. Warga datang ke kantor dan membawa berkas asli.
Surat yang selesai **otomatis tersortir ke folder** sesuai jenis layanan, tahun, bulan, dan nama pemohon.

---

## 1. Yang perlu di-install

| Software | Untuk apa | Link |
|---|---|---|
| **Laragon** (cukup di PC kelurahan) | Berisi PHP 8.3, MySQL, Composer, Node.js | https://laragon.org |
| **VS Code** (opsional, untuk mengubah kode) | Editor kode | https://code.visualstudio.com |

Tablet dan komputer Kasi **tidak perlu install apa-apa**, cukup browser (Chrome).

### Ekstensi (plugin) VS Code yang disarankan

| Ekstensi | Fungsi |
|---|---|
| **PHP Intelephense** (Ben Mewburn) | Autocomplete & cek error PHP |
| **Laravel Blade Snippets** (Winnie Lin) | Warna & singkatan untuk file `.blade.php` (tampilan) |
| **Laravel Extra Intellisense** (amir) | Autocomplete nama route, view, config |
| **DotENV** (mikestead) | Warna untuk file `.env` |
| **Indonesian Language Pack** (opsional) | Menu VS Code berbahasa Indonesia |

### Paket PHP yang dipakai (sudah terpasang lewat Composer, tidak perlu download manual)

| Paket | Fungsi |
|---|---|
| `laravel/framework` | Framework utama website |
| `phpoffice/phpword` | Membuat file **Word (.docx)** |
| `barryvdh/laravel-dompdf` | Membuat file **PDF** |

Aset tampilan (sudah ada di `public/vendor`, tidak butuh internet): **Bootstrap 5** (tampilan), **Bootstrap Icons** (ikon), **TinyMCE 6** (editor surat seperti Word di dalam website).

---

## 2. Menjalankan di kantor

```
               WiFi / LAN kantor
   ┌──────────────┬───────────┬───────────┬───────────┐
   │              │           │           │           │
PC KELURAHAN    TABLET      PC KASI 1   PC KASI 2   PC KASI 3
(server: data   Pelayanan
 & arsip ada
 di sini)
```

1. Di **PC kelurahan**, klik dua kali **`jalankan-jaringan.bat`**. Biarkan jendela hitamnya terbuka selama jam kerja; kalau ditutup, website mati. **Cukup satu jendela**: jangan membuka `jalankan.bat` dan `jalankan-jaringan.bat` bersamaan.
2. Jendela itu menampilkan alamat, contoh `http://192.168.1.10:8000`. Alamat yang sama juga tertulis di menu samping website (bagian bawah).
3. Di **tablet** dan **komputer Kasi**, buka alamat itu di Chrome, lalu pilih peran (lihat "Cara masuk" di bawah). Simpan sebagai bookmark, atau pasang sebagai aplikasi.
4. Pertama kali, Windows mungkin bertanya soal **Firewall** untuk **"Apache HTTP Server"**. Klik **Allow**. Pastikan jenis jaringan yang sedang dipakai PC itu ikut dicentang:
   - Cek di *Settings → Network & internet → (WiFi/Ethernet) → Network profile type*. Jaringan kantor sebaiknya diubah ke **Private**.
   - Kalau tetap **Public**, centang juga *Public networks*. Kalau tidak, tablet & PC Kasi tidak bisa tersambung.

**Uji sambungan (sekali saja):** setelah `jalankan-jaringan.bat` jalan, sambungkan HP/tablet ke WiFi kantor, lalu buka alamat yang tampil di jendela hitam. Kalau muncul layar **Pelayanan / Kasi**, berarti sudah tersambung.

**Kalau tablet/HP tidak bisa membuka ("This site can't be reached"):**

| Cek | Penyebab | Solusi |
|---|---|---|
| Alamat diketik persis `http://192.168.x.x:8000`? | Salah ketik (mis. `htpp`) membuat browser mencari di Google | Ketik ulang lengkap dengan `http://` |
| iPhone/iPad memakai Chrome? | iOS butuh izin jaringan lokal | Pengaturan → Chrome → nyalakan **Jaringan Lokal**, atau pakai Safari |
| IP tablet beda awalan dengan PC (mis. `192.168.0.x` vs `192.168.1.x`)? | Tersambung ke WiFi/router lain | Sambungkan ke WiFi dari router yang sama dengan PC kelurahan |
| IP sudah seawalan tapi tetap gagal? | Router menyalakan **AP/Client Isolation** (perangkat WiFi dilarang berhubungan dengan perangkat lain). Umum di WiFi kos/umum | Buka pengaturan router (biasanya `http://192.168.1.1`, login di stiker router), cari menu *Wireless → Advanced*: matikan **AP Isolation / Client Isolation / Wireless Isolation**. Kalau router bukan milik sendiri, minta ke pemiliknya / teknisi |
| Belum bisa mengubah router? | | Coba sementara pakai **hotspot HP**: sambungkan PC kelurahan & tablet ke hotspot yang sama |

| File | Dipakai kapan |
|---|---|
| `jalankan-jaringan.bat` | **Di PC kelurahan.** Tablet & komputer Kasi bisa membuka. |
| `jalankan.bat` | Di laptop/kos untuk mencoba-coba. Hanya bisa dibuka dari laptop itu sendiri. |

Saat pertama kali dijalankan di komputer baru, skrip otomatis menyiapkan semuanya (PHP, library, database, akun awal).

Website dijalankan dengan **Apache** (sudah ikut di Laragon), yang sanggup melayani tablet + beberapa PC Kasi sekaligus. Kalau Apache tidak ditemukan, skrip memakai server sederhana bawaan PHP. Server itu hanya melayani satu permintaan dalam satu waktu, sehingga tampilan bisa rusak kalau banyak perangkat dipakai bersamaan.

> Setelah mengubah file `.env`, **tutup lalu jalankan ulang** `jalankan-jaringan.bat`. Pengaturan hanya dibaca sekali saat website dinyalakan.

### Pasang sebagai aplikasi (PC & tablet)

SIPEKA bisa dipasang seperti aplikasi: punya ikon sendiri, terbuka di jendela sendiri tanpa address bar, dan menampilkan pesan *"Tidak tersambung ke PC Kelurahan"* kalau WiFi putus atau PC kelurahan mati. **Tidak perlu hosting**, dan data tetap hanya ada di PC kelurahan.

Browser hanya mengizinkan "pasang aplikasi" untuk alamat aman. Alamat jaringan kantor (`http://192.168.x.x`) harus **diizinkan sekali** di setiap tablet / komputer Kasi:

**Tablet Android / komputer Kasi (Chrome):**
1. Buka Chrome, ketik di address bar: `chrome://flags/#unsafely-treat-insecure-origin-as-secure`
2. Pada kotak **Insecure origins treated as secure**, isi alamat PC kelurahan, contoh `http://192.168.1.10:8000`, lalu ubah pilihannya ke **Enabled**.
3. Tekan **Relaunch** (Chrome dibuka ulang).
4. Buka alamat SIPEKA, lalu tekan tombol hijau **Pasang Aplikasi** (di layar pilih peran atau di pojok kanan atas).
   Kalau tombolnya tidak muncul: menu Chrome (⋮) → **Pasang aplikasi** / **Tambahkan ke layar utama**.
5. Ikon **SIPEKA** muncul di layar utama tablet / desktop PC. Buka dari ikon itu mulai sekarang.

**PC kelurahan (server):** buka `http://localhost:8000` di Chrome/Edge, lalu klik **Pasang Aplikasi**. Tidak perlu langkah flags.

> Kalau alamat IP PC kelurahan berubah, langkah 2 harus diulang dengan alamat baru. Karena itu, IP-nya sebaiknya dibuat tetap (lihat tips di bawah).

> **Tips:** minta IP PC kelurahan dibuat **tetap** (atur *DHCP reservation* di router, atau IP statis di Windows). Kalau IP berubah, bookmark di tablet & komputer Kasi harus diganti.

### Cara masuk (tanpa login ribet)

Buka alamat SIPEKA, lalu muncul **2 tombol besar**:

| Tombol | Dipakai di | Setelah ditekan |
|---|---|---|
| **Pelayanan** | Tablet meja depan | Langsung masuk ke halaman pilih jenis surat |
| **Kasi** | Komputer Kasi | Pilih **Kasi PC 1 / PC 2 / PC 3**, lalu masuk ke Antrean Surat |

- Pilihan ini **diingat di perangkat itu**. Besok cukup buka aplikasinya, langsung masuk tanpa memilih lagi.
- Untuk mengganti: klik nama di pojok kanan atas → **Ganti Peran**.
- Tiap komputer Kasi harus memilih **PC yang berbeda**. Sistem perlu tahu permohonan sedang dipegang komputer yang mana (supaya hilang dari komputer lain). Kalau sebuah PC sedang dibuka di perangkat lain, tombolnya ditandai *"sedang dipakai"*.
- **Admin** tetap memakai email + password (link kecil *"Masuk sebagai Admin"*), karena Admin bisa menghapus data, mengubah pengaturan, dan mengatur akun. Email & password awal Admin ada di `database/seeders/DatabaseSeeder.php`. **Segera ganti** lewat menu *Ganti Password*.
- Menambah komputer Kasi ke-4: masuk sebagai Admin → **Akun Petugas** → isi nama, misalnya "Kasi PC 4", lalu pilih peran Kasi. Tidak perlu email/password, tombolnya langsung muncul di layar pilihan.

> **Penting:** karena tanpa password, **siapa pun yang tersambung ke WiFi kantor** bisa membuka SIPEKA sebagai Pelayanan/Kasi dan melihat NIK/KK warga. Pakai WiFi kantor yang **berpassword**, jangan dibagikan ke pengunjung (buat WiFi tamu terpisah kalau perlu).

### Hak akses tiap peran

| Menu / tindakan | Admin | Pelayanan | Kasi |
|---|:-:|:-:|:-:|
| Buat Permohonan: pilih surat, isi formulir, beri nomor antrian | ✔ | ✔ | |
| Cek Antrian: ketik nomor → langsung tahu statusnya | ✔ | ✔ | |
| Antrean Surat: ambil & kerjakan surat | ✔ | | ✔ |
| Semua Permohonan: pantau, lihat siapa yang mengerjakan | ✔ | ✔ | |
| Tolak permohonan | ✔ | ✔ | hanya miliknya |
| Tarik paksa permohonan ke antrean (misal komputer Kasi rusak) | ✔ | ✔ | |
| Revisi surat yang sudah selesai | ✔ | ✔ | |
| Arsip Berkas | ✔ | ✔ | |
| Layanan & Template surat | ✔ | ✔ | |
| Data Penduduk: lihat | ✔ | ✔ | ✔ |
| Data Penduduk: tambah/ubah/import/export | ✔ | ✔ | |
| Dashboard, Profil Kelurahan, Akun Petugas, hapus data | ✔ | | |

### Reset database (hapus semua data, isi ulang data contoh)

```bash
php artisan migrate:fresh --seed
```

---

## 3. Alur kerja

```
TABLET PELAYANAN                   KOMPUTER KASI 1 / 2 / 3                PELAYANAN (menu Cek Antrian)
────────────────                   ───────────────────────                ────────────────────────────
Warga datang membawa berkas asli
Petugas pilih jenis surat
Centang berkas yang dibawa
Ketik NIK → data diri terisi otomatis
(dari Data Penduduk), cocokkan KTP
Isi keperluan
        │
        ▼
NOMOR ANTRIAN untuk warga ─────►  Muncul di "Antrean Belum Diambil"      Warga bertanya "nomor 15
(tanggal + jam + nomor,           di ketiga komputer Kasi                 sudah sampai mana?"
 mulai dari 1 setiap hari)               │                               → ketik 15, Enter
                                Kasi PC 2 klik "Ambil"                   → "Sedang diproses oleh
                                → HILANG dari Kasi PC 1 & 3                 Kasi PC 2 sejak 10:20"
                                → hanya Kasi PC 2 yang bisa buka
                                        │                               Bisa "Tarik ke Antrean"
                                ├─ Tolak (isi alasan)                    kalau Kasi PC 2 berhalangan
                                └─ Buat Surat (otomatis dari template)
                                   edit, isi nomor & tanggal surat
                                   "Selesai & Arsipkan"
                                   → PDF + Word masuk folder arsip
                                   → HILANG dari daftar Kasi PC 2        Bisa cetak ulang dari Arsip
                                Cetak, tanda tangan, cap
                                → panggil nomor antrian warga
```

- Berkas **wajib** harus dicentang sebelum bisa disimpan. Kalau warga belum membawanya, minta dilengkapi dulu.
- Kalau NIK belum ada di Data Penduduk, data diri diisi manual. Setelah itu bisa disimpan ke Data Penduduk lewat halaman detail permohonan.
- Kalau dua komputer Kasi menekan **Ambil** di detik yang sama, hanya satu yang berhasil. Yang lain mendapat pesan "sudah diambil oleh Kasi PC ...".
- Daftar antrean di komputer Kasi diperbarui otomatis tiap 10 detik, daftar di Cek Antrian tiap 15 detik.
- Kasi yang belum sempat menyelesaikan bisa klik **Kembalikan ke Antrean** supaya diambil komputer lain.
- Di menu **Semua Permohonan**, mengetik angka pendek (misal `15`) langsung mencari nomor antrian.

---

## 4. Folder arsip otomatis

Surat yang selesai disimpan di `storage/app/private/arsip/` (di PC kelurahan) dengan susunan:

```
arsip/
├── 01 - Keterangan Domisili/
│   └── 2026/
│       └── 11 - November/
│           └── 261108-007_AHMAD_FAUZI/        ← tanggal (YYMMDD) - nomor antrian _ nama
│               ├── SURAT_Surat_Keterangan_Domisili_AHMAD_FAUZI.pdf
│               └── SURAT_Surat_Keterangan_Domisili_AHMAD_FAUZI.docx
├── 02 - SKTM/
├── 03 - Keterangan Usaha/
└── ...
```

- Nama folder layanan bisa diubah di menu **Layanan & Template**.
- Di menu **Arsip Berkas**, buka folder lalu klik **Unduh folder ini (ZIP)**. Contoh: unduh folder bulan November untuk mencetak semua surat sekaligus.

---

## 5. Mengedit template surat

Menu **Layanan & Template** → pilih layanan → **Template**.

- Editornya mirip Word: bisa tebal/miring, rata kiri-kanan, tabel, gambar, ukuran huruf.
- Klik **penanda** di panel kanan untuk menyisipkan data otomatis, misalnya `{{nama}}`, `{{nik}}`, `{{ttl}}`, `{{alamat}}`, `{{keperluan}}`.
- `{{kop_surat}}` diganti kop resmi (logo + nama kelurahan). Kop diatur di menu **Profil Kelurahan**.
- Klik **Pratinjau PDF** / **Pratinjau Word** untuk melihat hasil dengan data contoh.

Untuk satu surat tertentu, Kasi juga bisa mengedit langsung di halaman **Buat Surat** tanpa mengubah template aslinya.

### Menambah jenis layanan baru

**Layanan & Template** → **Tambah Layanan**:

- **Berkas yang Harus Dibawa Warga**: satu per baris, muncul sebagai daftar centang di tablet. Tambah `(opsional)` jika tidak wajib.
- **Isian Tambahan**: format `Label | tipe` (tipe: `teks`, `paragraf`, `tanggal`, `angka`).
  Contoh `Nama Almarhum | teks` → penandanya `{{nama_almarhum}}`.

---

## 6. Fitur lengkap

**Pelayanan (tablet)**
- **Buat Permohonan**: pilihan jenis surat beserta daftar berkas yang harus dibawa
- Formulir besar ramah tablet, **isi otomatis dari NIK**, daftar centang berkas fisik
- Layar **nomor antrian** besar untuk ditunjukkan ke warga (bisa dicetak)
- **Cek Antrian**: ketik nomor → status langsung terlihat, plus daftar & ringkasan antrian hari ini
- Semua Permohonan: cari/filter per tanggal, lihat siapa yang mengerjakan, tolak, tarik ke antrean, unduh PDF/Word/ZIP

**Kasi**
- Antrean Surat: ambil permohonan seperti orderan, kerjakan, selesai lalu hilang dari daftar
- Lihat berkas fisik yang sudah dicek Pelayanan & siapa yang mengeceknya
- Editor surat + unduh PDF/Word

**Umum**
- Pencocokan NIK dengan data penduduk + tombol "Tambahkan ke Data Penduduk"
- Data Penduduk: tambah/ubah, lihat anggota keluarga (per No. KK), riwayat surat, **import & export CSV (Excel)**
- Arsip Berkas: penjelajah folder + unduh ZIP

**Khusus admin**
- Dashboard: jumlah permohonan per status/layanan, antrian hari ini, beban kerja tiap komputer Kasi, statistik penduduk per RT
- Profil Kelurahan: kop, logo, nama lurah/NIP, format nomor surat, ukuran kertas A4/F4
- Akun Petugas (peran *Admin*, *Pelayanan*, atau *Kasi*)

---

## 7. Struktur kode (untuk presentasi)

Laravel memakai pola **MVC** (Model – View – Controller):

| Bagian | Lokasi | Isi |
|---|---|---|
| **Model** (data) | `app/Models/` | `Penduduk`, `Layanan`, `Permohonan`, `Berkas`, `Pengaturan`, `User` |
| **View** (tampilan) | `resources/views/` | `admin/pengajuan/` (tablet), `admin/permohonan/` (antrean, detail, surat), `layouts/` (kerangka halaman) |
| **Controller** (logika) | `app/Http/Controllers/Admin/` | `PengajuanController` (tablet), `PermohonanController` (antrean & surat), dll. |
| **Service** | `app/Services/` | `SuratService` (isi template → PDF/Word), `ArsipService` (atur folder) |
| **Route** (alamat URL) | `routes/web.php` | Daftar semua halaman |
| **Database** | `database/migrations/` | Struktur tabel |
| **Data awal** | `database/seeders/` | Layanan + template bawaan, akun petugas, penduduk contoh |

Tabel database: `users`, `penduduk`, `layanan`, `permohonan`, `berkas` (unggahan versi lama), `pengaturan`.

---

## 8. Pindah ke komputer lain (PC kelurahan / laptop)

1. Di komputer tujuan, install **Laragon** (https://laragon.org). Cukup install saja, tidak perlu disetel.
2. Salin **seluruh folder `sekumpul`** (flashdisk / ZIP) ke komputer tujuan, misalnya `D:\sekumpul`.
3. Klik dua kali `jalankan-jaringan.bat` (di kantor) atau `jalankan.bat` (di laptop). Selesai.

Yang ikut terbawa saat menyalin folder:

| Isi | Lokasi | Catatan |
|---|---|---|
| Kode website | seluruh folder | |
| Library PHP | `vendor/` | Kalau tidak ikut tersalin, skrip mengunduhnya otomatis (butuh internet). |
| **Data** (penduduk, permohonan, template, akun) | `database/database.sqlite` | Kalau file ini tidak dibawa, komputer tujuan mulai dengan database baru yang kosong. |
| **Surat yang sudah jadi** | `storage/app/private/arsip/` | |
| Pengaturan rahasia | `.env` | Bawa juga supaya sesi login & enkripsi tetap sama. |

> **Hati-hati:** `database.sqlite` dan folder `arsip` berisi NIK/KK warga. Jangan ditaruh di Google Drive publik / grup WA. Kalau pakai flashdisk, hapus setelah selesai menyalin.

**PC kelurahan adalah "server utama".** Pengerjaan di laptop sebaiknya cuma untuk mengubah **kode/tampilan**. Saat memindahkan perubahan ke PC kelurahan, salin semua **kecuali** `database/database.sqlite`, `storage/app/private/arsip/`, dan `.env` milik PC kelurahan. Kalau ikut tertimpa, data asli di kantor bisa hilang.

## 8b. Versi online di Vercel (demo)

- Alamat: **https://sipeka-iota.vercel.app** (project Vercel `sipeka`, tim Faiq). Setiap push ke branch `main` di GitHub `Virtuoez/sipeka` otomatis dideploy.
- Server di **Singapura** (`sin1`). Database **Postgres Neon** (project `muddy-block-08939547`, branch `production`, region Singapura).
- File khusus Vercel: `vercel.json` dan `api/index.php`. Saat pertama jalan, tabel dibuat otomatis, dan data awal diisi **hanya kalau database masih kosong**.
- Variabel rahasia di Vercel (Settings → Environment Variables): `APP_KEY`, `DB_URL` (koneksi langsung ke Neon), `DATABASE_URL` (lewat pooler).
- Di laptop, `neon init` menambahkan `DATABASE_URL` ke `.env`. SIPEKA lokal **tetap memakai SQLite**, karena yang dibaca Laravel adalah `DB_URL`/`DB_CONNECTION`.

- **PIN wajib** di versi Vercel (`SIPEKA_WAJIB_PIN=true` di `vercel.json`): akun Pelayanan/Kasi tanpa PIN tidak bisa masuk.
- **Password admin versi Vercel** diatur lewat variabel rahasia `SIPEKA_ADMIN_PASSWORD` (Settings → Environment Variables, minimal 8 karakter, lalu Redeploy). Password bawaan di seeder tidak dipakai lagi setelah itu.

**Langkah awal versi online:** isi `SIPEKA_ADMIN_PASSWORD` → Redeploy → *Masuk sebagai Admin* → **Akun Petugas** → atur PIN untuk Admin Pelayanan & Kasi PC 1/2/3.

**Masih perlu sebelum data warga asli:** surat PDF/Word & logo disimpan di `/tmp` Vercel, jadi bisa hilang. Perlu **Vercel Blob**.

### PIN (PC kelurahan & Vercel)
- Diatur Admin di **Akun Petugas**: 4–6 angka per akun. Bisa diganti atau dihapus.
- Akun ber-PIN: setelah menekan tombolnya, muncul kotak PIN. Perangkat tetap diingat, jadi PIN cukup sekali per perangkat.
- **Salah 5 kali → akun dikunci 15 menit.** Admin menyimpan PIN baru untuk membuka kuncinya.
- Di PC kelurahan PIN **opsional**: akun tanpa PIN tetap langsung masuk seperti biasa.

## 9. Pindah ke MySQL (opsional)

Default-nya memakai **SQLite** (file `database/database.sqlite`), jadi tidak perlu menyalakan MySQL. Untuk MySQL:

1. Buka Laragon → **Start All**.
2. Buat database `sekumpul` (Laragon → Database, atau HeidiSQL).
3. Ubah `.env`:
   ```
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=sekumpul
   DB_USERNAME=root
   DB_PASSWORD=
   ```
4. Jalankan `php artisan migrate --seed`.

---

## 10. Sebelum dipakai sungguhan

- [ ] Ganti password akun **Admin**.
- [ ] Di tiap perangkat, pilih peran sekali: tablet → Pelayanan, komputer Kasi → Kasi PC 1 / 2 / 3 (masing-masing beda).
- [ ] Isi **Profil Kelurahan**: nama lurah, NIP, alamat, telepon, upload logo Kabupaten Banjar.
- [ ] Hapus **data penduduk contoh** (keterangan "DATA CONTOH"), lalu import data asli supaya isi otomatis dari NIK bisa dipakai.
- [ ] Cek setiap template surat dan sesuaikan dengan format resmi kelurahan.
- [ ] Atur IP PC kelurahan supaya **tetap** (lihat tips di bagian 2).
- [ ] Pasang SIPEKA sebagai aplikasi di tablet & 3 komputer Kasi (bagian 2, "Pasang sebagai aplikasi").
- [ ] Pakai WiFi kantor yang **berpassword** dan tidak dibagikan ke pengunjung, karena website ini menyimpan NIK dan KK (data pribadi, dilindungi UU PDP).
- [ ] Di `.env` PC kelurahan: `APP_DEBUG=false`.
- [ ] Rutin **backup** folder `storage/app/private/arsip` dan file `database/database.sqlite` (misal tiap Jumat ke flashdisk khusus).
