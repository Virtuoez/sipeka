@extends('layouts.admin')
@section('judul', $layanan->nama)

@section('konten')
@php($pilih = fn ($nama, $opsi, $bawaan = '') => collect($opsi)->map(fn ($v) => '<option'.(old($nama, $bawaan) === $v ? ' selected' : '').'>'.e($v).'</option>')->implode(''))
<div style="max-width: 980px">
    <a href="{{ route('admin.pengajuan.index') }}" class="small"><i class="bi bi-arrow-left"></i> Pilih surat lain</a>
    <div class="d-flex align-items-center gap-3 mt-1 mb-3">
        <div class="ikon-layanan flex-shrink-0"><i class="bi bi-{{ $layanan->ikon }}"></i></div>
        <div>
            <h4 class="fw-bold mb-0">{{ $layanan->nama }}</h4>
            <div class="text-muted small">{{ $layanan->deskripsi }}</div>
        </div>
    </div>

    <form method="post" action="{{ route('admin.pengajuan.simpan', $layanan) }}" id="formPengajuan" novalidate>
        @csrf

        {{-- 1. Berkas fisik: dicek dulu sebelum mengisi --}}
        @if (! empty($layanan->persyaratan))
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-folder-check me-1"></i> 1. Cek Berkas yang Dibawa Warga</div>
            <div class="card-body">
                <div class="row g-2">
                    @foreach ($layanan->persyaratan as $syarat)
                        <div class="col-md-6">
                            <label class="d-flex align-items-center gap-2 border rounded-3 p-2 h-100 @error('berkas.'.$syarat['key']) border-danger @enderror" style="cursor: pointer">
                                <input type="checkbox" class="form-check-input m-0 flex-shrink-0" style="width: 1.5rem; height: 1.5rem" name="berkas[{{ $syarat['key'] }}]" value="1" @checked(old('berkas.'.$syarat['key']))>
                                <span>{{ $syarat['label'] }} @if ($syarat['wajib'])<span class="text-danger">*</span>@else<span class="badge text-bg-light">opsional</span>@endif</span>
                            </label>
                        </div>
                    @endforeach
                </div>
                <div class="form-text">Centang berkas <strong>asli</strong> yang sudah diperiksa. Berkas bertanda <span class="text-danger">*</span> wajib ada.</div>
            </div>
        </div>
        @endif

        {{-- 2. Data diri --}}
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-person-vcard me-1"></i> {{ empty($layanan->persyaratan) ? 1 : 2 }}. Data Diri Pemohon (sesuai KTP/KK)</div>
            <div class="card-body row g-3">
                <div class="col-md-6">
                    <label class="form-label">NIK <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input name="nik" id="nik" value="{{ old('nik') }}" class="form-control form-control-lg @error('nik') is-invalid @enderror" inputmode="numeric" maxlength="16" required autocomplete="off">
                        <button type="button" class="btn btn-outline-primary" id="tombolCariNik"><i class="bi bi-search"></i> Isi Otomatis</button>
                    </div>
                    <div class="form-text" id="infoNik">Ketik 16 digit NIK. Kalau terdaftar di data penduduk, data diri terisi sendiri.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Nomor KK <span class="badge text-bg-light">opsional</span></label>
                    <input name="no_kk" value="{{ old('no_kk') }}" class="form-control form-control-lg @error('no_kk') is-invalid @enderror" inputmode="numeric" maxlength="16">
                </div>
                <div class="col-12">
                    <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                    <input name="nama" value="{{ old('nama') }}" class="form-control form-control-lg @error('nama') is-invalid @enderror" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Tempat Lahir <span class="text-danger">*</span></label>
                    <input name="tempat_lahir" value="{{ old('tempat_lahir') }}" class="form-control form-control-lg @error('tempat_lahir') is-invalid @enderror" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Tanggal Lahir <span class="text-danger">*</span></label>
                    <input type="date" name="tanggal_lahir" value="{{ old('tanggal_lahir') }}" class="form-control form-control-lg @error('tanggal_lahir') is-invalid @enderror" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Jenis Kelamin <span class="text-danger">*</span></label>
                    <select name="jenis_kelamin" class="form-select form-select-lg @error('jenis_kelamin') is-invalid @enderror" required><option value="">- Pilih -</option>{!! $pilih('jenis_kelamin', ['Laki-laki', 'Perempuan']) !!}</select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Agama <span class="text-danger">*</span></label>
                    <select name="agama" class="form-select form-select-lg @error('agama') is-invalid @enderror" required><option value="">- Pilih -</option>{!! $pilih('agama', \App\Models\Penduduk::AGAMA) !!}</select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status Perkawinan <span class="text-danger">*</span></label>
                    <select name="status_perkawinan" class="form-select form-select-lg @error('status_perkawinan') is-invalid @enderror" required><option value="">- Pilih -</option>{!! $pilih('status_perkawinan', \App\Models\Penduduk::STATUS_KAWIN) !!}</select>
                </div>
                <div class="col-md-8">
                    <label class="form-label">Pekerjaan <span class="text-danger">*</span></label>
                    <input name="pekerjaan" value="{{ old('pekerjaan') }}" list="daftarPekerjaan" class="form-control form-control-lg @error('pekerjaan') is-invalid @enderror" required>
                    <datalist id="daftarPekerjaan">
                        @foreach (['Belum/Tidak Bekerja', 'Mengurus Rumah Tangga', 'Pelajar/Mahasiswa', 'Wiraswasta', 'Pedagang', 'Karyawan Swasta', 'Pegawai Negeri Sipil', 'Guru', 'Petani/Pekebun', 'Buruh Harian Lepas', 'Pensiunan', 'TNI', 'Polri'] as $v)<option value="{{ $v }}">@endforeach
                    </datalist>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Kewarganegaraan <span class="text-danger">*</span></label>
                    <select name="kewarganegaraan" class="form-select form-select-lg" required>{!! $pilih('kewarganegaraan', ['WNI', 'WNA'], 'WNI') !!}</select>
                </div>
                <div class="col-md-8">
                    <label class="form-label">Alamat (Jalan/Gang/No. Rumah) <span class="text-danger">*</span></label>
                    <input name="alamat" value="{{ old('alamat') }}" class="form-control form-control-lg @error('alamat') is-invalid @enderror" required>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label">RT <span class="text-danger">*</span></label>
                    <input name="rt" value="{{ old('rt') }}" class="form-control form-control-lg @error('rt') is-invalid @enderror" inputmode="numeric" maxlength="3" required>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label">RW <span class="text-danger">*</span></label>
                    <input name="rw" value="{{ old('rw') }}" class="form-control form-control-lg @error('rw') is-invalid @enderror" inputmode="numeric" maxlength="3" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Nomor HP/WhatsApp <span class="badge text-bg-light">opsional</span></label>
                    <input name="no_hp" value="{{ old('no_hp') }}" class="form-control form-control-lg @error('no_hp') is-invalid @enderror" inputmode="tel" placeholder="08xxxxxxxxxx">
                </div>
            </div>
        </div>

        {{-- 3. Keterangan khusus layanan --}}
        @if (! empty($layanan->isian))
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-ui-checks me-1"></i> {{ empty($layanan->persyaratan) ? 2 : 3 }}. Keterangan Permohonan</div>
            <div class="card-body row g-3">
                @foreach ($layanan->isian as $isian)
                    @php($nama = 'isian['.$isian['key'].']')
                    @php($lama = old('isian.'.$isian['key']))
                    <div class="{{ $isian['tipe'] === 'paragraf' ? 'col-12' : 'col-md-6' }}">
                        <label class="form-label">{{ $isian['label'] }} @if ($isian['wajib'])<span class="text-danger">*</span>@endif</label>
                        @if ($isian['tipe'] === 'pilihan')
                            <select name="{{ $nama }}" class="form-select form-select-lg @error('isian.'.$isian['key']) is-invalid @enderror" @required($isian['wajib'])>
                                <option value="">- pilih -</option>
                                @foreach ($isian['pilihan'] ?? [] as $no => $opsi)
                                    <option value="{{ $opsi }}" @selected($lama === $opsi)>{{ $no + 1 }}. {{ $opsi }}</option>
                                @endforeach
                            </select>
                        @elseif ($isian['tipe'] === 'paragraf')
                            <textarea name="{{ $nama }}" rows="{{ $isian['key'] === 'anggota_yang_pindah' ? 4 : 2 }}" class="form-control form-control-lg @error('isian.'.$isian['key']) is-invalid @enderror" @required($isian['wajib'])
                                @if ($isian['key'] === 'anggota_yang_pindah') placeholder="6303xxxxxxxxxxxx - NAMA - Anak" @endif>{{ $lama }}</textarea>
                            @if ($isian['key'] === 'anggota_yang_pindah')
                                <div class="form-text">Satu orang per baris: <b>NIK - Nama - Keterangan</b> (maks. 6 orang). Kosongkan kalau yang pindah hanya pemohon.</div>
                            @endif
                        @else
                            <input type="{{ ['tanggal' => 'date', 'angka' => 'number'][$isian['tipe']] ?? 'text' }}" name="{{ $nama }}" value="{{ $lama }}" class="form-control form-control-lg @error('isian.'.$isian['key']) is-invalid @enderror" @required($isian['wajib'])>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
        @endif

        <button class="btn btn-primary btn-lg w-100 py-3" id="tombolKirim"><i class="bi bi-ticket-perforated me-1"></i> Simpan &amp; Ambil Nomor Antrian</button>
    </form>
