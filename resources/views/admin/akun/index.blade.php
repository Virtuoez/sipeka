@extends('layouts.admin')
@section('judul', 'Akun Petugas')

@section('konten')
<div class="row g-3">
    <div class="col-lg-7">
        <div class="card">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead><tr><th>Nama (tampil di tombol)</th><th>Cara masuk</th><th>Peran</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($daftar as $u)
                            <tr>
                                <td>{{ $u->name }}</td>
                                <td class="small">{!! $u->isAdmin() ? '<i class="bi bi-shield-lock"></i> '.e($u->email) : '<i class="bi bi-hand-index"></i> tombol, tanpa password' !!}</td>
                                <td><span class="badge text-bg-{{ ['admin' => 'danger', 'pelayanan' => 'primary', 'kasi' => 'success'][$u->role] ?? 'secondary' }}">{{ $u->labelPeran() }}</span></td>
                                <td class="text-end">
                                    @unless ($u->is(auth()->user()))
                                        <form method="post" action="{{ route('admin.akun.destroy', $u) }}" onsubmit="return confirm('Hapus akun {{ $u->name }}?')">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                        </form>
                                    @endunless
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <form method="post" action="{{ route('admin.akun.store') }}" class="card">
            @csrf
            <div class="card-header">Tambah Akun</div>
            <div class="card-body">
                <div class="mb-2"><label class="form-label">Nama</label><input name="name" value="{{ old('name') }}" class="form-control" placeholder="mis. Kasi PC 4" required></div>
                <div class="mb-2">
                    <label class="form-label">Peran</label>
                    <select name="role" class="form-select" id="pilihPeran">
                        <option value="kasi" @selected(old('role') === 'kasi')>Kasi (ambil &amp; kerjakan surat dari antrean)</option>
                        <option value="pelayanan" @selected(old('role') === 'pelayanan')>Pelayanan (pantau &amp; verifikasi, template, arsip, data penduduk)</option>
                        <option value="admin" @selected(old('role') === 'admin')>Admin (semua menu + pengaturan &amp; akun)</option>
                    </select>
                    <div class="form-text">Kasi & Pelayanan masuk cukup dengan menekan tombol bernama ini. Untuk Kasi, buat satu akun per komputer.</div>
                </div>
                <div id="isianAdmin">
                    <div class="mb-2"><label class="form-label">Email</label><input type="email" name="email" value="{{ old('email') }}" class="form-control"></div>
                    <div class="mb-3"><label class="form-label">Password (min. 8 karakter)</label><input type="password" name="password" class="form-control"></div>
                </div>
                <button class="btn btn-primary w-100">Buat Akun</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('js')
<script>
    // Email & password hanya untuk Admin
    const peran = document.getElementById('pilihPeran');
    const isianAdmin = document.getElementById('isianAdmin');
    const atur = () => isianAdmin.classList.toggle('d-none', peran.value !== 'admin');
    peran.addEventListener('change', atur);
    atur();
</script>
@endpush
