@extends('layouts.admin')
@section('judul', 'Profil Kelurahan')

@section('konten')
<form method="post" action="{{ route('admin.pengaturan.update') }}" enctype="multipart/form-data" style="max-width: 960px">
    @csrf @method('PUT')
    <div class="card mb-3">
        <div class="card-header">Identitas &amp; Kop Surat</div>
        <div class="card-body row g-3">
            <div class="col-md-3"><label class="form-label">Kelurahan</label><input name="nama_kelurahan" value="{{ old('nama_kelurahan', $s['nama_kelurahan']) }}" class="form-control" required></div>
            <div class="col-md-3"><label class="form-label">Kecamatan</label><input name="kecamatan" value="{{ old('kecamatan', $s['kecamatan']) }}" class="form-control" required></div>
            <div class="col-md-3"><label class="form-label">Kabupaten</label><input name="kabupaten" value="{{ old('kabupaten', $s['kabupaten']) }}" class="form-control" required></div>
            <div class="col-md-3"><label class="form-label">Provinsi</label><input name="provinsi" value="{{ old('provinsi', $s['provinsi']) }}" class="form-control" required></div>
            <div class="col-12"><label class="form-label">Alamat Kantor</label><input name="alamat_kantor" value="{{ old('alamat_kantor', $s['alamat_kantor']) }}" class="form-control" required></div>
            <div class="col-md-4"><label class="form-label">Telepon</label><input name="telepon" value="{{ old('telepon', $s['telepon']) }}" class="form-control"></div>
            <div class="col-md-4"><label class="form-label">Email</label><input name="email" value="{{ old('email', $s['email']) }}" class="form-control"></div>
            <div class="col-md-4">
                <label class="form-label">Ukuran Kertas</label>
                <select name="kertas" class="form-select">
                    <option value="A4" @selected($s['kertas'] === 'A4')>A4 (21 x 29,7 cm)</option>
                    <option value="F4" @selected($s['kertas'] === 'F4')>F4 / Folio (21,5 x 33 cm)</option>
                </select>
            </div>
            <div class="col-md-8">
                <label class="form-label">Logo Kabupaten (untuk kop surat)</label>
                <input type="file" name="logo" accept=".png,.jpg,.jpeg" class="form-control">
                <div class="form-text">PNG transparan lebih bagus. Maks 1 MB.</div>
            </div>
            <div class="col-md-4 d-flex align-items-center gap-2">
                @if ($s['logo'])
                    <img src="{{ asset('storage/'.$s['logo']) }}" alt="Logo" style="height: 64px">
                    <div class="form-check"><input class="form-check-input" type="checkbox" name="hapus_logo" value="1" id="hapusLogo"><label class="form-check-label small" for="hapusLogo">Kembali ke logo Kabupaten Banjar</label></div>
                @else
                    <img src="{{ \App\Services\SuratService::logoDataUri() }}" alt="Logo Kabupaten Banjar" style="height: 64px">
                    <span class="small text-muted">Logo Kabupaten Banjar (bawaan)</span>
                @endif
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">Penandatangan &amp; Nomor Surat</div>
        <div class="card-body row g-3">
            <div class="col-md-4"><label class="form-label">Nama Lurah / Penandatangan</label><input name="nama_lurah" value="{{ old('nama_lurah', $s['nama_lurah']) }}" class="form-control" required></div>
            <div class="col-md-4"><label class="form-label">NIP</label><input name="nip_lurah" value="{{ old('nip_lurah', $s['nip_lurah']) }}" class="form-control"></div>
            <div class="col-md-4"><label class="form-label">Jabatan</label><input name="jabatan_ttd" value="{{ old('jabatan_ttd', $s['jabatan_ttd']) }}" class="form-control" required></div>
            <div class="col-12"><div class="small text-muted">Penandatangan <b>a.n. Lurah</b> untuk surat yang ditandatangani Kasi (mis. Surat Pemekaran, SKBB, Domisili Usaha).</div></div>
            @foreach (\App\Models\Layanan::BIDANG as $kunci => $label)
                <div class="col-md-6"><label class="form-label">Nama {{ $label }}</label><input name="nama_kasi_{{ $kunci }}" value="{{ old('nama_kasi_'.$kunci, $s['nama_kasi_'.$kunci]) }}" class="form-control"></div>
                <div class="col-md-6"><label class="form-label">NIP {{ $label }}</label><input name="nip_kasi_{{ $kunci }}" value="{{ old('nip_kasi_'.$kunci, $s['nip_kasi_'.$kunci]) }}" class="form-control"></div>
            @endforeach
            <div class="col-md-6"><label class="form-label">Nama Camat (Mengetahui, Surat Hibah)</label><input name="nama_camat" value="{{ old('nama_camat', $s['nama_camat']) }}" class="form-control"></div>
            <div class="col-md-6"><label class="form-label">NIP Camat</label><input name="nip_camat" value="{{ old('nip_camat', $s['nip_camat']) }}" class="form-control"></div>
            <div class="col-md-6"><label class="form-label">Nama Supervisor / Fasilitator Puskesos (SKTM)</label><input name="nama_fasilitator_puskesos" value="{{ old('nama_fasilitator_puskesos', $s['nama_fasilitator_puskesos']) }}" class="form-control" placeholder="kosong = titik-titik"></div>
            <div class="col-12">
                <label class="form-label">Format Nomor Surat</label>
                <input name="format_nomor" value="{{ old('format_nomor', $s['format_nomor']) }}" class="form-control font-monospace" required>
                <div class="form-text">Pilihan: <code>{kode}</code> kode layanan, <code>{no}</code> nomor urut tahun ini, <code>{bulan_romawi}</code>, <code>{bulan}</code>, <code>{tahun}</code>.</div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">Tampilan Website</div>
        <div class="card-body row g-3">
            <div class="col-12"><label class="form-label">Jam Layanan</label><input name="jam_layanan" value="{{ old('jam_layanan', $s['jam_layanan']) }}" class="form-control"></div>
            <div class="col-12"><label class="form-label">Pengumuman di Beranda</label><textarea name="pengumuman" rows="2" class="form-control">{{ old('pengumuman', $s['pengumuman']) }}</textarea></div>
        </div>
    </div>
    <button class="btn btn-primary"><i class="bi bi-save me-1"></i> Simpan Pengaturan</button>
</form>
@endsection
