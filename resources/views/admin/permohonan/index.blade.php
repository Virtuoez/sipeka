@extends('layouts.admin')
@section('judul', 'Permohonan Surat')

@section('konten')
<div class="card mb-3">
    <div class="card-body">
        <form class="row g-2">
            <div class="col-md-3"><input name="q" value="{{ request('q') }}" class="form-control" placeholder="No. antrian, NIK, atau nama..."></div>
            <div class="col-md-2"><input type="date" name="tanggal" value="{{ request('tanggal') }}" class="form-control" title="Tanggal antrian"></div>
            <div class="col-md-2">
                <select name="layanan" class="form-select">
                    <option value="">Semua layanan</option>
                    @foreach ($layanan as $l)<option value="{{ $l->slug }}" @selected(request('layanan') === $l->slug)>{{ $l->nama }}</option>@endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select" title="Status">
                    <option value="">Semua status</option>
                    @foreach (\App\Models\Permohonan::STATUS as $k => [$label])<option value="{{ $k }}" @selected(request('status') === $k)>{{ $label }}</option>@endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex gap-1">
                <button class="btn btn-primary flex-grow-1"><i class="bi bi-funnel"></i> Filter</button>
                <a href="{{ route('admin.permohonan.index') }}" class="btn btn-light" title="Reset"><i class="bi bi-x-lg"></i></a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Antrian</th><th>Pemohon</th><th>Layanan</th><th>Status</th><th>Petugas</th><th></th></tr></thead>
            <tbody>
                @forelse ($daftar as $p)
                    <tr>
                        <td><span class="fw-bold fs-6">{{ $p->nomorLabel() }}</span><div class="small text-muted text-nowrap">{{ $p->tanggal_antrian?->format('d/m/Y') }} {{ $p->created_at->format('H:i') }}</div></td>
                        <td>{{ $p->nama }}<div class="small text-muted">{{ $p->nik }}</div></td>
                        <td class="small">{{ $p->layanan->nama }}</td>
                        <td><span class="badge text-bg-{{ $p->statusWarna() }}">{{ $p->statusLabel() }}</span></td>
                        <td class="small">{{ $p->petugas?->name ?? '-' }}</td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('admin.permohonan.show', $p) }}" class="btn btn-sm btn-outline-primary">Buka</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-5">Tidak ada permohonan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($daftar->hasPages())<div class="card-footer bg-white">{{ $daftar->links('pagination::bootstrap-5') }}</div>@endif
</div>
@endsection
