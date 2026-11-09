@extends('layouts.admin')
@section('judul', 'Akun Petugas')

@section('konten')
@if (config('app.wajib_pin') && $daftar->contains(fn ($u) => ! $u->isAdmin() && ! $u->punyaPin()))
    <div class="alert alert-warning"><i class="bi bi-exclamation-triangle"></i> Versi ini <b>mewajibkan PIN</b>. Akun Pelayanan/Kasi yang belum punya PIN tidak bisa masuk.</div>
@endif
<div class="row g-3">
    <div class="col-lg-8">
        <div class="card">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead><tr><th>Nama (tampil di tombol)</th><th>Peran</th><th>Cara masuk / PIN</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($daftar as $u)
                            <tr>
                                <td>{{ $u->name }}</td>
                                <td><span class="badge text-bg-{{ ['admin' => 'danger', 'pelayanan' => 'primary', 'kasi' => 'success'][$u->role] ?? 'secondary' }}">{{ $u->labelPeran() }}</span></td>
                                <td class="small">
                                    @if ($u->isAdmin())
                                        <i class="bi bi-shield-lock"></i> {{ $u->email }}
                                    @else
                                        <div class="mb-1">
                                            @if ($u->punyaPin())
                                                <span class="badge text-bg-success"><i class="bi bi-lock-fill"></i> tombol + PIN</span>
                                            @else
                                                <span class="badge text-bg-{{ config('app.wajib_pin') ? 'danger' : 'light' }}">{{ config('app.wajib_pin') ? 'belum ada PIN (tidak bisa masuk)' : 'tombol, tanpa PIN' }}</span>
                                            @endif
                                        </div>
                                        <form method="post" action="{{ route('admin.akun.pin', $u) }}" class="d-flex gap-1">
                                            @csrf @method('PUT')
                                            <input type="password" name="pin" class="form-control form-control-sm" style="max-width: 110px" inputmode="numeric" pattern="\d{4,6}" maxlength="6" placeholder="{{ $u->punyaPin() ? 'PIN baru' : 'Atur PIN' }}" autocomplete="new-password" required>
                                            <button class="btn btn-sm btn-outline-primary">Simpan</button>
                                        </form>
                                        @if ($u->punyaPin())
                                            <form method="post" action="{{ route('admin.akun.pin', $u) }}" class="mt-1" onsubmit="return confirm('Hapus PIN {{ $u->name }}?')">
                                                @csrf @method('PUT')
                                                <input type="hidden" name="hapus" value="1">
                                                <button class="btn btn-link btn-sm text-danger p-0">Hapus PIN</button>
                                            </form>
                                        @endif
                                    @endif
                                </td>
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
        <p class="small text-muted mt-2">PIN 4–6 angka. Salah 5 kali → akun dikunci 15 menit (menyimpan PIN baru membuka kuncinya). Beritahu PIN langsung ke petugasnya, jangan lewat grup WA.</p>
    </div>
    <div class="col-lg-4">
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
                    <div class="form-text">Kasi & Pelayanan masuk dengan menekan tombol bernama ini. Untuk Kasi, buat satu akun per komputer.</div>
                </div>
                <div id="isianPetugas" class="mb-3">
                    <label class="form-label">PIN (4–6 angka){{ config('app.wajib_pin') ? '' : ', opsional' }}</label>
                    <input type="password" name="pin" class="form-control" inputmode="numeric" pattern="\d{4,6}" maxlength="6" autocomplete="new-password">
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
    // Email & password hanya untuk Admin; PIN hanya untuk Pelayanan/Kasi
    const peran = document.getElementById('pilihPeran');
    const atur = () => {
        document.getElementById('isianAdmin').classList.toggle('d-none', peran.value !== 'admin');
        document.getElementById('isianPetugas').classList.toggle('d-none', peran.value === 'admin');
    };
    peran.addEventListener('change', atur);
    atur();
</script>
@endpush
