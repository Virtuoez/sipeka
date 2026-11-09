@extends('layouts.admin')
@section('judul', 'Template Surat')

@section('konten')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <a href="{{ route('admin.layanan.index') }}" class="small"><i class="bi bi-arrow-left"></i> Kembali</a>
        <h5 class="fw-bold mb-0 mt-1">Template: {{ $layanan->nama }}</h5>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-danger btn-sm" onclick="pratinjau('pdf')"><i class="bi bi-file-earmark-pdf"></i> Pratinjau PDF</button>
        <button type="button" class="btn btn-outline-primary btn-sm" onclick="pratinjau('docx')"><i class="bi bi-file-earmark-word"></i> Pratinjau Word</button>
    </div>
</div>

<div class="row g-3">
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
