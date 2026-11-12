@extends('layouts.admin')
@section('judul', 'Google Drive')

@section('konten')
<div class="row g-3">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-body">
                <h5 class="fw-bold"><i class="bi bi-google text-primary"></i> Simpan arsip surat ke Google Drive</h5>
                <p class="small text-muted mb-3">
                    Setiap surat yang <b>Selesai</b> otomatis disalin ke Google Drive akun kantor, tersusun sama dengan menu Arsip Berkas
                    (Kasi &rsaquo; Layanan &rsaquo; Tahun &rsaquo; Bulan &rsaquo; Kode_Nama). Website hanya bisa membuka file yang dia buat sendiri,
                    bukan isi Drive lainnya.
                </p>

                @if (! $siap)
                    <div class="alert alert-warning small mb-0">Login Google belum diatur di server (GOOGLE_CLIENT_ID / GOOGLE_CLIENT_SECRET kosong).</div>
                @elseif (! $terhubung)
                    <div class="alert alert-light border small">
                        <i class="bi bi-exclamation-circle text-warning"></i> <b>Belum tersambung.</b>
                        Arsip hanya tersimpan di server{{ str_contains(request()->getHost(), 'vercel.app') ? ', dan di versi online ini folder server bisa terhapus sendiri' : '' }}.
                    </div>
                    <a href="{{ route('admin.drive.sambung') }}" class="btn btn-primary"><i class="bi bi-link-45deg"></i> Sambungkan Google Drive kantor</a>
                    <div class="small text-muted mt-2">Masuk dengan akun Google <b>resmi kantor</b> (bukan akun pribadi), lalu izinkan akses Drive.</div>
                @else
                    <div class="alert alert-success small">
                        <i class="bi bi-check-circle-fill"></i> Tersambung ke <b>{{ $email }}</b>.
                        <a href="{{ \App\Services\GoogleDrive::tautanFolder($root) }}" target="_blank" rel="noopener">Buka folder di Drive <i class="bi bi-box-arrow-up-right"></i></a>
                    </div>
                    <p class="small mb-2">Tersalin ke Drive: <b>{{ $tersalin }}</b> dari {{ $jumlah }} file arsip.</p>
                    <div class="d-flex flex-wrap gap-2">
                        <form method="post" action="{{ route('admin.drive.sinkron') }}">@csrf
                            <button class="btn btn-outline-primary btn-sm"><i class="bi bi-cloud-upload"></i> Salin arsip yang belum ada di Drive</button>
                        </form>
                        <form method="post" action="{{ route('admin.drive.putus') }}" onsubmit="return confirm('Putuskan Google Drive? File yang sudah ada di Drive tetap aman (tidak dihapus).')">
                            @csrf @method('DELETE')
                            <button class="btn btn-link btn-sm text-danger"><i class="bi bi-x-circle"></i> Putuskan</button>
                        </form>
                    </div>
                @endif
            </div>
        </div>

        @if ($terhubung)
            <div class="card mt-3">
                <div class="card-header">Bagikan folder ke Kasi</div>
                <div class="card-body small">
                    <p>Tiap Kasi bisa membuka folder bidangnya sendiri di Google Drive (HP / laptop) dengan akun Google yang terdaftar di
                        <a href="{{ route('admin.akun.index') }}">Akun Petugas</a>. Aksesnya <b>hanya lihat &amp; unduh</b>.</p>
                    <ul class="list-unstyled">
                        @foreach ($folderKasi as $f)
                            <li class="mb-1">
                                <i class="bi bi-folder-fill text-warning"></i> <b>{{ $f['label'] }}</b>:
                                @if ($f['email'])
                                    {{ implode(', ', $f['email']) }}
                                @else
                                    <span class="text-muted">belum ada email Google Kasi</span>
                                @endif
                                @if ($f['id'])
                                    &middot; <a href="{{ \App\Services\GoogleDrive::tautanFolder($f['id']) }}" target="_blank" rel="noopener">buka</a>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                    <form method="post" action="{{ route('admin.drive.bagikan') }}" onsubmit="return confirm('Bagikan folder ke email Kasi di atas? Google akan mengirim email pemberitahuan ke mereka.')">@csrf
                        <button class="btn btn-outline-primary btn-sm"><i class="bi bi-share"></i> Bagikan folder ke Kasi</button>
                    </form>
                </div>
            </div>
        @endif
    </div>

    <div class="col-lg-5">
        <div class="card">
            <div class="card-header">Catatan</div>
            <div class="card-body small">
                <ul class="ps-3 mb-0">
                    <li class="mb-1">Arsip berisi NIK/KK warga. Pakai akun Google kantor dengan <b>verifikasi 2 langkah</b>, dan jangan bagikan folder ke orang lain.</li>
                    <li class="mb-1">File surat tidak disimpan di database. Database hanya mencatat nama file &amp; lokasinya di Drive.</li>
                    <li class="mb-1">Menghapus permohonan memindahkan foldernya ke <b>Sampah</b> Drive (bisa dipulihkan 30 hari).</li>
                    <li class="mb-1">Kalau Google meminta izin ulang (mis. password akun diganti), klik <b>Putuskan</b> lalu sambungkan lagi.</li>
                    <li>Alamat kembali yang didaftarkan di Google Cloud: <code class="user-select-all">{{ $redirect }}</code></li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
