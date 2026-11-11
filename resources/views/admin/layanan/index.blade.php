@extends('layouts.admin')
@section('judul', 'Layanan & Template Surat')

@section('konten')
<div class="d-flex justify-content-between align-items-center mb-3">
    <p class="text-muted mb-0">Atur jenis surat, persyaratan, nama folder arsip, dan template suratnya.</p>
    <a href="{{ route('admin.layanan.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Tambah Layanan</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Layanan</th><th>Folder Arsip</th><th>Kode</th><th>Syarat</th><th>Permohonan</th><th>Status</th><th></th></tr></thead>
            <tbody>
                @foreach ($daftar as $l)
                    <tr>
                        <td><i class="bi bi-{{ $l->ikon }} text-primary me-1"></i> <strong>{{ $l->nama }}</strong>
                            <div><span class="badge text-bg-{{ $l->bidang ? 'success' : 'light' }} fw-normal">{{ $l->labelBidang() }}</span>
                                @if (\App\Services\SuratService::templateWord($l))
                                    <span class="badge text-bg-primary fw-normal"><i class="bi bi-file-earmark-word"></i> Word kantor</span>
                                @else
                                    <span class="badge text-bg-light fw-normal border">Template website</span>
                                @endif
                            </div></td>
                        <td class="small"><i class="bi bi-folder-fill text-warning"></i> {{ $l->folder }}</td>
                        <td class="small">{{ $l->kode_surat }}</td>
                        <td class="small">{{ count($l->persyaratan ?? []) }} berkas</td>
                        <td>{{ $l->permohonan_count }}</td>
                        <td>{!! $l->aktif ? '<span class="badge text-bg-success">Aktif</span>' : '<span class="badge text-bg-secondary">Nonaktif</span>' !!}</td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('admin.layanan.template', $l) }}" class="btn btn-sm btn-primary"><i class="bi bi-file-earmark-richtext"></i> Template</a>
                            <a href="{{ route('admin.layanan.edit', $l) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                            <form method="post" action="{{ route('admin.layanan.destroy', $l) }}" class="d-inline" onsubmit="return confirm('Hapus layanan {{ $l->nama }}?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
