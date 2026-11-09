@extends('layouts.admin')
@section('judul', 'Dashboard')

@section('konten')
<div class="row g-3 mb-4">
    @foreach (\App\Models\Permohonan::STATUS as $kunci => [$label, $warna])
        <div class="col-6 col-lg-3">
            <a href="{{ route('admin.permohonan.index', ['status' => $kunci]) }}" class="text-decoration-none">
                <div class="card kartu-stat p-3" style="border-left-color: var(--bs-{{ $warna }})">
                    <div class="small text-muted">Permohonan {{ $label }}</div>
                    <div class="angka text-{{ $warna }}">{{ $jumlahStatus[$kunci] ?? 0 }}</div>
                </div>
            </a>
        </div>
    @endforeach
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-clock-history me-1"></i> Permohonan Terbaru</span>
                <a href="{{ route('admin.permohonan.index') }}" class="small">Lihat semua</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Antrian</th><th>Pemohon</th><th>Layanan</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse ($terbaru as $p)
                            <tr onclick="location='{{ route('admin.permohonan.show', $p) }}'" style="cursor: pointer">
                                <td><span class="fw-bold">{{ $p->nomorLabel() }}</span><div class="small text-muted">{{ $p->tanggal_antrian?->format('d/m') }} {{ $p->created_at->format('H:i') }}</div></td>
                                <td>{{ $p->nama }}<div class="small text-muted">{{ $p->created_at->diffForHumans() }}</div></td>
                                <td class="small">{{ $p->layanan->nama }}</td>
                                <td><span class="badge text-bg-{{ $p->statusWarna() }}">{{ $p->statusLabel() }}</span>@if ($p->petugas && $p->status === 'diproses')<div class="small text-muted">{{ $p->petugas->name }}</div>@endif</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">Belum ada permohonan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between">
                <span><i class="bi bi-pc-display me-1"></i> Komputer Kasi</span>
                <span class="small fw-normal text-muted">Antrian hari ini: <strong>{{ $antrianHariIni }}</strong></span>
            </div>
            <ul class="list-group list-group-flush small">
                @forelse ($perKasi as $k)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-person-workspace text-success me-1"></i> {{ $k->name }}</span>
                        <span>
                            <span class="badge text-bg-warning" title="Sedang dikerjakan">{{ $k->dikerjakan_count }} dikerjakan</span>
                            <span class="badge text-bg-success" title="Selesai hari ini">{{ $k->selesai_hari_ini_count }} selesai</span>
                        </span>
                    </li>
                @empty
                    <li class="list-group-item text-muted">Belum ada akun Kasi.</li>
                @endforelse
            </ul>
        </div>
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-people me-1"></i> Data Penduduk Aktif</div>
            <div class="card-body">
                <div class="row text-center g-2">
                    <div class="col-6"><div class="fs-4 fw-bold text-primary">{{ $penduduk['total'] }}</div><div class="small text-muted">Jiwa</div></div>
                    <div class="col-6"><div class="fs-4 fw-bold text-primary">{{ $penduduk['kk'] }}</div><div class="small text-muted">KK</div></div>
                    <div class="col-6"><div class="fs-5 fw-bold">{{ $penduduk['laki'] }}</div><div class="small text-muted">Laki-laki</div></div>
                    <div class="col-6"><div class="fs-5 fw-bold">{{ $penduduk['perempuan'] }}</div><div class="small text-muted">Perempuan</div></div>
                </div>
                @if ($perRt->isNotEmpty())
                    <hr>
                    <div class="small fw-semibold mb-2">Jumlah jiwa per RT</div>
                    @php($maks = max($perRt->max(), 1))
                    @foreach ($perRt as $rt => $jml)
                        <div class="d-flex align-items-center gap-2 small mb-1">
                            <span style="width: 52px">RT {{ $rt ?: '-' }}</span>
                            <div class="progress flex-grow-1" style="height: 8px"><div class="progress-bar bg-primary" style="width: {{ $jml / $maks * 100 }}%"></div></div>
                            <span style="width: 28px" class="text-end">{{ $jml }}</span>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
        <div class="card">
            <div class="card-header"><i class="bi bi-bar-chart me-1"></i> Per Layanan</div>
            <ul class="list-group list-group-flush small">
                @foreach ($perLayanan as $l)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <a href="{{ route('admin.permohonan.index', ['layanan' => $l->slug]) }}" class="text-decoration-none text-body">{{ $l->nama }}</a>
                        <span>
                            @if ($l->baru_count)<span class="badge text-bg-primary">{{ $l->baru_count }} baru</span>@endif
                            <span class="badge text-bg-light">{{ $l->permohonan_count }}</span>
                        </span>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
@endsection
