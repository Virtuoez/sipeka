@extends('layouts.admin')
@section('judul', 'Data Penduduk')

@section('konten')
@php($bolehUbah = auth()->user()->punyaPeran('admin', 'pelayanan'))
@if ($bolehUbah)
<div class="d-flex flex-wrap gap-2 justify-content-end mb-3">
    <a href="{{ route('admin.penduduk.create') }}" class="btn btn-primary"><i class="bi bi-person-plus me-1"></i> Tambah Penduduk</a>
    <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalImport"><i class="bi bi-upload me-1"></i> Import CSV</button>
    <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="btn btn-outline-secondary"><i class="bi bi-filetype-csv me-1"></i> Export (Excel)</a>
</div>
@endif

<div class="card mb-3">
    <div class="card-body">
        <form class="row g-2">
            <div class="col-md-4"><input name="q" value="{{ request('q') }}" class="form-control" placeholder="Cari nama, NIK, atau No. KK..."></div>
            <div class="col-6 col-md-2">
                <select name="rt" class="form-select">
                    <option value="">Semua RT</option>
                    @foreach ($daftarRt as $rt)<option value="{{ $rt }}" @selected(request('rt') == $rt)>RT {{ $rt }}</option>@endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <select name="jk" class="form-select">
                    <option value="">L/P</option>
                    @foreach (['Laki-laki', 'Perempuan'] as $v)<option @selected(request('jk') === $v)>{{ $v }}</option>@endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <select name="status" class="form-select">
                    @foreach (['' => 'Aktif', 'pindah' => 'Pindah', 'meninggal' => 'Meninggal'] as $k => $v)<option value="{{ $k }}" @selected(request('status') === $k)>{{ $v }}</option>@endforeach
                </select>
            </div>
            <div class="col-6 col-md-2 d-flex gap-1">
                <button class="btn btn-primary flex-grow-1"><i class="bi bi-search"></i></button>
                <a href="{{ route('admin.penduduk.index') }}" class="btn btn-light"><i class="bi bi-x-lg"></i></a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header small text-muted">{{ $daftar->total() }} data</div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>NIK</th><th>Nama</th><th>L/P</th><th>Umur</th><th>No. KK</th><th>Hubungan</th><th>RT/RW</th><th></th></tr></thead>
            <tbody>
                @forelse ($daftar as $p)
                    <tr>
                        <td class="font-monospace small">{{ $p->nik }}</td>
                        <td><a href="{{ route('admin.penduduk.show', $p) }}" class="text-decoration-none fw-semibold">{{ $p->nama }}</a></td>
                        <td>{{ $p->jenis_kelamin === 'Laki-laki' ? 'L' : ($p->jenis_kelamin === 'Perempuan' ? 'P' : '-') }}</td>
                        <td>{{ $p->umur ?? '-' }}</td>
                        <td class="font-monospace small">{{ $p->no_kk }}</td>
                        <td class="small">{{ $p->hubungan_keluarga }}</td>
                        <td>{{ $p->rt }}/{{ $p->rw }}</td>
                        <td class="text-end">@if ($bolehUbah)<a href="{{ route('admin.penduduk.edit', $p) }}" class="btn btn-sm btn-light"><i class="bi bi-pencil"></i></a>@endif</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-5">Belum ada data.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($daftar->hasPages())<div class="card-footer bg-white">{{ $daftar->links('pagination::bootstrap-5') }}</div>@endif
</div>

<div class="modal fade" id="modalImport" tabindex="-1">
    <div class="modal-dialog">
        <form method="post" action="{{ route('admin.penduduk.import') }}" enctype="multipart/form-data" class="modal-content">
            @csrf
            <div class="modal-header"><h5 class="modal-title">Import Data Penduduk</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body small">
                <ol class="ps-3">
                    <li>Unduh <a href="{{ route('admin.penduduk.template-csv') }}">template CSV</a>, buka di Excel.</li>
                    <li>Isi data mulai baris ke-2 (jangan ubah judul kolom). Tanggal lahir format <code>1990-12-31</code> atau <code>31/12/1990</code>.</li>
                    <li>Simpan sebagai <b>CSV</b>, lalu unggah di sini.</li>
                </ol>
                <p>NIK yang sudah ada akan <b>diperbarui</b>, NIK baru akan <b>ditambahkan</b>.</p>
                <input type="file" name="file" accept=".csv,.txt" class="form-control" required>
            </div>
            <div class="modal-footer"><button class="btn btn-primary">Import</button></div>
        </form>
    </div>
</div>
@endsection
