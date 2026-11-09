@extends('layouts.admin')
@section('judul', 'Nomor Antrian')

@section('konten')
<div class="mx-auto" style="max-width: 520px">
    <div class="card text-center p-4">
        <div class="text-success mb-1"><i class="bi bi-check-circle-fill fs-2"></i></div>
        <div class="fw-semibold mb-3">Permohonan tersimpan dan masuk ke antrean Kasi</div>

        <div class="border border-2 border-success rounded-4 py-3 px-2 mb-3" style="border-style: dashed !important">
            <div class="small text-muted text-uppercase" style="letter-spacing: .1em">Nomor Antrian</div>
            <div class="fw-bold text-primary lh-1 my-2" style="font-size: 6rem">{{ $p->nomorLabel() }}</div>
            <div class="fw-semibold fs-5">{{ $p->nama }}</div>
            <div>{{ $p->layanan->nama }}</div>
            <hr class="my-2 mx-4">
            <div class="row small g-0">
                <div class="col"><div class="text-muted">Tanggal</div><strong>{{ $p->tanggal_antrian->locale('id')->translatedFormat('d F Y') }}</strong></div>
                <div class="col"><div class="text-muted">Jam</div><strong>{{ $p->created_at->format('H:i') }} WITA</strong></div>
            </div>
        </div>
        <p class="small text-muted">Sampaikan nomor ini ke warga dan minta menunggu dipanggil.</p>

        <div class="d-grid gap-2">
            <a href="{{ route('admin.pengajuan.index') }}" class="btn btn-primary btn-lg"><i class="bi bi-plus-circle me-1"></i> Layani Warga Berikutnya</a>
            <button class="btn btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer me-1"></i> Cetak</button>
        </div>
    </div>
</div>
@endsection
