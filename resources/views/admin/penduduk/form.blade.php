@extends('layouts.admin')
@section('judul', $penduduk->exists ? 'Ubah Data Penduduk' : 'Tambah Penduduk')

@section('konten')
@php($v = fn ($k) => old($k, $k === 'tanggal_lahir' ? $penduduk->tanggal_lahir?->format('Y-m-d') : $penduduk->$k))
<a href="{{ $penduduk->exists ? route('admin.penduduk.show', $penduduk) : route('admin.penduduk.index') }}" class="small"><i class="bi bi-arrow-left"></i> Kembali</a>
<form method="post" action="{{ $penduduk->exists ? route('admin.penduduk.update', $penduduk) : route('admin.penduduk.store') }}" class="card mt-2" style="max-width: 960px">
    @csrf
    @if ($penduduk->exists) @method('PUT') @endif
    <div class="card-body row g-3">
        <div class="col-md-6"><label class="form-label">NIK *</label><input name="nik" value="{{ $v('nik') }}" class="form-control" maxlength="16" required></div>
        <div class="col-md-6"><label class="form-label">No. KK</label><input name="no_kk" value="{{ $v('no_kk') }}" class="form-control" maxlength="16"></div>
        <div class="col-md-8"><label class="form-label">Nama Lengkap *</label><input name="nama" value="{{ $v('nama') }}" class="form-control" required></div>
        <div class="col-md-4">
            <label class="form-label">Hubungan dalam Keluarga</label>
            <select name="hubungan_keluarga" class="form-select"><option value="">-</option>@foreach (\App\Models\Penduduk::HUBUNGAN as $o)<option @selected($v('hubungan_keluarga') === $o)>{{ $o }}</option>@endforeach</select>
        </div>
        <div class="col-md-4"><label class="form-label">Tempat Lahir</label><input name="tempat_lahir" value="{{ $v('tempat_lahir') }}" class="form-control"></div>
        <div class="col-md-4"><label class="form-label">Tanggal Lahir</label><input type="date" name="tanggal_lahir" value="{{ $v('tanggal_lahir') }}" class="form-control"></div>
        <div class="col-md-4">
            <label class="form-label">Jenis Kelamin</label>
            <select name="jenis_kelamin" class="form-select"><option value="">-</option>@foreach (['Laki-laki', 'Perempuan'] as $o)<option @selected($v('jenis_kelamin') === $o)>{{ $o }}</option>@endforeach</select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Agama</label>
            <select name="agama" class="form-select"><option value="">-</option>@foreach (\App\Models\Penduduk::AGAMA as $o)<option @selected($v('agama') === $o)>{{ $o }}</option>@endforeach</select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Status Perkawinan</label>
            <select name="status_perkawinan" class="form-select"><option value="">-</option>@foreach (\App\Models\Penduduk::STATUS_KAWIN as $o)<option @selected($v('status_perkawinan') === $o)>{{ $o }}</option>@endforeach</select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Pendidikan</label>
            <select name="pendidikan" class="form-select"><option value="">-</option>@foreach (\App\Models\Penduduk::PENDIDIKAN as $o)<option @selected($v('pendidikan') === $o)>{{ $o }}</option>@endforeach</select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Gol. Darah</label>
            <select name="golongan_darah" class="form-select"><option value="">-</option>@foreach (['A', 'B', 'AB', 'O'] as $o)<option @selected($v('golongan_darah') === $o)>{{ $o }}</option>@endforeach</select>
        </div>
        <div class="col-md-6"><label class="form-label">Pekerjaan</label><input name="pekerjaan" value="{{ $v('pekerjaan') }}" class="form-control"></div>
        <div class="col-md-3">
            <label class="form-label">Kewarganegaraan</label>
            <select name="kewarganegaraan" class="form-select">@foreach (['WNI', 'WNA'] as $o)<option @selected(($v('kewarganegaraan') ?: 'WNI') === $o)>{{ $o }}</option>@endforeach</select>
        </div>
        <div class="col-md-3"><label class="form-label">No. HP</label><input name="no_hp" value="{{ $v('no_hp') }}" class="form-control"></div>
        <div class="col-md-8"><label class="form-label">Alamat</label><input name="alamat" value="{{ $v('alamat') }}" class="form-control"></div>
        <div class="col-6 col-md-2"><label class="form-label">RT</label><input name="rt" value="{{ $v('rt') }}" class="form-control" maxlength="3"></div>
        <div class="col-6 col-md-2"><label class="form-label">RW</label><input name="rw" value="{{ $v('rw') }}" class="form-control" maxlength="3"></div>
        <div class="col-md-4">
            <label class="form-label">Status Penduduk</label>
            <select name="status" class="form-select">@foreach (['aktif' => 'Aktif', 'pindah' => 'Pindah', 'meninggal' => 'Meninggal'] as $k => $o)<option value="{{ $k }}" @selected(($v('status') ?: 'aktif') === $k)>{{ $o }}</option>@endforeach</select>
        </div>
        <div class="col-md-8"><label class="form-label">Keterangan</label><input name="keterangan" value="{{ $v('keterangan') }}" class="form-control"></div>
    </div>
    <div class="card-footer bg-white d-flex justify-content-between">
        <span></span>
        <button class="btn btn-primary"><i class="bi bi-save me-1"></i> Simpan</button>
    </div>
</form>
@if ($penduduk->exists && auth()->user()->isAdmin())
    <form method="post" action="{{ route('admin.penduduk.destroy', $penduduk) }}" class="mt-2" onsubmit="return confirm('Hapus data {{ $penduduk->nama }} permanen? Untuk penduduk pindah/meninggal, sebaiknya ubah status saja.')">
        @csrf @method('DELETE')
        <button class="btn btn-link text-danger btn-sm p-0"><i class="bi bi-trash"></i> Hapus data ini</button>
    </form>
@endif
@endsection
