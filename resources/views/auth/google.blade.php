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
        .tombol-google { display: flex; align-items: center; justify-content: center; gap: 12px; width: 100%; padding: 14px 16px;
                         border: 1px solid #dadce0; border-radius: 12px; background: #fff; color: #3c4043; font-weight: 600; font-size: 1.05rem; }
        .tombol-google:hover { background: #f8f9fa; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
        .tombol-google svg { width: 22px; height: 22px; }
    </style>
</head>
<body class="hero d-flex align-items-center min-vh-100 py-4">
    <div class="container position-relative" style="max-width: 440px; z-index: 1">
        <div class="card p-4">
            <div class="text-center mb-4">
                <img src="{{ asset('icons/ikon-192.png') }}" alt="" width="64" height="64" class="mb-2">
                <h4 class="fw-bold mb-0 text-dark">SIPEKA Kelurahan {{ $s['nama_kelurahan'] }}</h4>
                <div class="text-muted">Masuk untuk petugas kelurahan</div>
            </div>

            @include('partials.flash')

            @if ($googleSiap)
                <a href="{{ route('google') }}" class="tombol-google text-decoration-none mb-2">
                    {{-- Logo "G" Google --}}
                    <svg viewBox="0 0 48 48" aria-hidden="true"><path fill="#EA4335" d="M24 9.5c3.5 0 6.6 1.2 9.1 3.6l6.8-6.8C35.7 2.4 30.2 0 24 0 14.6 0 6.6 5.4 2.7 13.3l7.9 6.1C12.5 13.6 17.8 9.5 24 9.5z"/><path fill="#4285F4" d="M46.5 24.5c0-1.6-.1-3.1-.4-4.5H24v9h12.7c-.6 3-2.3 5.5-4.8 7.2l7.7 6c4.5-4.2 6.9-10.3 6.9-17.7z"/><path fill="#FBBC05" d="M10.6 28.6c-.5-1.4-.8-3-.8-4.6s.3-3.2.8-4.6l-7.9-6.1C1 16.6 0 20.2 0 24s1 7.4 2.7 10.7l7.9-6.1z"/><path fill="#34A853" d="M24 48c6.5 0 11.9-2.1 15.9-5.8l-7.7-6c-2.1 1.4-4.9 2.3-8.2 2.3-6.2 0-11.5-4.2-13.4-9.9l-7.9 6.1C6.6 42.6 14.6 48 24 48z"/></svg>
                    Masuk dengan Google
                </a>
                <p class="small text-muted text-center mb-0">Gunakan akun Google yang sudah didaftarkan Admin.</p>
            @else
                <div class="alert alert-warning small mb-0"><i class="bi bi-exclamation-triangle"></i> Login Google belum diaktifkan oleh Admin.</div>
            @endif

            @if (config('app.tamu'))
                <div class="text-center text-muted small my-3">atau</div>
                <form method="post" action="{{ route('masuk.tamu') }}">
                    @csrf
                    <button class="btn btn-outline-success w-100 py-2"><i class="bi bi-person-badge me-1"></i> Coba sebagai Tamu</button>
                </form>
                <p class="small text-muted text-center mt-2 mb-0">Mode demo: coba Pelayanan &amp; Kasi dengan data contoh.</p>
            @endif

            <div class="text-center mt-4 small">
                <button type="button" class="btn btn-outline-success btn-sm d-none mb-2" data-pasang-aplikasi onclick="pasangAplikasi()"><i class="bi bi-download me-1"></i> Pasang sebagai Aplikasi</button><br>
                <a href="{{ route('login.admin') }}" class="text-muted"><i class="bi bi-shield-lock"></i> Masuk Admin dengan password</a>
            </div>
        </div>
    </div>
</body>
</html>
