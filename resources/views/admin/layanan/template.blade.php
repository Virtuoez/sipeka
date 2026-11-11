@extends('layouts.admin')
@section('judul', 'Template Surat')

@php
    $aplikasi = $ext === 'xlsx' ? 'Excel' : 'Word';
    $ikon = $ext === 'xlsx' ? 'bi-file-earmark-excel text-success' : 'bi-file-earmark-word text-primary';
@endphp

@section('konten')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <a href="{{ route('admin.layanan.index') }}" class="small"><i class="bi bi-arrow-left"></i> Kembali</a>
        <h5 class="fw-bold mb-0 mt-1">Template: {{ $layanan->nama }}</h5>
    </div>
    <div class="d-flex gap-2">
        @unless ($word)
            <button type="button" class="btn btn-outline-danger btn-sm" onclick="pratinjau('pdf')"><i class="bi bi-file-earmark-pdf"></i> Pratinjau PDF</button>
        @endunless
        <button type="button" class="btn btn-{{ $word ? '' : 'outline-' }}primary btn-sm" onclick="pratinjau('{{ $ext ?: 'docx' }}')"><i class="bi {{ $word ? str_replace([' text-primary', ' text-success'], '', $ikon) : 'bi-file-earmark-word' }}"></i> {{ $word ? 'Coba Isi '.$aplikasi.' (data contoh)' : 'Pratinjau Word' }}</button>
    </div>
</div>

<div class="card mb-3 {{ $word ? 'border-primary' : '' }}">
    <div class="card-body">
        <div class="fw-semibold mb-1"><i class="bi {{ $word ? $ikon : 'bi-file-earmark-word text-primary' }}"></i> Template {{ $word ? $aplikasi : 'Word / Excel' }} kantor</div>
        @if ($word)
            <p class="small mb-2">
                Surat ini <b>dicetak dari file {{ $aplikasi }}</b> ({{ $wordUnggahan ? 'unggahan' : 'bawaan, dari surat kantor' }}).
                Editor di bawah hanya dipakai kalau file {{ $aplikasi }} dihapus. Untuk mengubah surat: unduh file-nya, ubah di {{ $aplikasi }}
                (biarkan tulisan seperti <code>${nama}</code>, <code>${nik}</code> karena itu diisi otomatis), lalu unggah lagi.
            </p>
        @else
            <p class="small mb-2">Belum ada file kantor. Surat dibuat dari editor di bawah. Bisa juga unggah file Word (.docx) atau Excel (.xlsx) dengan penanda seperti <code>${nama}</code>, <code>${nik}</code>, <code>${nomor_surat}</code>.</p>
        @endif
        <div class="d-flex flex-wrap gap-2 align-items-center">
            @if ($word)
                <a href="{{ route('admin.layanan.word', $layanan) }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-download"></i> Unduh template {{ $aplikasi }}</a>
            @endif
            <form method="post" action="{{ route('admin.layanan.unggah-word', $layanan) }}" enctype="multipart/form-data" class="d-flex gap-2">
                @csrf
                <input type="file" name="template_word" accept=".docx,.xlsx" class="form-control form-control-sm" required>
                <button class="btn btn-primary btn-sm text-nowrap"><i class="bi bi-upload"></i> Unggah</button>
            </form>
            @if ($wordUnggahan)
                <form method="post" action="{{ route('admin.layanan.hapus-word', $layanan) }}" onsubmit="return confirm('Hapus template unggahan?{{ $wordBawaan ? ' Surat kembali memakai file bawaan.' : '' }}')">
                    @csrf @method('DELETE')
                    <button class="btn btn-link btn-sm text-danger"><i class="bi bi-arrow-counterclockwise"></i> {{ $wordBawaan ? 'Kembali ke file bawaan' : 'Hapus file unggahan' }}</button>
                </form>
            @endif
        </div>
        @error('template_word')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
    </div>
</div>

