<?php

namespace App\Services;

use App\Models\Pengaturan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Salinan arsip surat di Google Drive akun kantor (REST API langsung, tanpa paket google/apiclient yang besar).
 *
 * Izin yang diminta hanya `drive.file`: website cuma bisa melihat file/folder yang DIA BUAT sendiri,
 * bukan isi Drive lainnya. Token disimpan terenkripsi (APP_KEY) di tabel pengaturan.
 * Struktur di Drive sama dengan folder arsip: {Folder Utama}/Kasi Bidang/Layanan/Tahun/Bulan/Kode_Nama/file.
 */
class GoogleDrive
{
    public const SCOPE = 'https://www.googleapis.com/auth/drive.file openid email';

    private const API = 'https://www.googleapis.com/drive/v3';

    private const UPLOAD = 'https://www.googleapis.com/upload/drive/v3';

    private const FOLDER = 'application/vnd.google-apps.folder';

    public static function siap(): bool
    {
        return filled(config('services.google.client_id')) && filled(config('services.google.client_secret'));
    }

    public static function terhubung(): bool
    {
        return filled(Pengaturan::ambil('drive_token')) && filled(Pengaturan::ambil('drive_root'));
    }

    public static function email(): ?string
    {
        return Pengaturan::ambil('drive_email') ?: null;
    }

    public static function urlRedirect(): string
    {
        return route('admin.drive.kembali');
    }

