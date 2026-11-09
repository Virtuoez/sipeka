# Menyiapkan & menjalankan SIPEKA di komputer mana pun yang sudah ada Laragon.
# Dipanggil oleh jalankan.bat / jalankan-jaringan.bat. Aman dijalankan berulang kali.
param([string]$HostIp = '127.0.0.1', [int]$Port = 8000)

$ErrorActionPreference = 'Stop'
$proyek = Split-Path -Parent $PSScriptRoot
Set-Location $proyek

function Info($t) { Write-Host "[SIPEKA] $t" -ForegroundColor Green }
function Gagal($t) { Write-Host "[SIPEKA] $t" -ForegroundColor Red; Read-Host 'Tekan Enter untuk keluar'; exit 1 }

# 1. Cari PHP dari Laragon (versi berapa pun)
$phpDir = Get-ChildItem 'C:\laragon\bin\php' -Directory -ErrorAction SilentlyContinue |
    Where-Object { Test-Path (Join-Path $_.FullName 'php.exe') } | Sort-Object Name | Select-Object -Last 1
if (-not $phpDir) { Gagal 'Laragon belum terpasang. Install dulu dari https://laragon.org lalu jalankan lagi.' }
$php = Join-Path $phpDir.FullName 'php.exe'
$env:Path = "$($phpDir.FullName);C:\laragon\bin\composer;C:\laragon\bin\git\bin;$env:Path"
Info "PHP: $($phpDir.Name)"

# 2. Pastikan php.ini ada dan ekstensi yang dibutuhkan aktif
$ini = Join-Path $phpDir.FullName 'php.ini'
if (-not (Test-Path $ini)) {
    Copy-Item (Join-Path $phpDir.FullName 'php.ini-development') $ini
    Info 'php.ini dibuat.'
}
$isi = Get-Content $ini
$isi = $isi -replace '^;extension_dir = "ext"', 'extension_dir = "ext"'
foreach ($e in 'curl', 'fileinfo', 'gd', 'intl', 'mbstring', 'openssl', 'pdo_mysql', 'pdo_sqlite', 'sqlite3', 'zip', 'exif') {
    $isi = $isi -replace "^;extension=$e$", "extension=$e"
}
$isi = $isi -replace '^upload_max_filesize = .*', 'upload_max_filesize = 10M' -replace '^post_max_size = .*', 'post_max_size = 50M' -replace '^memory_limit = 128M', 'memory_limit = 512M'
[IO.File]::WriteAllLines($ini, $isi)

# 3. Library PHP (folder vendor). Hanya perlu internet kalau folder ini belum ada.
if (-not (Test-Path 'vendor\autoload.php')) {
    Info 'Memasang library (butuh internet, sekali saja)...'
    & composer install --no-interaction --no-dev --optimize-autoloader --no-progress
    if ($LASTEXITCODE) { Gagal 'composer install gagal. Cek koneksi internet.' }
}

# 4. File .env (pengaturan rahasia)
if (-not (Test-Path '.env')) {
    Copy-Item '.env.example' '.env'
    & $php artisan key:generate --force
    Info '.env dibuat.'
}

# 5. Database: kalau belum ada, buat baru + isi layanan, template, dan akun admin awal
if (-not (Test-Path 'database\database.sqlite')) {
    New-Item 'database\database.sqlite' -ItemType File | Out-Null
    & $php artisan migrate --seed --force
    Info 'Database baru dibuat.'
} else {
    & $php artisan migrate --force   # jalankan perubahan tabel terbaru kalau ada
}

# 6. Link folder logo (rusak kalau proyek disalin pakai ZIP/flashdisk)
$link = Get-Item 'public\storage' -ErrorAction SilentlyContinue
if ($link -and -not ($link.Attributes -band [IO.FileAttributes]::ReparsePoint)) {
    Remove-Item 'public\storage' -Recurse -Force
    $link = $null
}
if (-not $link) { & $php artisan storage:link }

& $php artisan optimize:clear | Out-Null

# 7. Jalankan
$env:SIPEKA_ALAMAT_LAN = ''
if ($HostIp -eq '0.0.0.0') {
    # Ambil IP jaringan lokal dari adapter yang tersambung ke router (WiFi/LAN kantor).
    # Adapter virtual (VirtualBox: tanpa gateway) dan VPN (mis. Radmin 26.x: bukan IP lokal) dilewati.
    $lokal = '^(10\.|172\.(1[6-9]|2\d|3[01])\.|192\.168\.)'
    $ip = Get-NetIPConfiguration |
        Where-Object { $_.IPv4DefaultGateway -and $_.NetAdapter.Status -eq 'Up' } |
        ForEach-Object { $_.IPv4Address.IPAddress } |
        Where-Object { $_ -match $lokal } | Select-Object -First 1
    if ($ip) {
        $env:SIPEKA_ALAMAT_LAN = "http://${ip}:$Port"   # ditampilkan di menu samping website
        Info "Buka di tablet / komputer Kasi: $env:SIPEKA_ALAMAT_LAN"
    } else {
        Info 'Komputer ini belum tersambung ke WiFi/LAN kantor.'
    }
    Info 'Kalau Windows bertanya soal Firewall untuk "Apache HTTP Server", klik Allow (centang juga Public kalau jaringannya Public).'
}

