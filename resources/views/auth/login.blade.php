@php($s = \App\Models\Pengaturan::semua())
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk Admin - Kelurahan {{ $s['nama_kelurahan'] }}</title>
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v=5">
    @include('partials.pwa')
</head>
<body class="hero d-flex align-items-center min-vh-100 py-4">
    <div class="container position-relative" style="max-width: 420px; z-index: 1">
        <div class="card p-4">
            <div class="text-center mb-3">
                <img src="{{ asset('icons/logo-banjar.png') }}" alt="Logo Kabupaten Banjar" height="72" class="mb-2">
                <div class="small text-muted"><i class="bi bi-shield-lock"></i> Khusus Admin</div>
                <h5 class="fw-bold mb-0 text-dark">Masuk sebagai Admin</h5>
                <div class="small text-muted">Kelurahan {{ $s['nama_kelurahan'] }}</div>
            </div>
            @include('partials.flash')
            <form method="post" action="{{ route('login.admin') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label text-dark">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" class="form-control" required autofocus>
                </div>
                <div class="mb-3">
                    <label class="form-label text-dark">Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <button class="btn btn-primary w-100">Masuk</button>
            </form>
            <a href="{{ route('login') }}" class="small d-block text-center mt-3"><i class="bi bi-arrow-left"></i> Kembali ke pilihan Pelayanan / Kasi</a>
        </div>
    </div>
</body>
</html>
