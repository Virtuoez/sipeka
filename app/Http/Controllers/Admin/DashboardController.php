<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Layanan;
use App\Models\Penduduk;
use App\Models\Permohonan;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        // Halaman utama tiap peran: Kasi ke antrean, Pelayanan ke pilihan surat
        if ($request->user()->isKasi()) {
            return redirect()->route('admin.antrean');
        }
        if ($request->user()->isPelayanan()) {
            return redirect()->route('admin.pengajuan.index');
        }

        $aktif = Penduduk::where('status', 'aktif');

        return view('admin.dashboard', [
            'antrianHariIni' => Permohonan::whereDate('tanggal_antrian', today())->count(),
            'perKasi' => User::where('role', 'kasi')->orderBy('name')->withCount([
                'permohonanDikerjakan as dikerjakan_count' => fn ($q) => $q->where('status', 'diproses'),
                'permohonanDikerjakan as selesai_hari_ini_count' => fn ($q) => $q->where('status', 'selesai')->whereDate('selesai_at', today()),
            ])->get(),
            'jumlahStatus' => Permohonan::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
            'perLayanan' => Layanan::withCount([
                'permohonan',
                'permohonan as baru_count' => fn ($q) => $q->where('status', 'baru'),
            ])->orderBy('urutan')->get(),
            'terbaru' => Permohonan::with('layanan', 'petugas')->latest()->limit(8)->get(),
            'penduduk' => [
                'total' => (clone $aktif)->count(),
                'kk' => (clone $aktif)->whereNotNull('no_kk')->distinct('no_kk')->count('no_kk'),
                'laki' => (clone $aktif)->where('jenis_kelamin', 'Laki-laki')->count(),
                'perempuan' => (clone $aktif)->where('jenis_kelamin', 'Perempuan')->count(),
            ],
            'perRt' => (clone $aktif)->selectRaw('rt, count(*) as total')->groupBy('rt')->orderBy('rt')->pluck('total', 'rt'),
        ]);
    }
}
