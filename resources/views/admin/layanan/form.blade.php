@extends('layouts.admin')
@section('judul', $layanan->exists ? 'Ubah Layanan' : 'Tambah Layanan')

@section('konten')
<a href="{{ route('admin.layanan.index') }}" class="small"><i class="bi bi-arrow-left"></i> Kembali</a>
<form method="post" action="{{ $layanan->exists ? route('admin.layanan.update', $layanan) : route('admin.layanan.store') }}" class="card mt-2" style="max-width: 900px">
    @csrf
    @if ($layanan->exists) @method('PUT') @endif
    <div class="card-body row g-3">
        <div class="col-md-8">
            <label class="form-label">Nama Layanan <span class="text-danger">*</span></label>
            <input name="nama" value="{{ old('nama', $layanan->nama) }}" class="form-control" required placeholder="Surat Keterangan ...">
        </div>
        <div class="col-md-4">
            <label class="form-label">Kode Surat</label>
            <input name="kode_surat" value="{{ old('kode_surat', $layanan->kode_surat) }}" class="form-control" placeholder="mis. 470">
            <div class="form-text">Dipakai di nomor surat sebagai {kode}.</div>
        </div>
        <div class="col-md-6">
            <label class="form-label">Dikerjakan oleh</label>
            <select name="bidang" class="form-select">
                <option value="">Semua Kasi</option>
                @foreach (\App\Models\Layanan::BIDANG as $kunci => $label)
                    <option value="{{ $kunci }}" @selected(old('bidang', $layanan->bidang) === $kunci)>{{ $label }}</option>
                @endforeach
            </select>
            <div class="form-text">Permohonan surat ini hanya muncul di antrean Kasi tersebut, dan arsipnya masuk folder Kasi itu.</div>
        </div>
        <div class="col-md-6">
            <label class="form-label">Nama Folder Arsip</label>
            <input name="folder" value="{{ old('folder', $layanan->folder) }}" class="form-control" placeholder="mis. 10 - Keterangan Ahli Waris">
            <div class="form-text">Semua berkas layanan ini masuk ke folder ini. Kosongkan = pakai nama layanan.</div>
        </div>
        <div class="col-md-3">
            <label class="form-label">Alamat URL (slug)</label>
            <input name="slug" value="{{ old('slug', $layanan->slug) }}" class="form-control" placeholder="otomatis">
        </div>
        <div class="col-md-3">
            <label class="form-label">Ikon</label>
            <input name="ikon" value="{{ old('ikon', $layanan->ikon) }}" class="form-control">
            <div class="form-text">Nama dari <a href="https://icons.getbootstrap.com" target="_blank">Bootstrap Icons</a>.</div>
        </div>
        <div class="col-12">
            <label class="form-label">Deskripsi Singkat</label>
            <input name="deskripsi" value="{{ old('deskripsi', $layanan->deskripsi) }}" class="form-control">
        </div>
        <div class="col-md-6">
            <label class="form-label">Berkas yang Harus Dibawa Warga (satu per baris)</label>
            <textarea name="persyaratan_teks" rows="7" class="form-control font-monospace small" placeholder="KTP&#10;Kartu Keluarga&#10;Surat Pengantar RT/RW&#10;Foto Rumah (opsional)">{{ old('persyaratan_teks', $layanan->persyaratanTeks()) }}</textarea>
            <div class="form-text">Muncul sebagai daftar centang di formulir Pelayanan. Tambahkan <code>(opsional)</code> di akhir untuk berkas yang tidak wajib.</div>
        </div>
        <div class="col-md-6">
            <label class="form-label">Isian Tambahan (satu per baris)</label>
            <textarea name="isian_teks" rows="7" class="form-control font-monospace small" placeholder="Keperluan | paragraf&#10;Nama Usaha | teks&#10;Tanggal Meninggal | tanggal&#10;Jumlah Anak | angka (opsional)">{{ old('isian_teks', $layanan->isianTeks()) }}</textarea>
            <div class="form-text">Format: <code>Label | tipe</code>. Tipe: teks, paragraf, tanggal, angka. Di template, pakai penanda <code>@{{nama_label}}</code> (huruf kecil, spasi jadi garis bawah).</div>
        </div>
        <div class="col-12">
            <div class="form-check form-switch">
                <input type="hidden" name="aktif" value="0">
                <input class="form-check-input" type="checkbox" name="aktif" value="1" id="aktif" @checked(old('aktif', $layanan->aktif))>
                <label class="form-check-label" for="aktif">Aktif (tampil di website)</label>
            </div>
        </div>
    </div>
    <div class="card-footer bg-white text-end">
        <button class="btn btn-primary"><i class="bi bi-save me-1"></i> Simpan</button>
    </div>
</form>
@endsection
