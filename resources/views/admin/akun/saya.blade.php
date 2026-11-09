@extends('layouts.admin')
@section('judul', 'Ganti Password')

@section('konten')
<form method="post" action="{{ route('admin.akun.password') }}" class="card" style="max-width: 480px">
    @csrf @method('PUT')
    <div class="card-body">
        <div class="mb-2"><label class="form-label">Password Lama</label><input type="password" name="password_lama" class="form-control" required></div>
        <div class="mb-2"><label class="form-label">Password Baru (min. 8 karakter)</label><input type="password" name="password" class="form-control" required></div>
        <div class="mb-3"><label class="form-label">Ulangi Password Baru</label><input type="password" name="password_confirmation" class="form-control" required></div>
        <button class="btn btn-primary">Simpan</button>
    </div>
</form>
@endsection