@if ($word && ! $wordUnggahan && is_file(resource_path('surat/contoh/'.$layanan->slug.'.pdf')))
    <div class="card mb-3">
        <div class="card-header small fw-semibold">Tampilan surat yang dicetak (contoh, data fiktif)</div>
        @php($s = \App\Models\Pengaturan::semua())
        <div class="card-body small py-2 border-bottom bg-light">
            <i class="bi bi-info-circle"></i> Nama &amp; NIP pejabat di contoh ini hanya contoh. Surat asli otomatis memakai
            <a href="{{ route('admin.pengaturan') }}">Profil Kelurahan</a>:
            Lurah <b>{{ $s['nama_lurah'] }}</b> (NIP {{ $s['nip_lurah'] }})
            @foreach (\App\Models\Layanan::BIDANG as $k => $label)
                &middot; {{ $label }} <b>{{ $s['nama_kasi_'.$k] }}</b>
            @endforeach
            &middot; Camat <b>{{ $s['nama_camat'] }}</b>.
            Klik <b>Coba Isi</b> di atas untuk melihat surat dengan nama-nama ini.
        </div>
        <iframe src="{{ route('admin.layanan.contoh-word', $layanan) }}#toolbar=0&view=FitH" style="width: 100%; height: 900px; border: 0"></iframe>
    </div>
@endif

@if ($word)
<details class="mb-3">
    <summary class="small text-muted">Template cadangan (tidak dipakai selama file {{ $aplikasi }} ada)</summary>
@endif
<div class="row g-3 mt-1">
    <div class="col-xl-9">
        <form method="post" action="{{ route('admin.layanan.simpan-template', $layanan) }}" id="formTemplate">
            @csrf @method('PUT')
            <textarea name="template_html" id="editorTemplate">{{ $layanan->template_html }}</textarea>
            <button class="btn btn-primary mt-2"><i class="bi bi-save me-1"></i> Simpan Template</button>
        </form>
    </div>
    <div class="col-xl-3">
        <div class="card">
            <div class="card-header small">Penanda (klik untuk menyisipkan)</div>
            <div class="card-body p-2" style="max-height: 720px; overflow: auto">
                @if (! empty($layanan->isian))
                    <div class="small fw-semibold text-primary px-1 mb-1">Isian khusus layanan ini</div>
                    <ul class="list-group list-group-flush mb-3">
                        @foreach ($layanan->isian as $i)
                            <li class="list-group-item penanda px-1 py-1" data-penanda="{{ $i['key'] }}">
                                <span class="text-primary">&#123;&#123;{{ $i['key'] }}&#125;&#125;</span><br><span class="text-muted">{{ $i['label'] }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
                <div class="small fw-semibold text-primary px-1 mb-1">Penanda umum</div>
                <ul class="list-group list-group-flush">
                    @foreach ($penandaUmum as $kunci => $ket)
                        <li class="list-group-item penanda px-1 py-1" data-penanda="{{ $kunci }}">
                            <span class="text-primary">&#123;&#123;{{ $kunci }}&#125;&#125;</span><br><span class="text-muted">{{ $ket }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
        <p class="small text-muted mt-2">Penanda akan otomatis diganti data pemohon saat surat dibuat. Kop surat diatur di menu <a href="{{ route('admin.pengaturan') }}">Profil Kelurahan</a>.</p>
    </div>
</div>
@if ($word)
</details>
@endif

<form method="post" id="formPratinjau" target="_blank">
    @csrf
    <input type="hidden" name="template_html">
</form>
@endsection

@push('js')
    @include('partials.tinymce', ['selector' => '#editorTemplate'])
    <script>
        function pratinjau(format) {
            const f = document.getElementById('formPratinjau');
            f.action = '{{ url('admin/layanan/'.$layanan->slug.'/pratinjau') }}/' + format;
            f.template_html.value = tinymce.get('editorTemplate').getContent();
            f.submit();
        }
    </script>
@endpush
