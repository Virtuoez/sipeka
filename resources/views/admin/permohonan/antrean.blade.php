@extends('layouts.admin')
@section('judul', 'Antrean Surat')

@section('konten')
<div data-segarkan="antrean">
<div class="card mb-4 border-start border-4 border-warning">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-person-workspace me-1"></i> Sedang Saya Kerjakan ({{ $milikSaya->count() }})</span>
        <span class="small text-muted fw-normal">Hanya akun ini yang bisa membuka</span>
    </div>
    @if ($milikSaya->isEmpty())
        <div class="card-body text-muted small">Belum ada. Ambil permohonan dari antrean di bawah.</div>
    @else
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Antrian</th><th>Pemohon</th><th>Layanan</th><th>Diambil</th><th></th></tr></thead>
                <tbody>
                    @foreach ($milikSaya as $p)
                        <tr>
                            <td><span class="fs-5 fw-bold text-primary">{{ $p->nomorLabel() }}</span><div class="small text-muted">{{ $p->tanggal_antrian->format('d/m/Y') }} {{ $p->created_at->format('H:i') }}</div></td>
                            <td>{{ $p->nama }}</td>
                            <td class="small">{{ $p->layanan->nama }}</td>
                            <td class="small">{{ $p->diambil_at?->diffForHumans() }}</td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('admin.permohonan.show', $p) }}" class="btn btn-sm btn-outline-primary">Detail</a>
                                <a href="{{ route('admin.permohonan.surat', $p) }}" class="btn btn-sm btn-primary"><i class="bi bi-pencil-square"></i> Buat Surat</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-list-ol me-1"></i> Antrean Belum Diambil ({{ $antrean->count() }})
            @if (auth()->user()->labelBidang())
                <span class="badge text-bg-success ms-1">khusus surat {{ auth()->user()->labelBidang() }}</span>
            @endif
        </span>
        <span class="small text-muted fw-normal"><i class="bi bi-arrow-repeat"></i> Diperbarui otomatis tiap 10 detik</span>
    </div>
    @if ($antrean->isEmpty())
        <div class="card-body text-center text-muted py-5"><i class="bi bi-cup-hot fs-1 d-block mb-2"></i> Antrean kosong.</div>
    @else
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Antrian</th><th>Pemohon</th><th>Layanan</th><th>Berkas Fisik</th><th></th></tr></thead>
                <tbody>
                    @foreach ($antrean as $p)
                        <tr>
                            <td><span class="fs-5 fw-bold">{{ $p->nomorLabel() }}</span><div class="small text-muted">{{ $p->tanggal_antrian->format('d/m/Y') }} {{ $p->created_at->format('H:i') }}</div></td>
                            <td>{{ $p->nama }}<div class="small text-muted">RT {{ $p->data['rt'] ?? '-' }}/RW {{ $p->data['rw'] ?? '-' }}</div></td>
                            <td class="small">{{ $p->layanan->nama }}</td>
                            <td class="small">{{ count($p->data['berkas_dibawa'] ?? []) }}/{{ count($p->layanan->persyaratan ?? []) }} dicek</td>
                            <td class="text-end text-nowrap">
                                <form method="post" action="{{ route('admin.permohonan.ambil', $p) }}" class="d-inline">
                                    @csrf
                                    <button class="btn btn-sm btn-success"><i class="bi bi-hand-index-thumb"></i> Ambil</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
</div>
@endsection

@push('js')
    {{-- Daftar diperbarui tiap 10 detik supaya permohonan yang diambil komputer lain ikut hilang --}}
    @include('partials.segarkan', ['detik' => 10])
@endpush
