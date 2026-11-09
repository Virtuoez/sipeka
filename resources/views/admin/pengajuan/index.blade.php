@extends('layouts.admin')
@section('judul', 'Buat Permohonan')

@section('konten')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h4 class="fw-bold mb-0">Warga mau mengurus surat apa?</h4>
        <div class="text-muted">Pilih jenis surat, lalu isi formulir bersama warga.</div>
    </div>
    <div class="text-end">
        <div class="small text-muted">Antrian hari ini</div>
        <div class="fs-3 fw-bold text-primary lh-1">{{ $hariIni }}</div>
    </div>
</div>

<div class="row g-3">
    @foreach ($layanan as $l)
        <div class="col-sm-6 col-xl-4">
            <a href="{{ route('admin.pengajuan.form', $l) }}" class="card kartu-layanan h-100 p-3 text-decoration-none text-body">
                <div class="d-flex gap-3 align-items-start">
                    <div class="ikon-layanan flex-shrink-0"><i class="bi bi-{{ $l->ikon }}"></i></div>
                    <div class="flex-grow-1">
                        <div class="fw-bold fs-6 mb-1">{{ $l->nama }}</div>
                        <div class="small text-muted mb-2">{{ $l->deskripsi }}</div>
                        @if (! empty($l->persyaratan))
                            <div class="small">
                                <span class="fw-semibold">Bawa:</span>
                                {{ collect($l->persyaratan)->map(fn ($s) => $s['label'].($s['wajib'] ? '' : ' (opsional)'))->implode(', ') }}
                            </div>
                        @endif
                    </div>
                    <i class="bi bi-chevron-right text-muted align-self-center"></i>
                </div>
            </a>
        </div>
    @endforeach
</div>
@endsection
