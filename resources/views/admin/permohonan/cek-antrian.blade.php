@extends('layouts.admin')
@section('judul', 'Cek Antrian')

@section('konten')
@php($hariIni = $tanggal->isToday())
@php($tgl = $tanggal->toDateString())

{{-- Kotak cek cepat: ketik nomor, tekan Enter --}}
<div class="card mb-3">
    <div class="card-body">
        <form method="get" class="row g-2 align-items-end" id="formCek">
            <div class="col-sm-5 col-lg-4">
                <label class="form-label fw-semibold mb-1">Nomor antrian</label>
                <input type="number" min="1" name="nomor" id="inputNomor" value="{{ request('nomor') }}" class="form-control form-control-lg text-center fw-bold" placeholder="mis. 15" autofocus autocomplete="off">
            </div>
            <div class="col-sm-4 col-lg-3">
                <label class="form-label small text-muted mb-1">Tanggal</label>
                <input type="date" name="tanggal" value="{{ $tgl }}" class="form-control form-control-lg" onchange="this.form.submit()">
            </div>
            <div class="col-sm-3 col-lg-2">
                <button class="btn btn-primary btn-lg w-100"><i class="bi bi-search"></i> Cek</button>
            </div>
            @unless ($hariIni)
                <div class="col-lg-3 text-lg-end"><a href="{{ route('admin.cek-antrian') }}" class="btn btn-link">Kembali ke hari ini</a></div>
            @endunless
        </form>

        <div data-segarkan="hasil">
        @if (request()->filled('nomor'))
            @if ($hasil)
                <div class="mt-3 p-3 rounded-3 border border-2 border-{{ $hasil->statusWarna() }} d-flex flex-wrap align-items-center gap-3">
                    <div class="text-center px-2">
                        <div class="small text-muted lh-1">Antrian</div>
                        <div class="display-5 fw-bold text-primary lh-1">{{ $hasil->nomorLabel() }}</div>
                    </div>
                    <div class="flex-grow-1">
                        <div class="fs-5 fw-bold">{{ $hasil->nama }}</div>
                        <div>{{ $hasil->layanan->nama }} &middot; masuk {{ $hasil->created_at->format('H:i') }}</div>
                        <div class="mt-1">
                            <span class="badge fs-6 text-bg-{{ $hasil->statusWarna() }}">{{ $hasil->statusLabel() }}</span>
                            @if ($hasil->status === 'diproses')
                                <span class="ms-1">oleh <strong>{{ $hasil->petugas?->name }}</strong> sejak {{ $hasil->diambil_at?->format('H:i') }}</span>
                            @elseif ($hasil->status === 'selesai')
                                <span class="ms-1">{{ $hasil->selesai_at?->format('H:i') }} &middot; No. surat {{ $hasil->nomor_surat }}. Surat bisa diambil.</span>
                            @elseif ($hasil->status === 'ditolak')
                                <span class="ms-1 text-danger">{{ $hasil->catatan }}</span>
                            @else
                                <span class="ms-1 text-muted">belum diambil Kasi</span>
                            @endif
                        </div>
                    </div>
                    <a href="{{ route('admin.permohonan.show', $hasil) }}" class="btn btn-outline-primary">Buka Detail <i class="bi bi-arrow-right"></i></a>
                </div>
            @else
                <div class="alert alert-warning mt-3 mb-0">
                    <i class="bi bi-exclamation-circle"></i> Antrian No. <strong>{{ request('nomor') }}</strong> tidak ada pada {{ $tanggal->locale('id')->translatedFormat('d F Y') }}.
                    @if ($daftar->isNotEmpty()) Nomor yang ada: 1 sampai {{ $daftar->max('nomor_antrian') }}. @endif
                </div>
            @endif
        @endif
        </div>
    </div>
</div>

<div data-segarkan="daftar">
{{-- Ringkasan --}}
<div class="row g-2 mb-3 text-center">
    <div class="col"><div class="card p-2"><div class="small text-muted">Total</div><div class="fs-4 fw-bold">{{ $daftar->count() }}</div></div></div>
    @foreach (\App\Models\Permohonan::STATUS as $k => [$label, $warna])
        <div class="col"><div class="card p-2"><div class="small text-muted">{{ $label }}</div><div class="fs-4 fw-bold text-{{ $warna }}">{{ $jumlah[$k] ?? 0 }}</div></div></div>
    @endforeach
</div>

{{-- Daftar antrian pada tanggal itu --}}
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-list-ol me-1"></i> Antrian {{ $hariIni ? 'Hari Ini' : '' }}, {{ $tanggal->locale('id')->translatedFormat('l, d F Y') }}</span>
        @if ($hariIni)<span class="small text-muted fw-normal"><i class="bi bi-arrow-repeat"></i> diperbarui otomatis</span>@endif
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th style="width: 80px">No.</th><th>Jam</th><th>Pemohon</th><th>Layanan</th><th>Status</th><th>Dikerjakan oleh</th></tr></thead>
            <tbody>
                @forelse ($daftar as $p)
                    <tr onclick="location='{{ route('admin.permohonan.show', $p) }}'" style="cursor: pointer" class="{{ $hasil?->is($p) ? 'table-active' : '' }}">
                        <td class="fs-5 fw-bold">{{ $p->nomorLabel() }}</td>
                        <td>{{ $p->created_at->format('H:i') }}</td>
                        <td>{{ $p->nama }}</td>
                        <td class="small">{{ $p->layanan->nama }}</td>
                        <td><span class="badge text-bg-{{ $p->statusWarna() }}">{{ $p->statusLabel() }}</span></td>
                        <td class="small">{{ $p->status === 'baru' ? '-' : ($p->petugas?->name ?? '-') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-5">Belum ada antrian di tanggal ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
</div>
@endsection

@push('js')
@if ($hariIni)
    {{-- Status & daftar diperbarui tiap 15 detik; kotak nomor yang sedang diketik tidak tersentuh --}}
    @include('partials.segarkan', ['detik' => 15])
@endif
@endpush
