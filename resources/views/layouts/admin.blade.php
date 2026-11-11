@php($s = \App\Models\Pengaturan::semua())
@php($u = auth()->user())
@php($jumlahBaru = \App\Models\Permohonan::bagianKasi($u)->where('status', 'baru')->whereNull('petugas_id')->count())
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('judul', 'Dashboard') - Admin Kelurahan {{ $s['nama_kelurahan'] }}</title>
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @include('partials.pwa')
    @stack('css')
</head>
<body>
    <aside class="sidebar d-flex flex-column p-3" id="sidebar">
        <a href="{{ route('admin.dashboard') }}" class="d-flex align-items-center gap-2 text-decoration-none mb-3 px-1">
            <span class="brand-logo bg-white text-success">
                @if ($s['logo'])<img src="{{ asset('storage/'.$s['logo']) }}" alt="Logo">@else<i class="bi bi-building"></i>@endif
            </span>
            <span class="text-white lh-sm"><strong>SIPEKA</strong><br><small class="text-white-50">Kel. {{ $s['nama_kelurahan'] }}</small></span>
        </a>
        <nav class="nav flex-column flex-grow-1">
            @if ($u->isAdmin())
                <a class="nav-link @if (request()->routeIs('admin.dashboard')) active @endif" href="{{ route('admin.dashboard') }}"><i class="bi bi-speedometer2"></i> Dashboard</a>
            @endif

            <div class="judul-menu">Pelayanan</div>
            @if ($u->punyaPeran('admin', 'pelayanan'))
                <a class="nav-link @if (request()->routeIs('admin.pengajuan.*')) active @endif" href="{{ route('admin.pengajuan.index') }}"><i class="bi bi-plus-square"></i> Buat Permohonan</a>
            @endif
            @if ($u->punyaPeran('admin', 'kasi'))
                <a class="nav-link d-flex align-items-center @if (request()->routeIs('admin.antrean')) active @endif" href="{{ route('admin.antrean') }}">
                    <span><i class="bi bi-list-ol"></i> Antrean Surat</span>
                    <span class="ms-auto" data-segarkan="lencana-antrean">@if ($jumlahBaru)<span class="badge rounded-pill text-bg-warning">{{ $jumlahBaru }}</span>@endif</span>
                </a>
            @endif
            @if ($u->punyaPeran('admin', 'pelayanan'))
                <a class="nav-link @if (request()->routeIs('admin.cek-antrian')) active @endif" href="{{ route('admin.cek-antrian') }}"><i class="bi bi-123"></i> Cek Antrian</a>
                <a class="nav-link d-flex align-items-center @if (request()->routeIs('admin.permohonan.*')) active @endif" href="{{ route('admin.permohonan.index') }}">
                    <span><i class="bi bi-inbox"></i> Semua Permohonan</span>
                    <span class="ms-auto" data-segarkan="lencana-permohonan">@if ($jumlahBaru && $u->isPelayanan())<span class="badge rounded-pill text-bg-warning">{{ $jumlahBaru }}</span>@endif</span>
                </a>
            @endif
            @if ($u->punyaPeran('admin', 'pelayanan', 'kasi'))
                <a class="nav-link @if (request()->routeIs('admin.arsip.*')) active @endif" href="{{ route('admin.arsip.index') }}"><i class="bi bi-folder2-open"></i> Arsip Berkas</a>
            @endif

            <div class="judul-menu">Pendataan</div>
            <a class="nav-link @if (request()->routeIs('admin.penduduk.*')) active @endif" href="{{ route('admin.penduduk.index') }}"><i class="bi bi-people"></i> Data Penduduk</a>

            @if ($u->punyaPeran('admin', 'pelayanan') && ! $u->isTamu())
                <div class="judul-menu">Pengaturan</div>
                <a class="nav-link @if (request()->routeIs('admin.layanan.*')) active @endif" href="{{ route('admin.layanan.index') }}"><i class="bi bi-file-earmark-richtext"></i> Layanan &amp; Template</a>
            @endif
            @if ($u->isAdmin())
                <a class="nav-link @if (request()->routeIs('admin.pengaturan')) active @endif" href="{{ route('admin.pengaturan') }}"><i class="bi bi-gear"></i> Profil Kelurahan</a>
                <a class="nav-link @if (request()->routeIs('admin.akun.index')) active @endif" href="{{ route('admin.akun.index') }}"><i class="bi bi-person-gear"></i> Akun Petugas</a>
            @endif
        </nav>
        @unless ($u->isKasi() || $u->isTamu() || config('app.mode_masuk') === 'google')
            @php($alamatLan = \App\Support\Jaringan::alamat())
            @if ($alamatLan)
                <div class="small text-white-50 px-2 mt-2" title="Buka alamat ini di tablet / komputer lain yang tersambung ke WiFi kantor">
                    <i class="bi bi-wifi"></i> Alamat untuk tablet:<br>
                    @foreach ($alamatLan as $a)<span class="text-white font-monospace">{{ $a }}</span><br>@endforeach
                    <span style="font-size: .7rem">(PC kelurahan harus dijalankan dengan jalankan-jaringan.bat)</span>
                </div>
            @endif
        @endunless
    </aside>

    <div class="konten-admin d-flex flex-column">
        <header class="topbar-admin px-3 py-2 d-flex align-items-center gap-2 sticky-top">
            <button class="btn btn-light d-lg-none" onclick="document.getElementById('sidebar').classList.toggle('tampil')" aria-label="Menu"><i class="bi bi-list"></i></button>
            <h1 class="h6 mb-0 fw-semibold">@yield('judul', 'Dashboard')</h1>
            <button type="button" class="btn btn-success btn-sm ms-auto d-none" data-pasang-aplikasi onclick="pasangAplikasi()"><i class="bi bi-download me-1"></i> Pasang Aplikasi</button>
            <div class="dropdown ms-2">
                <button class="btn btn-light btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="bi bi-person-circle me-1"></i> {{ $u->name }}
                    <span class="badge text-bg-{{ ['admin' => 'danger', 'pelayanan' => 'primary', 'kasi' => 'success'][$u->role] ?? 'secondary' }}">{{ $u->labelPeran() }}</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    @if ($u->isAdmin())
                        <li><a class="dropdown-item" href="{{ route('admin.akun.saya') }}"><i class="bi bi-key me-1"></i> Ganti Password</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="post" action="{{ route('logout') }}">@csrf<button class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-1"></i> Keluar</button></form>
                        </li>
                    @else
                        <li>
                            <form method="post" action="{{ route('logout') }}" onsubmit="return confirm('Ganti peran perangkat ini? Anda akan kembali ke layar pilihan Pelayanan / Kasi.')">@csrf<button class="dropdown-item"><i class="bi bi-arrow-left-right me-1"></i> Ganti Peran</button></form>
                        </li>
                    @endif
                </ul>
            </div>
        </header>
        <main class="p-3 p-lg-4 flex-grow-1">
            @if ($u->isTamu())
                <div class="alert alert-info py-2 small"><i class="bi bi-eyeglasses"></i> <b>Mode Tamu (demo).</b> Silakan coba Pelayanan &amp; Kasi. Yang tampil hanya <b>data contoh</b>, dan dibersihkan setiap hari.</div>
            @endif
            @include('partials.flash')
            @yield('konten')
        </main>
    </div>

    <script src="{{ asset('vendor/bootstrap/bootstrap.bundle.min.js') }}"></script>
    @stack('js')
</body>
</html>