# Pengaturan dibaca sekali lalu disimpan (config cache). Wajib untuk Apache di Windows: membaca .env
# di setiap permintaan tidak aman saat banyak permintaan berjalan bersamaan. Ubah .env → jalankan ulang skrip ini.
& $php artisan config:cache | Out-Null

# Apache dari Laragon: sanggup melayani tablet + beberapa PC sekaligus.
# (Server bawaan PHP hanya melayani 1 permintaan per waktu, sehingga CSS/JS sering gagal dimuat → tampilan rusak.)
$apacheDir = Get-ChildItem 'C:\laragon\bin\apache' -Directory -ErrorAction SilentlyContinue |
    Where-Object { Test-Path (Join-Path $_.FullName 'bin\httpd.exe') } | Sort-Object Name | Select-Object -Last 1
$modPhp = Get-ChildItem $phpDir.FullName -Filter 'php*apache2_4.dll' -ErrorAction SilentlyContinue | Select-Object -First 1

if (-not $apacheDir -or -not $modPhp) {
    Info 'Apache tidak ditemukan, memakai server sederhana (bisa lambat kalau banyak perangkat).'
    Info "Buka di browser: http://127.0.0.1:$Port  (tutup jendela ini untuk mematikan website)"
    & $php artisan serve --host=$HostIp --port=$Port
    exit
}

$garis = { param($p) $p.Replace('\', '/') }
$publik = & $garis (Join-Path $proyek 'public')
$logs = & $garis (Join-Path $proyek 'storage\logs')
$kerja = Join-Path $proyek 'storage\app\apache'
New-Item -ItemType Directory -Force $kerja | Out-Null
$conf = Join-Path $kerja 'httpd.conf'

@"
# Dibuat otomatis oleh tools/siapkan.ps1 setiap kali SIPEKA dijalankan. Jangan diedit manual.
Define SRVROOT "$(& $garis $apacheDir.FullName)"
ServerRoot "`${SRVROOT}"
Listen ${HostIp}:$Port
ServerName localhost:$Port

LoadModule authz_core_module modules/mod_authz_core.so
LoadModule authz_host_module modules/mod_authz_host.so
LoadModule dir_module modules/mod_dir.so
LoadModule mime_module modules/mod_mime.so
LoadModule rewrite_module modules/mod_rewrite.so
LoadModule log_config_module modules/mod_log_config.so
LoadModule headers_module modules/mod_headers.so
LoadModule setenvif_module modules/mod_setenvif.so
LoadModule php_module "$(& $garis $modPhp.FullName)"
PHPIniDir "$(& $garis $phpDir.FullName)"

<IfModule mpm_winnt_module>
    ThreadsPerChild 64
    MaxConnectionsPerChild 0
</IfModule>
AcceptFilter http none
AcceptFilter https none
EnableSendfile off
EnableMMAP off

TypesConfig conf/mime.types
AddType application/manifest+json .webmanifest

# Bootstrap, TinyMCE, ikon jarang berubah: simpan di browser 7 hari supaya tidak diunduh ulang tiap halaman
<LocationMatch "^/(vendor|icons)/">
    Header set Cache-Control "public, max-age=604800"
</LocationMatch>

DocumentRoot "$publik"
<Directory />
    AllowOverride None
    Require all denied
</Directory>
<Directory "$publik">
    Options FollowSymLinks
    AllowOverride All
    Require all granted
</Directory>
DirectoryIndex index.php index.html
<FilesMatch "\.php$">
    SetHandler application/x-httpd-php
</FilesMatch>

ErrorLog "$logs/apache-error.log"
LogLevel warn
PidFile "$(& $garis $kerja)/httpd.pid"
"@ | Set-Content -Path $conf -Encoding ascii

# Bersihkan sisa PID dari Apache yang ditutup paksa
Remove-Item (Join-Path $kerja 'httpd.pid') -ErrorAction SilentlyContinue

# httpd menulis "Syntax OK" ke stderr; di PowerShell 5.1 itu dianggap error kalau ErrorActionPreference = Stop
$ErrorActionPreference = 'Continue'
$cek = (& (Join-Path $apacheDir.FullName 'bin\httpd.exe') -t -f $conf 2>&1 | Out-String).Trim()
if ($LASTEXITCODE) { Gagal "Pengaturan Apache bermasalah: $cek" }

Info "Server: $($apacheDir.Name)"
Info "Buka di browser: http://127.0.0.1:$Port  (tutup jendela ini untuk mematikan website)"
& (Join-Path $apacheDir.FullName 'bin\httpd.exe') -f $conf
if ($LASTEXITCODE) { Gagal "Apache gagal menyala. Mungkin port $Port sedang dipakai (jendela SIPEKA lain masih terbuka?)." }
