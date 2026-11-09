@extends('layouts.admin')
@section('judul', 'Detail Penduduk')

@section('konten')
<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <a href="{{ route('admin.penduduk.index') }}" class="small"><i class="bi bi-arrow-left"></i> Kembali</a>
        <h4 class="fw-bold mb-0 mt-1">{{ $penduduk->nama }}</h4>
        <div class="text-muted font-monospace small">{{ $penduduk->nik }}</div>
    </div>
    @if (auth()->user()->punyaPeran('admin', 'pelayanan'))
        <a href="{{ route('admin.penduduk.edit', $penduduk) }}" class="btn btn-primary"><i class="bi bi-pencil me-1"></i> Ubah</a>
    @endif
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">Biodata</div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    @foreach ([
                        'No. KK' => $penduduk->no_kk, 'Tempat, Tgl. Lahir' => $penduduk->tempat_lahir.', '.\App\Services\SuratService::tanggalIndo($penduduk->tanggal_lahir),
                        'Umur' => $penduduk->umur !== null ? $penduduk->umur.' tahun' : '-', 'Jenis Kelamin' => $penduduk->jenis_kelamin,
                        'Agama' => $penduduk->agama, 'Status Perkawinan' => $penduduk->status_perkawinan, 'Pendidikan' => $penduduk->pendidikan,
                        'Pekerjaan' => $penduduk->pekerjaan, 'Golongan Darah' => $penduduk->golongan_darah, 'Kewarganegaraan' => $penduduk->kewarganegaraan,
                        'Alamat' => $penduduk->alamat.' RT '.$penduduk->rt.' RW '.$penduduk->rw, 'No. HP' => $penduduk->no_hp,
                        'Status' => ucfirst($penduduk->status), 'Keterangan' => $penduduk->keterangan,
                    ] as $label => $nilai)
                        <tr><th class="fw-normal text-muted" style="width: 40%">{{ $label }}</th><td>{{ $nilai ?: '-' }}</td></tr>
                    @endforeach
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card mb-3">
            <div class="card-header">Anggota Keluarga (KK {{ $penduduk->no_kk ?: '-' }})</div>
            <ul class="list-group list-group-flush">
                @forelse ($keluarga as $k)
                    <li class="list-group-item d-flex justify-content-between {{ $k->is($penduduk) ? 'bg-light' : '' }}">
                        <a href="{{ route('admin.penduduk.show', $k) }}" class="text-decoration-none">{{ $k->nama }}</a>
                        <span class="small text-muted">{{ $k->hubungan_keluarga }} &middot; {{ $k->umur ?? '-' }} th</span>
                    </li>
                @empty
                    <li class="list-group-item text-muted small">Belum ada No. KK.</li>
                @endforelse
            </ul>
        </div>
        <div class="card">
            <div class="card-header">Riwayat Permohonan Surat</div>
            <ul class="list-group list-group-flush">
                @forelse ($riwayat as $r)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <a href="{{ route('admin.permohonan.show', $r) }}" class="text-decoration-none small">{{ $r->layanan->nama }}<br><span class="text-muted">{{ $r->created_at->format('d/m/Y') }}</span></a>
                        <span class="badge text-bg-{{ $r->statusWarna() }}">{{ $r->statusLabel() }}</span>
                    </li>
                @empty
                    <li class="list-group-item text-muted small">Belum pernah mengajukan surat.</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
@endsection
