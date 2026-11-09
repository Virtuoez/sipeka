@php($s = \App\Models\Pengaturan::semua())
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SIPEKA - Kelurahan {{ $s['nama_kelurahan'] }}</title>
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @include('partials.pwa')
    <style>
        .tombol-peran { border: 0; border-radius: 18px; padding: 28px 16px; width: 100%; color: #fff; transition: transform .1s, box-shadow .1s; }
        .tombol-peran:active { transform: scale(.98); }
        .tombol-peran:hover { box-shadow: 0 10px 26px rgba(0, 0, 0, .25); }
        .tombol-peran i { font-size: 3rem; display: block; margin-bottom: 6px; }
        .tombol-peran .judul { font-size: 1.5rem; font-weight: 700; }
        .peran-pelayanan { background: linear-gradient(135deg, #1b8a66, #0f6b4f); }
        .peran-kasi { background: linear-gradient(135deg, #c9a227, #a8841a); }
        .tombol-pc { border-radius: 14px; padding: 18px 12px; font-size: 1.15rem; font-weight: 600; }
    </style>
</head>
<body class="hero d-flex align-items-center min-vh-100 py-4">
    <div class="container position-relative" style="max-width: 560px; z-index: 1">
        <div class="card p-4">
            <div class="text-center mb-4">
                <img src="{{ asset('icons/ikon-192.png') }}" alt="" width="64" height="64" class="mb-2">
                <h4 class="fw-bold mb-0 text-dark">SIPEKA Kelurahan {{ $s['nama_kelurahan'] }}</h4>
                <div class="text-muted" id="petunjuk">Perangkat ini dipakai sebagai?</div>
            </div>

            @include('partials.flash')

            {{-- Langkah 1: pilih peran --}}
            <div id="langkahPeran" class="row g-3">
                <div class="col-6">
                    @if ($pelayanan->count() === 1)
                        <form method="post" action="{{ route('masuk', $pelayanan->first()) }}">
                            @csrf
                            <button class="tombol-peran peran-pelayanan"><i class="bi bi-person-badge"></i><span class="judul">Pelayanan</span><div class="small opacity-75">Tablet meja depan</div></button>
                        </form>
                    @else
                        <button type="button" class="tombol-peran peran-pelayanan" onclick="tampilkan('pelayanan')" @disabled($pelayanan->isEmpty())><i class="bi bi-person-badge"></i><span class="judul">Pelayanan</span><div class="small opacity-75">Tablet meja depan</div></button>
                    @endif
                </div>
                <div class="col-6">
                    <button type="button" class="tombol-peran peran-kasi" onclick="tampilkan('kasi')" @disabled($kasi->isEmpty())><i class="bi bi-pc-display"></i><span class="judul">Kasi</span><div class="small opacity-75">Komputer pembuat surat</div></button>
                </div>
            </div>

            {{-- Langkah 2: pilih komputer (Kasi) atau petugas (kalau Pelayanan lebih dari satu) --}}
            @foreach (['kasi' => $kasi, 'pelayanan' => $pelayanan] as $peran => $daftar)
                @if ($peran === 'kasi' || $daftar->count() > 1)
                    <div id="pilih-{{ $peran }}" class="d-none">
                        <div class="row g-2">
                            @foreach ($daftar as $u)
                                @php($sedangDipakai = in_array($u->id, $dipakai))
                                <div class="col-sm-{{ $daftar->count() >= 3 ? 4 : 6 }}">
                                    <form method="post" action="{{ route('masuk', $u) }}" @if ($sedangDipakai) onsubmit="return confirm('{{ $u->name }} sepertinya sedang dibuka di perangkat lain. Tetap pakai di perangkat ini?')" @endif>
                                        @csrf
                                        <button class="btn {{ $peran === 'kasi' ? 'btn-outline-warning text-dark' : 'btn-outline-success' }} tombol-pc w-100">
                                            <i class="bi {{ $peran === 'kasi' ? 'bi-pc-display' : 'bi-person-badge' }} d-block fs-2"></i>
                                            {{ $u->name }}
                                            @if ($sedangDipakai)<div class="small fw-normal text-muted">sedang dipakai</div>@endif
                                        </button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                        <button type="button" class="btn btn-link w-100 mt-2" onclick="kembali()"><i class="bi bi-arrow-left"></i> Kembali</button>
                    </div>
                @endif
            @endforeach

            <div class="text-center mt-4 small">
                <button type="button" class="btn btn-outline-success btn-sm d-none mb-2" data-pasang-aplikasi onclick="pasangAplikasi()"><i class="bi bi-download me-1"></i> Pasang sebagai Aplikasi</button><br>
                <a href="{{ route('login.admin') }}" class="text-muted"><i class="bi bi-shield-lock"></i> Masuk sebagai Admin</a>
            </div>
        </div>
        <p class="text-center text-white-50 small mt-3 mb-0">Pilihan ini diingat di perangkat ini. Untuk mengganti, buka menu nama di pojok kanan atas lalu <b>Ganti Peran</b>.</p>
    </div>

    <script>
        const judulPilih = { kasi: 'Pilih komputer Kasi yang ini:', pelayanan: 'Pilih petugas Pelayanan:' };
        function tampilkan(peran) {
            document.getElementById('langkahPeran').classList.add('d-none');
            document.getElementById('pilih-' + peran).classList.remove('d-none');
            document.getElementById('petunjuk').textContent = judulPilih[peran];
        }
        function kembali() {
            document.querySelectorAll('[id^="pilih-"]').forEach((el) => el.classList.add('d-none'));
            document.getElementById('langkahPeran').classList.remove('d-none');
            document.getElementById('petunjuk').textContent = 'Perangkat ini dipakai sebagai?';
        }
    </script>
</body>
</html>
