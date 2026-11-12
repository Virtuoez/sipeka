<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Layanan;
use App\Models\User;
use App\Services\ArsipService;
use App\Services\GoogleDrive;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/** Menu admin "Google Drive": sambungkan akun Google kantor sebagai tempat simpan arsip surat. */
class DriveController extends Controller
{
    public function index()
    {
        $folderKasi = [];
        if (GoogleDrive::terhubung()) {
            foreach (Layanan::BIDANG as $k => $label) {
                $folderKasi[$k] = [
                    'label' => $label,
                    'id' => DB::table('drive_folder')->where('path', ArsipService::ROOT.'/'.$label)->value('drive_id'),
                    'email' => $this->emailKasi($k),
                ];
            }
        }

        return view('admin.drive', [
            'siap' => GoogleDrive::siap(),
            'terhubung' => GoogleDrive::terhubung(),
            'email' => GoogleDrive::email(),
            'root' => \App\Models\Pengaturan::ambil('drive_root'),
            'redirect' => GoogleDrive::urlRedirect(),
            'jumlah' => DB::table('arsip_file')->where('path', 'like', ArsipService::ROOT.'/%')->count(),
            'tersalin' => DB::table('arsip_file')->where('path', 'like', ArsipService::ROOT.'/%')->whereNotNull('drive_id')->count(),
            'folderKasi' => $folderKasi,
        ]);
    }

    public function sambung(Request $request)
    {
        abort_unless(GoogleDrive::siap(), 404);
        $state = Str::random(40);
        $request->session()->put('drive_state', $state);

        return redirect()->away(GoogleDrive::urlIzin($state));
    }

    public function kembali(Request $request, GoogleDrive $drive)
    {
        $state = $request->session()->pull('drive_state');
        if (! $state || ! hash_equals($state, (string) $request->query('state'))) {
            return redirect()->route('admin.drive')->withErrors(['drive' => 'Sesi penyambungan kedaluwarsa. Klik Sambungkan lagi.']);
        }
        if ($request->filled('error') || ! $request->filled('code')) {
            return redirect()->route('admin.drive')->withErrors(['drive' => 'Penyambungan dibatalkan di halaman Google.']);
        }

        try {
            $drive->sambungkan((string) $request->query('code'));
        } catch (Throwable $e) {
            report($e);

            return redirect()->route('admin.drive')->withErrors(['drive' => $e->getMessage()]);
        }

        return redirect()->route('admin.drive')->with('ok', 'Google Drive tersambung. Surat yang selesai mulai sekarang otomatis tersimpan di Drive.');
    }

    public function sinkron(ArsipService $arsip)
    {
        abort_unless(GoogleDrive::terhubung(), 404);
        [$ok, $gagal, $hilang, $sisa] = $arsip->sinkronkan();

        $pesan = "$ok file disalin ke Google Drive.";
        if ($hilang) {
            $pesan .= " $hilang file lama sudah tidak ada di server, jadi dilewati.";
        }
        if ($gagal) {
            return back()->withErrors(['drive' => $pesan.' Ada yang gagal diunggah, coba lagi sebentar lagi.']);
        }

        return back()->with('ok', $pesan.($sisa ? " Masih $sisa file lagi, klik Salin lagi." : ''));
    }

    /** Bagikan folder "Kasi …" di Drive ke akun Google Kasi bidang itu (akses lihat & unduh). */
    public function bagikan(GoogleDrive $drive)
    {
        abort_unless(GoogleDrive::terhubung(), 404);
        $hasil = [];
        try {
            foreach (Layanan::BIDANG as $k => $label) {
                $emails = $this->emailKasi($k);
                if (! $emails) {
                    continue;
                }
                $folder = $drive->folder(ArsipService::ROOT.'/'.$label);
                foreach ($emails as $e) {
                    $hasil[] = $drive->bagikan($folder, $e) ? "$label → $e" : "$label → $e (gagal)";
                }
            }
        } catch (Throwable $e) {
            report($e);

            return back()->withErrors(['drive' => $e->getMessage()]);
        }

        return $hasil
            ? back()->with('ok', 'Folder dibagikan: '.implode(', ', $hasil).'. Kasi akan menerima email dari Google.')
            : back()->withErrors(['drive' => 'Belum ada akun Kasi dengan email Google. Isi email Google Kasi di menu Akun Petugas dulu.']);
    }

    public function putus(GoogleDrive $drive)
    {
        $drive->putuskan();

        return back()->with('ok', 'Google Drive diputus. File yang sudah ada di Drive TIDAK dihapus.');
    }

    /** Email Google asli akun Kasi bidang ini (email buatan otomatis @sekumpul.local dilewati). */
    private function emailKasi(string $bidang): array
    {
        return User::where('role', 'kasi')->where('bidang', $bidang)->pluck('email')
            ->filter(fn ($e) => $e && ! str_ends_with($e, '.local'))->values()->all();
    }
}
