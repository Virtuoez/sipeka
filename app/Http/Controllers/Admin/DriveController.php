<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Layanan;
use App\Services\ArsipService;
use App\Services\GoogleDrive;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * Menu "Google Drive": tiap Kasi menyambungkan akun Google-nya sendiri, dan arsip surat bidangnya
 * otomatis tersimpan di Drive Kasi itu. Kasi hanya mengatur bidangnya sendiri; Admin bisa melihat
 * & mengatur ketiganya.
 */
class DriveController extends Controller
{
    /** Bidang yang boleh diatur akun ini. */
    private function bidangBoleh(Request $request): array
    {
        $u = $request->user();
        if ($u->isAdmin()) {
            return array_keys(Layanan::BIDANG);
        }

        return $u->bidang && isset(Layanan::BIDANG[$u->bidang]) ? [$u->bidang] : [];
    }

    private function drive(Request $request, string $bidang): GoogleDrive
    {
        abort_unless(in_array($bidang, $this->bidangBoleh($request), true), 403, 'Ini bukan Drive bidang Anda.');

        return GoogleDrive::untuk($bidang);
    }

    public function index(Request $request)
    {
        $daftar = [];
        foreach ($this->bidangBoleh($request) as $b) {
            $d = GoogleDrive::untuk($b);
            $baris = DB::table('arsip_file')->where('path', 'like', $d->pathAkar().'/%')->get()
                ->filter(fn ($r) => str_starts_with($r->path, $d->pathAkar().'/'));
            $daftar[$b] = [
                'label' => $d->label(),
                'terhubung' => $d->terhubung(),
                'email' => $d->email(),
                'akar' => $d->idAkar(),
                'jumlah' => $baris->count(),
                'tersalin' => $baris->whereNotNull('drive_id')->count(),
            ];
        }

        return view('admin.drive', [
            'siap' => GoogleDrive::siap(),
            'daftar' => $daftar,
            'redirect' => GoogleDrive::urlRedirect(),
        ]);
    }

    public function sambung(Request $request, string $bidang)
    {
        $this->drive($request, $bidang);
        abort_unless(GoogleDrive::siap(), 404);
        $state = Str::random(40);
        $request->session()->put('drive_state', ['kode' => $state, 'bidang' => $bidang]);

        // Kasi: sarankan akun Google yang dipakai masuk ke website
        $email = $request->user()->isKasi() && ! str_ends_with((string) $request->user()->email, '.local') ? $request->user()->email : null;

        return redirect()->away(GoogleDrive::urlIzin($state, $email));
    }

    public function kembali(Request $request)
    {
        $simpan = $request->session()->pull('drive_state');
        if (! is_array($simpan) || ! hash_equals($simpan['kode'], (string) $request->query('state'))) {
            return redirect()->route('admin.drive')->withErrors(['drive' => 'Sesi penyambungan kedaluwarsa. Klik Sambungkan lagi.']);
        }
        if ($request->filled('error') || ! $request->filled('code')) {
            return redirect()->route('admin.drive')->withErrors(['drive' => 'Penyambungan dibatalkan di halaman Google.']);
        }
        $drive = $this->drive($request, $simpan['bidang']);

        try {
            $drive->sambungkan((string) $request->query('code'));
        } catch (Throwable $e) {
            report($e);

            return redirect()->route('admin.drive')->withErrors(['drive' => $e->getMessage()]);
        }

        return redirect()->route('admin.drive')->with('ok', "Google Drive {$drive->label()} tersambung ke {$drive->email()}. Surat bidang ini yang selesai otomatis tersimpan di sana.");
    }

    public function sinkron(Request $request, string $bidang, ArsipService $arsip)
    {
        $drive = $this->drive($request, $bidang);
        abort_unless($drive->terhubung(), 404);
        [$ok, $gagal, $hilang, $sisa] = $arsip->sinkronkan($drive);

        $pesan = "$ok file disalin ke Google Drive {$drive->label()}.";
        if ($hilang) {
            $pesan .= " $hilang file lama sudah tidak ada di server, jadi dilewati.";
        }
        if ($gagal) {
            return back()->withErrors(['drive' => $pesan.' Ada yang gagal diunggah, coba lagi sebentar lagi.']);
        }

        return back()->with('ok', $pesan.($sisa ? " Masih $sisa file lagi, klik Salin lagi." : ''));
    }

    public function putus(Request $request, string $bidang)
    {
        $drive = $this->drive($request, $bidang);
        $drive->putuskan();

        return back()->with('ok', "Google Drive {$drive->label()} diputus. File yang sudah ada di Drive TIDAK dihapus.");
    }
}
