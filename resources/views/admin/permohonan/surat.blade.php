@extends('layouts.admin')
@section('judul', 'Buat Surat')

@section('konten')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <a href="{{ route('admin.permohonan.show', $p) }}" class="small"><i class="bi bi-arrow-left"></i> Kembali ke detail</a>
        <h5 class="fw-bold mb-0 mt-1">{{ $p->layanan->nama }} &mdash; {{ $p->nama }}</h5>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.permohonan.unduh', [$p, 'pdf']) }}" target="_blank" class="btn btn-outline-danger btn-sm"><i class="bi bi-file-earmark-pdf"></i> Lihat PDF</a>
        <a href="{{ route('admin.permohonan.unduh', [$p, 'docx']) }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-file-earmark-word"></i> Unduh Word</a>
    </div>
</div>

<form method="post" action="{{ route('admin.permohonan.simpan-surat', $p) }}" id="formSurat">
    @csrf
    <div class="row g-3">
        <div class="col-xl-9">
            <textarea name="surat_html" id="editorSurat">{{ $p->surat_html }}</textarea>
        </div>
        <div class="col-xl-3">
            <div class="card mb-3">
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nomor Surat</label>
                        <input name="nomor_surat" value="{{ old('nomor_surat', $p->nomor_surat) }}" class="form-control" required>
                        <div class="form-text">Terisi otomatis, lanjut dari nomor terakhir. Kalau belum sama dengan buku nomor kantor, ganti sekali saja; surat berikutnya ikut lanjut.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Tanggal Surat</label>
                        <input type="date" name="tanggal_surat" value="{{ old('tanggal_surat', $p->tanggal_surat?->format('Y-m-d')) }}" class="form-control" required>
                    </div>
                    <div class="d-grid gap-2">
                        <button name="aksi" value="simpan" class="btn btn-outline-primary"><i class="bi bi-save me-1"></i> Simpan Draf</button>
                        <button name="aksi" value="selesai" class="btn btn-primary" onclick="return confirm('Tandai surat selesai? File PDF dan Word akan disimpan ke folder arsip.')"><i class="bi bi-check2-circle me-1"></i> Selesai &amp; Arsipkan</button>
                    </div>
                </div>
            </div>
            <div class="card small">
                <div class="card-body">
                    <p class="fw-semibold mb-1"><i class="bi bi-info-circle"></i> Petunjuk</p>
                    @verbatim
                    <ul class="ps-3 mb-2">
                        <li>Data pemohon sudah otomatis terisi dari template. Tinggal cek &amp; ubah jika perlu.</li>
                        <li><code>{{kop_surat}}</code> otomatis diganti kop resmi saat dicetak.</li>
                        <li><code>{{nomor_surat}}</code> dan <code>{{tanggal_surat}}</code> mengikuti isian di atas.</li>
                        <li>Klik <b>Simpan Draf</b> dulu sebelum melihat PDF/Word.</li>
                    </ul>
                    @endverbatim
                </div>
            </div>
        </div>
    </div>
</form>
<form method="post" action="{{ route('admin.permohonan.reset-surat', $p) }}" class="mt-2" onsubmit="return confirm('Isi surat akan diganti ulang dari template layanan. Perubahan manual akan hilang. Lanjutkan?')">
    @csrf
    <button class="btn btn-link btn-sm text-danger p-0"><i class="bi bi-arrow-counterclockwise"></i> Isi ulang dari template</button>
</form>
@endsection

@push('js')
    @include('partials.tinymce', ['selector' => '#editorSurat'])
@endpush
