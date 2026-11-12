@extends('layouts.admin')
@section('judul', 'Google Drive')

@section('konten')
<div class="row g-3">
    <div class="col-lg-7">
        <p class="small text-muted">
            Setiap surat yang <b>Selesai</b> otomatis disalin ke <b>Google Drive milik Kasi bidangnya</b>, tersusun sama dengan menu
            Arsip Berkas (Layanan &rsaquo; Tahun &rsaquo; Bulan &rsaquo; Kode_Nama). Website hanya bisa membuka file yang dia buat sendiri,
            bukan isi Drive lainnya.
        </p>

        @if (! $siap)
            <div class="alert alert-warning small">Login Google belum diatur di server (GOOGLE_CLIENT_ID / GOOGLE_CLIENT_SECRET kosong).</div>
        @endif
        @if (! $daftar)
            <div class="alert alert-warning small">Akun ini belum punya bidang Kasi. Minta Admin mengatur bidangnya di menu Akun Petugas.</div>
        @endif

        @foreach ($daftar as $bidang => $d)
            <div class="card mb-3 {{ $d['terhubung'] ? 'border-success' : '' }}">
                <div class="card-body">
                    <h6 class="fw-bold"><i class="bi bi-folder-fill text-warning"></i> {{ $d['label'] }}</h6>
                    @if (! $d['terhubung'])
                        <p class="small mb-2"><i class="bi bi-exclamation-circle text-warning"></i> Belum tersambung. Arsip bidang ini hanya tersimpan di server{{ str_contains(request()->getHost(), 'vercel.app') ? ' (di versi online bisa terhapus sendiri)' : '' }}.</p>
                        @if ($siap)
                            <a href="{{ route('admin.drive.sambung', $bidang) }}" class="btn btn-primary btn-sm"><i class="bi bi-link-45deg"></i> Sambungkan Google Drive {{ $d['label'] }}</a>
                            <div class="small text-muted mt-1">Masuk dengan akun Google milik {{ $d['label'] }}, lalu izinkan akses Drive.</div>
                        @endif
                    @else
                        <p class="small mb-2">
                            <i class="bi bi-check-circle-fill text-success"></i> Tersambung ke <b>{{ $d['email'] }}</b> &middot;
                            <a href="{{ \App\Services\GoogleDrive::tautanFolder($d['akar']) }}" target="_blank" rel="noopener">Buka folder di Drive <i class="bi bi-box-arrow-up-right"></i></a><br>
                            Tersalin: <b>{{ $d['tersalin'] }}</b> dari {{ $d['jumlah'] }} file arsip.
                        </p>
                        <div class="d-flex flex-wrap gap-2">
                            <form method="post" action="{{ route('admin.drive.sinkron', $bidang) }}">@csrf
                                <button class="btn btn-outline-primary btn-sm"><i class="bi bi-cloud-upload"></i> Salin arsip yang belum ada di Drive</button>
                            </form>
                            <form method="post" action="{{ route('admin.drive.putus', $bidang) }}" onsubmit="return confirm('Putuskan Google Drive {{ $d['label'] }}? File yang sudah ada di Drive tetap aman (tidak dihapus).')">
                                @csrf @method('DELETE')
                                <button class="btn btn-link btn-sm text-danger"><i class="bi bi-x-circle"></i> Putuskan</button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <div class="col-lg-5">
        <div class="card">
            <div class="card-header">Catatan</div>
            <div class="card-body small">
                <ul class="ps-3 mb-0">
                    <li class="mb-1">Arsip berisi NIK/KK warga. Pakai akun Google dengan <b>verifikasi 2 langkah</b> dan jangan bagikan folder SIPEKA ke orang lain.</li>
                    <li class="mb-1">Kalau Kasi berganti orang: klik <b>Putuskan</b>, lalu Kasi baru menyambungkan akunnya. File lama tetap di Drive Kasi sebelumnya, jadi minta dia memindahkan foldernya.</li>
                    <li class="mb-1">File surat tidak disimpan di database. Database hanya mencatat nama file &amp; lokasinya di Drive.</li>
                    <li class="mb-1">Menghapus permohonan memindahkan foldernya ke <b>Sampah</b> Drive (bisa dipulihkan 30 hari).</li>
                    <li class="mb-1">Kalau Google meminta izin ulang (mis. password akun diganti), klik <b>Putuskan</b> lalu sambungkan lagi.</li>
                    @if (auth()->user()->isAdmin())
                        <li>Alamat kembali yang didaftarkan di Google Cloud: <code class="user-select-all">{{ $redirect }}</code></li>
                    @endif
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