</div>
@endsection

@push('js')
<script>
    const form = document.getElementById('formPengajuan');
    form.addEventListener('submit', function () {
        const b = document.getElementById('tombolKirim');
        b.disabled = true;
        b.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...';
    });

    // Isi otomatis dari data penduduk
    const nik = document.getElementById('nik');
    const info = document.getElementById('infoNik');
    async function cariNik() {
        if (!/^\d{16}$/.test(nik.value)) { info.innerHTML = '<span class="text-danger">NIK harus 16 digit angka.</span>'; return; }
        info.textContent = 'Mencari...';
        try {
            const r = await fetch('{{ route('admin.pengajuan.cari-nik') }}?nik=' + nik.value, { headers: { Accept: 'application/json' } });
            const j = await r.json();
            if (!j.ditemukan) { info.innerHTML = '<span class="text-warning-emphasis"><i class="bi bi-info-circle"></i> NIK belum ada di data penduduk. Isi manual.</span>'; return; }
            for (const [k, v] of Object.entries(j.data)) {
                const el = form.elements[k];
                if (el && v !== null && v !== '') el.value = v;
            }
            info.innerHTML = '<span class="text-success"><i class="bi bi-check-circle-fill"></i> Data terisi dari data penduduk. Cocokkan dengan KTP warga.</span>'
                + (j.status !== 'aktif' ? ' <span class="badge text-bg-danger">Status penduduk: ' + j.status + '</span>' : '');
        } catch (e) {
            info.innerHTML = '<span class="text-danger">Gagal mencari. Cek koneksi ke PC kelurahan.</span>';
        }
    }
    document.getElementById('tombolCariNik').addEventListener('click', cariNik);
    nik.addEventListener('input', () => { if (nik.value.length === 16) cariNik(); });
</script>
@endpush