    public static function urlIzin(string $state): string
    {
        return 'https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query([
            'client_id' => config('services.google.client_id'),
            'redirect_uri' => self::urlRedirect(),
            'response_type' => 'code',
            'scope' => self::SCOPE,
            'access_type' => 'offline',
            'prompt' => 'consent',   // supaya Google selalu memberi refresh token
            'include_granted_scopes' => 'true',
            'state' => $state,
        ]);
    }

    /** Tukar kode dari Google dengan token, lalu buat folder utama di Drive. */
    public function sambungkan(string $kode): void
    {
        $r = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'code' => $kode,
            'client_id' => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'redirect_uri' => self::urlRedirect(),
            'grant_type' => 'authorization_code',
        ]);
        if (! $r->successful() || ! $r->json('refresh_token')) {
            throw new RuntimeException('Google tidak memberi izin penyimpanan. Coba lagi.');
        }
        if (! str_contains((string) $r->json('scope'), 'drive.file')) {
            throw new RuntimeException('Centang izin "Lihat, edit, buat, dan hapus file Google Drive yang digunakan dengan aplikasi ini" lalu coba lagi.');
        }

        $akses = $r->json('access_token');
        $email = Http::withToken($akses)->get('https://www.googleapis.com/oauth2/v3/userinfo')->json('email');

        // Akun berbeda = folder lama tidak bisa dibuka lagi, mulai dari awal
        $this->lupakanFolder();
        Pengaturan::simpan(['drive_token' => Crypt::encryptString($r->json('refresh_token')), 'drive_email' => $email, 'drive_root' => null]);
        Cache::put('drive:akses', $akses, now()->addSeconds(max(60, (int) $r->json('expires_in', 3600) - 120)));

        $nama = 'SIPEKA - Arsip Kelurahan '.Pengaturan::ambil('nama_kelurahan');
        $root = $this->buatFolder($nama, null);
        Pengaturan::simpan(['drive_root' => $root]);
    }

    /** Cabut izin di Google dan lupakan semua id Drive (file di Drive TIDAK dihapus). */
    public function putuskan(): void
    {
        $token = Pengaturan::ambil('drive_token');
        if ($token) {
            try {
                Http::asForm()->post('https://oauth2.googleapis.com/revoke', ['token' => Crypt::decryptString($token)]);
            } catch (\Throwable $e) {
                report($e);
            }
        }
        $this->lupakanFolder();
        Pengaturan::simpan(['drive_token' => null, 'drive_email' => null, 'drive_root' => null]);
    }

    private function lupakanFolder(): void
    {
        Cache::forget('drive:akses');
        DB::table('drive_folder')->delete();
        DB::table('arsip_file')->update(['drive_id' => null]);
    }

    private function akses(): string
    {
        return Cache::remember('drive:akses', now()->addMinutes(50), function () {
            $r = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'client_id' => config('services.google.client_id'),
                'client_secret' => config('services.google.client_secret'),
                'refresh_token' => Crypt::decryptString((string) Pengaturan::ambil('drive_token')),
                'grant_type' => 'refresh_token',
            ]);
            if (! $r->successful()) {
                throw new RuntimeException('Izin Google Drive kedaluwarsa atau dicabut. Admin perlu menyambungkan ulang di menu Google Drive.');
            }

            return $r->json('access_token');
        });
    }

    private function http()
    {
        return Http::withToken($this->akses())->timeout(60)->retry(2, 500, throw: false);
    }

    private function buatFolder(string $nama, ?string $induk): string
    {
        $r = $this->http()->post(self::API.'/files?fields=id', array_filter([
            'name' => $nama,
            'mimeType' => self::FOLDER,
            'parents' => $induk ? [$induk] : null,
        ]));
        if (! $r->successful()) {
            throw new RuntimeException('Gagal membuat folder di Google Drive: '.$r->json('error.message', $r->status()));
        }

        return $r->json('id');
    }

    /** Id folder Drive untuk path arsip (mis. "arsip/Kasi Kesos/09 - SKTM/2026"), dibuat bila belum ada. */
    public function folder(string $path): string
    {
        $path = trim($path, '/');
        if ($path === ArsipService::ROOT) {
            return (string) Pengaturan::ambil('drive_root');
        }
        $ada = DB::table('drive_folder')->where('path', $path)->value('drive_id');
        if ($ada) {
            return $ada;
        }

        $id = $this->buatFolder(basename($path), $this->folder(dirname($path)));
        DB::table('drive_folder')->insert(['path' => $path, 'drive_id' => $id, 'created_at' => now(), 'updated_at' => now()]);

        return $id;
    }

    /** Unggah (atau timpa) file. Mengembalikan id file di Drive. */
    public function unggah(string $path, string $isi, ?string $idLama = null): string
    {
        $mime = SuratService::MIME[strtolower(pathinfo($path, PATHINFO_EXTENSION))] ?? 'application/octet-stream';

        if ($idLama) {
            $r = $this->http()->withBody($isi, $mime)->patch(self::UPLOAD."/files/$idLama?uploadType=media&fields=id");
            if ($r->successful()) {
                return $r->json('id');
            }
            // File di Drive sudah dihapus orang: buat baru
        }

        $batas = 'sipeka'.bin2hex(random_bytes(8));
        $meta = json_encode(['name' => basename($path), 'parents' => [$this->folder(dirname($path))]]);
        $badan = "--$batas\r\nContent-Type: application/json; charset=UTF-8\r\n\r\n$meta\r\n"
            ."--$batas\r\nContent-Type: $mime\r\n\r\n$isi\r\n--$batas--";

        $r = $this->http()->withBody($badan, "multipart/related; boundary=$batas")
            ->post(self::UPLOAD.'/files?uploadType=multipart&fields=id');
        if (! $r->successful()) {
            throw new RuntimeException('Gagal mengunggah ke Google Drive: '.$r->json('error.message', $r->status()));
        }

        return $r->json('id');
    }

    public function unduh(string $id): ?string
    {
        $r = $this->http()->get(self::API."/files/$id?alt=media");

        return $r->successful() ? $r->body() : null;
    }

    /** Pindahkan ke Sampah Drive (masih bisa dipulihkan 30 hari). */
    public function buang(string $id): void
    {
        $this->http()->patch(self::API."/files/$id", ['trashed' => true]);
    }

    /** Beri akses "lihat" folder ke email (akun Google Kasi). */
    public function bagikan(string $folderId, string $email): bool
    {
        return $this->http()->post(self::API."/files/$folderId/permissions?sendNotificationEmail=true", [
            'type' => 'user',
            'role' => 'reader',
            'emailAddress' => $email,
        ])->successful();
    }

    public static function tautanFolder(string $id): string
    {
        return 'https://drive.google.com/drive/folders/'.$id;
    }
}
