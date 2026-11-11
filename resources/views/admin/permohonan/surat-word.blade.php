@extends('layouts.admin')
@section('judul', 'Buat Surat')

@php
    $label = collect($p->layanan->isian ?? [])->pluck('label', 'key')->all() + \App\Services\SuratService::PENANDA_UMUM + [
        'ttl_angka' => 'Tempat, tanggal lahir', 'balik_nama' => 'Balik nama', 'nama_camat' => 'Nama Camat', 'nip_camat' => 'NIP Camat',
    ];
    $sebut = fn ($k) => $label[$k] ?? ($label[preg_replace('/_(angka|hari)$/', '', $k)] ?? \Illuminate\Support\Str::headline($k))
        .(str_ends_with($k, '_hari') ? ' (hari)' : '');
    $lewati = ['nomor_surat', 'tanggal_surat'];
    $ext = $ext ?? 'docx';
    $aplikasi = $ext === 'xlsx' ? 'Excel' : 'Word';
    $ikon = $ext === 'xlsx' ? 'bi-file-earmark-excel' : 'bi-file-earmark-word';
    // Baris pengikut pindah yang kosong tidak perlu ditampilkan
    $penanda = array_values(array_filter($penanda, fn ($k) => ! preg_match('/^p\d+_/', $k) || trim((string) ($nilai[$k] ?? '')) !== ''));
@endphp

@section('konten')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <a href="{{ route('admin.permohonan.show', $p) }}" class="small"><i class="bi bi-arrow-left"></i> Kembali ke detail</a>
        <h5 class="fw-bold mb-0 mt-1">{{ $p->layanan->nama }} &mdash; {{ $p->nama }}</h5>
    </div>
    <a href="{{ route('admin.permohonan.unduh', [$p, $ext]) }}" class="btn btn-primary"><i class="bi {{ $ikon }} me-1"></i> Unduh Surat ({{ $aplikasi }})</a>
</div>

<div class="alert alert-info small py-2">
    <i class="bi bi-info-circle"></i> Surat ini memakai <b>file {{ $aplikasi }} kantor</b>. Klik <b>Unduh Surat ({{ $aplikasi }})</b>, buka di {{ $aplikasi }}, lalu cetak.
    Kalau ada yang perlu diubah, ubah langsung di {{ $aplikasi }}.
</div>

<form method="post" action="{{ route('admin.permohonan.simpan-surat', $p) }}" enctype="multipart/form-data">
    @csrf
    <div class="row g-3">
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header small fw-semibold">Data yang masuk ke surat</div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0 small align-middle">
                        <tbody>
                            @foreach ($penanda as $k)
                                @continue(in_array($k, $lewati))
                                @php $v = trim((string) ($nilai[$k] ?? '')); @endphp
                                <tr>
                                    <td class="text-muted" style="width: 38%">{{ $sebut($k) }}</td>
                                    <td>{!! $v !== '' ? e($v) : '<span class="text-danger">(kosong)</span>' !!}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
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
                    <button name="aksi" value="simpan" class="btn btn-outline-primary w-100 mb-3"><i class="bi bi-save me-1"></i> Simpan Nomor &amp; Tanggal</button>
                    <hr>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">{{ $aplikasi }} yang sudah diedit (opsional)</label>
                        <input type="file" name="surat_word" accept=".{{ $ext }}" class="form-control form-control-sm">
                        <div class="form-text">Kalau tadi surat diubah di {{ $aplikasi }}, unggah filenya di sini supaya yang diarsipkan sama dengan yang dicetak.</div>
                    </div>
                    <button name="aksi" value="selesai" class="btn btn-success w-100" onclick="return confirm('Tandai surat selesai? File {{ $aplikasi }} akan disimpan ke folder arsip.')"><i class="bi bi-check2-circle me-1"></i> Selesai &amp; Arsipkan</button>
                </div>
            </div>
            <div class="small text-muted">Nomor &amp; tanggal ikut masuk ke {{ $aplikasi }}. Ubah nomor/tanggal → klik <b>Simpan</b> dulu sebelum unduh lagi.</div>
        </div>
    </div>
</form>
@endsection
