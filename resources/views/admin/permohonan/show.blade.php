@extends('layouts.admin')
@section('judul', 'Detail Permohonan')

@section('konten')
@php($d = $p->data ?? [])
@php($u = auth()->user())
@php($milikSaya = $p->status === 'diproses' && $p->petugas_id === $u->id)
<div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
    <div class="d-flex align-items-center gap-3">
        <div class="text-center bg-white rounded-3 shadow-sm px-3 py-2">
            <div class="small text-muted lh-1">Antrian</div>
            <div class="fs-2 fw-bold text-primary lh-sm">{{ $p->nomorLabel() }}</div>
        </div>
        <div>
            <a href="{{ $u->isKasi() ? route('admin.antrean') : ($u->isPelayanan() ? route('admin.cek-antrian', ['tanggal' => $p->tanggal_antrian?->toDateString()]) : route('admin.permohonan.index')) }}" class="small"><i class="bi bi-arrow-left"></i> Kembali</a>
            <h4 class="fw-bold mb-0 mt-1">{{ $p->layanan->nama }}</h4>
            <div class="text-muted small">Masuk {{ $p->created_at->locale('id')->translatedFormat('l, d F Y \p\u\k\u\l H:i') }}</div>
        </div>
    </div>
    <span class="badge text-bg-{{ $p->statusWarna() }} fs-6">{{ $p->statusLabel() }}</span>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-person-vcard me-1"></i> Data Pemohon</div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    @foreach ([
                        'NIK' => $p->nik, 'No. KK' => $d['no_kk'] ?? '-', 'Nama' => $p->nama,
                        'Tempat, Tgl. Lahir' => ($d['tempat_lahir'] ?? '').', '.\App\Services\SuratService::tanggalIndo($d['tanggal_lahir'] ?? null),
                        'Jenis Kelamin' => $d['jenis_kelamin'] ?? '-', 'Agama' => $d['agama'] ?? '-',
                        'Status Perkawinan' => $d['status_perkawinan'] ?? '-', 'Pekerjaan' => $d['pekerjaan'] ?? '-',
                        'Kewarganegaraan' => $d['kewarganegaraan'] ?? '-',
                        'Alamat' => ($d['alamat'] ?? '').' RT '.($d['rt'] ?? '-').' RW '.($d['rw'] ?? '-'),
                        'No. HP' => $p->no_hp,
                    ] as $label => $nilai)
                        <tr><th class="fw-normal text-muted" style="width: 35%">{{ $label }}</th><td>{{ $nilai }}</td></tr>
                    @endforeach
                    @foreach ($p->layanan->isian ?? [] as $isian)
                        <tr>
                            <th class="fw-normal text-muted">{{ $isian['label'] }}</th>
                            <td>{{ $isian['tipe'] === 'tanggal' ? \App\Services\SuratService::tanggalIndo($d[$isian['key']] ?? null) : ($d[$isian['key']] ?? '-') }}</td>
                        </tr>
                    @endforeach
                </table>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between">
                <span><i class="bi bi-folder-check me-1"></i> Berkas Fisik yang Dibawa Warga</span>
                @if ($p->penginput)<span class="small fw-normal text-muted">dicek oleh {{ $p->penginput->name }}</span>@endif
            </div>
            <ul class="list-group list-group-flush">
                @php($dibawa = $d['berkas_dibawa'] ?? [])
                @forelse ($p->layanan->persyaratan ?? [] as $s)
                    @php($ada = in_array($s['label'], $dibawa, true))
                    <li class="list-group-item d-flex align-items-center gap-2">
                        <i class="bi {{ $ada ? 'bi-check-square-fill text-success' : 'bi-square text-muted' }} fs-5"></i>
                        <span class="{{ $ada ? '' : 'text-muted' }}">{{ $s['label'] }}</span>
                        @unless ($ada)<span class="badge text-bg-light ms-auto">{{ $s['wajib'] ? 'tidak dicentang' : 'tidak dibawa (opsional)' }}</span>@endunless
                    </li>
                @empty
                    <li class="list-group-item text-muted small">Layanan ini tidak memerlukan berkas.</li>
                @endforelse
            </ul>
        </div>

        @if ($p->berkas->isNotEmpty())
        {{-- Unggahan dari versi lama (sebelum berkas dibawa langsung ke kantor) --}}
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-paperclip me-1"></i> Berkas Unggahan Lama ({{ $p->berkas->count() }})</div>
            <div class="card-body">
                <div class="row g-3">
                    @foreach ($p->berkas as $b)
                        <div class="col-6 col-md-4">
                            <a href="{{ route('admin.berkas', $b) }}" target="_blank" class="card text-decoration-none border">
                                @if ($b->isGambar())
                                    <img src="{{ route('admin.berkas', $b) }}" class="lampiran-thumb" alt="{{ $b->label }}">
                                @else
                                    <div class="lampiran-pdf"><i class="bi bi-file-earmark-pdf"></i></div>
                                @endif
                                <div class="p-2 small">
                                    <div class="fw-semibold text-body">{{ $b->label }}</div>
                                    <div class="text-muted text-truncate">{{ basename($b->path) }}</div>
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif
    </div>

    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-lightning me-1"></i> Tindakan</div>
            <div class="card-body d-grid gap-2">
                @if ($p->status === 'baru')
                    @if ($u->punyaPeran('admin', 'kasi'))
                        <form method="post" action="{{ route('admin.permohonan.ambil', $p) }}" class="d-grid">
                            @csrf
                            <button class="btn btn-success"><i class="bi bi-hand-index-thumb me-1"></i> Ambil &amp; Kerjakan</button>
                        </form>
                        <div class="small text-muted">Setelah diambil, permohonan ini hilang dari antrean komputer lain.</div>
                    @else
                        <div class="alert alert-info small mb-0"><i class="bi bi-hourglass-split"></i> Menunggu diambil oleh Kasi.</div>
                    @endif
                @elseif ($p->status === 'diproses')
                    @if ($milikSaya)
                        <a href="{{ route('admin.permohonan.surat', $p) }}" class="btn btn-primary"><i class="bi bi-pencil-square me-1"></i> {{ $p->surat_html ? 'Lanjutkan Edit Surat' : 'Buat Surat' }}</a>
                        <form method="post" action="{{ route('admin.permohonan.lepas', $p) }}" class="d-grid" onsubmit="return confirm('Kembalikan ke antrean? Komputer lain bisa mengambilnya.')">
                            @csrf
                            <button class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-return-left me-1"></i> Kembalikan ke Antrean</button>
                        </form>
                    @else
                        <div class="alert alert-warning small mb-0">
                            <i class="bi bi-person-workspace"></i> Sedang dikerjakan oleh <strong>{{ $p->petugas?->name ?? '-' }}</strong>
                            sejak {{ $p->diambil_at?->format('H:i') }} ({{ $p->diambil_at?->diffForHumans() }}).
                        </div>
                        @if ($u->punyaPeran('admin', 'pelayanan'))
                            <form method="post" action="{{ route('admin.permohonan.lepas', $p) }}" class="d-grid" onsubmit="return confirm('Tarik paksa dari {{ $p->petugas?->name }} dan kembalikan ke antrean? Pakai ini kalau komputernya rusak atau petugasnya tidak masuk.')">
                                @csrf
                                <button class="btn btn-outline-warning btn-sm"><i class="bi bi-arrow-return-left me-1"></i> Tarik ke Antrean</button>
                            </form>
                        @endif
                    @endif
                @elseif ($p->status === 'selesai')
                    <div class="alert alert-success small mb-1">
                        <strong>No. Surat:</strong> {{ $p->nomor_surat }}<br>
                        Selesai {{ $p->selesai_at?->locale('id')->translatedFormat('d F Y H:i') }} oleh {{ $p->petugas?->name ?? '-' }}
                    </div>
                    <div class="btn-group">
                        <a href="{{ route('admin.permohonan.unduh', [$p, 'pdf']) }}" target="_blank" class="btn btn-danger"><i class="bi bi-file-earmark-pdf me-1"></i> PDF</a>
                        <a href="{{ route('admin.permohonan.unduh', [$p, 'docx']) }}" class="btn btn-primary"><i class="bi bi-file-earmark-word me-1"></i> Word</a>
                    </div>
                    <a href="{{ route('admin.permohonan.surat', $p) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-pencil me-1"></i> Revisi Surat</a>
                @endif

                @if ($p->bolehDitolak($u))
                    <button class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalTolak"><i class="bi bi-x-circle me-1"></i> Tolak Permohonan</button>
                @endif
                @if ($p->status === 'ditolak')
                    <div class="alert alert-danger small mb-0"><strong>Alasan ditolak:</strong> {{ $p->catatan }}</div>
                @endif
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-folder2-open me-1"></i> Folder Arsip</div>
            <div class="card-body">
                <div class="small font-monospace bg-light rounded p-2 mb-2 text-break">{{ $p->folder_path }}</div>
                <div class="d-grid gap-2">
                    @if ($u->punyaPeran('admin', 'pelayanan'))
                        <a href="{{ route('admin.arsip.index', ['path' => $p->folder_path]) }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-folder2-open me-1"></i> Buka Folder</a>
                    @endif
                    <a href="{{ route('admin.permohonan.zip', $p) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-file-zip me-1"></i> Unduh Semua (ZIP)</a>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-people me-1"></i> Pencocokan Data Penduduk</div>
            <div class="card-body small">
                @if ($penduduk)
                    <p class="mb-2"><i class="bi bi-check-circle-fill text-success"></i> NIK terdaftar sebagai <a href="{{ route('admin.penduduk.show', $penduduk) }}">{{ $penduduk->nama }}</a> (RT {{ $penduduk->rt }}/RW {{ $penduduk->rw }}, {{ $penduduk->status }}).</p>
                    @if (mb_strtolower($penduduk->nama) !== mb_strtolower($p->nama))
                        <p class="text-warning mb-2"><i class="bi bi-exclamation-triangle"></i> Nama berbeda dengan data penduduk.</p>
                    @endif
                @else
                    <p class="mb-2"><i class="bi bi-question-circle-fill text-warning"></i> NIK belum ada di data penduduk.</p>
                @endif
                <form method="post" action="{{ route('admin.permohonan.ke-penduduk', $p) }}">
                    @csrf
                    <button class="btn btn-sm btn-outline-primary w-100">{{ $penduduk ? 'Perbarui' : 'Tambahkan ke' }} Data Penduduk</button>
                </form>
            </div>
        </div>

        @if (auth()->user()->isAdmin())
            <form method="post" action="{{ route('admin.permohonan.destroy', $p) }}" onsubmit="return confirm('Hapus permohonan ini beserta seluruh berkas di foldernya? Tindakan ini tidak bisa dibatalkan.')">
                @csrf @method('DELETE')
                <button class="btn btn-link text-danger btn-sm p-0"><i class="bi bi-trash"></i> Hapus permohonan</button>
            </form>
        @endif
    </div>
</div>

<div class="modal fade" id="modalTolak" tabindex="-1">
    <div class="modal-dialog">
        <form method="post" action="{{ route('admin.permohonan.tolak', $p) }}" class="modal-content">
            @csrf
            <div class="modal-header"><h5 class="modal-title">Tolak Permohonan</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <label class="form-label">Alasan penolakan (akan dilihat pemohon)</label>
                <textarea name="catatan" class="form-control" rows="3" required placeholder="Contoh: Foto KK buram, mohon unggah ulang dengan foto yang jelas."></textarea>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-danger">Tolak</button></div>
        </form>
    </div>
</div>
@endsection
