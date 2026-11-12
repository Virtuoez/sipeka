@extends('layouts.admin')
@section('judul', 'Arsip Berkas')

@section('konten')
<div class="card mb-3">
    <div class="card-body d-flex flex-wrap align-items-center gap-2">
        <nav aria-label="breadcrumb" class="flex-grow-1">
            <ol class="breadcrumb mb-0">
                @foreach ($remah as $r)
                    @if ($loop->last)
                        <li class="breadcrumb-item active"><i class="bi bi-folder2-open"></i> {{ $r['nama'] }}</li>
                    @else
                        <li class="breadcrumb-item"><a href="{{ route('admin.arsip.index', ['path' => $r['path']]) }}">{{ $r['nama'] }}</a></li>
                    @endif
                @endforeach
            </ol>
        </nav>
        <a href="{{ route('admin.arsip.zip', ['path' => $path]) }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-file-zip me-1"></i> Unduh folder ini (ZIP)</a>
    </div>
</div>

@if ($path === \App\Services\ArsipService::root())
    <div class="alert alert-light border small">
        <i class="bi bi-info-circle"></i> Berkas tersortir otomatis: <b>Layanan</b> &rsaquo; <b>Tahun</b> &rsaquo; <b>Bulan</b> &rsaquo; <b>Kode_Nama Pemohon</b>.
        Untuk cetak massal, buka folder bulan lalu klik <b>Unduh folder ini (ZIP)</b>.
    </div>
@endif

@if ($folder->isNotEmpty())
    <div class="row g-2 mb-4">
        @foreach ($folder as $f)
            <div class="col-sm-6 col-lg-4 col-xxl-3">
                <a href="{{ route('admin.arsip.index', ['path' => $f['path']]) }}" class="folder-item p-2 d-flex align-items-center gap-2">
                    <i class="bi bi-folder-fill"></i>
                    <div class="text-truncate">
                        <div class="fw-semibold text-truncate">{{ $f['nama'] }}</div>
                        <div class="small text-muted">{{ $f['isi'] }} file</div>
                    </div>
                </a>
            </div>
        @endforeach
    </div>
@endif

@if ($file->isNotEmpty())
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Nama File</th><th>Ukuran</th><th>Diubah</th><th></th></tr></thead>
                <tbody>
                    @foreach ($file as $f)
                        @php($ext = strtolower(pathinfo($f['nama'], PATHINFO_EXTENSION)))
                        <tr>
                            <td>
                                <i class="bi {{ ['pdf' => 'bi-file-earmark-pdf text-danger', 'docx' => 'bi-file-earmark-word text-primary', 'xlsx' => 'bi-file-earmark-excel text-success', 'jpg' => 'bi-file-earmark-image text-success', 'jpeg' => 'bi-file-earmark-image text-success', 'png' => 'bi-file-earmark-image text-success'][$ext] ?? 'bi-file-earmark' }} me-1"></i>
                                {{ $f['nama'] }}
                                @if ($f['drive'])<i class="bi bi-google text-success ms-1" title="Tersimpan di Google Drive"></i>@endif
                            </td>
                            <td class="small">{{ number_format($f['ukuran'] / 1024, 0, ',', '.') }} KB</td>
                            <td class="small">{{ date('d/m/Y H:i', $f['waktu']) }}</td>
                            <td class="text-end text-nowrap">
                                @if (! in_array($ext, ['docx', 'xlsx']))
                                    <a href="{{ route('admin.arsip.file', ['path' => $f['path']]) }}" target="_blank" class="btn btn-sm btn-light" title="Lihat / cetak"><i class="bi bi-eye"></i></a>
                                @endif
                                <a href="{{ route('admin.arsip.file', ['path' => $f['path'], 'unduh' => 1]) }}" class="btn btn-sm btn-light" title="Unduh"><i class="bi bi-download"></i></a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

@if ($folder->isEmpty() && $file->isEmpty())
    <div class="text-center text-muted py-5"><i class="bi bi-folder2 fs-1 d-block"></i> Folder masih kosong.</div>
@endif
@endsection
