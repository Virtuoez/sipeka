@extends('layouts.admin')
@section('judul', 'Akun Petugas')

@section('konten')
@php($modeGoogle = config('app.mode_masuk') === 'google')
@if ($modeGoogle)
    <div class="alert alert-info small"><i class="bi bi-google"></i> Petugas masuk dengan <b>akun Google</b>. Isi <b>email Google</b> masing-masing petugas di bawah. Email yang tidak terdaftar akan ditolak.</div>
@elseif (config('app.wajib_pin') && $daftar->contains(fn ($u) => ! $u->isAdmin() && ! $u->punyaPin()))
    <div class="alert alert-warning"><i class="bi bi-exclamation-triangle"></i> Versi ini <b>mewajibkan PIN</b>. Akun Pelayanan/Kasi yang belum punya PIN tidak bisa masuk.</div>
@endif
<div class="row g-3">
    <div class="col-lg-8">
        <div class="card">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead><tr><th>Nama</th><th>Peran</th><th>{{ $modeGoogle ? 'Email Google' : 'Cara masuk / PIN' }}</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($daftar as $u)
                            <tr>
                                <td>{{ $u->name }}</td>
                                <td><span class="badge text-bg-{{ ['admin' => 'danger', 'pelayanan' => 'primary', 'kasi' => 'success'][$u->role] ?? 'secondary' }}">{{ $u->labelPeran() }}</span></td>
                                <td class="small">
                                    @if ($modeGoogle)
                                        @php($belum = str_ends_with($u->email, '@sekumpul.local') || str_ends_with($u->email, '@sekumpul.test'))
                                        <div class="mb-1">
                                            @if ($belum)
                                                <span class="badge text-bg-danger">belum diisi (tidak bisa masuk dengan Google)</span>
                                            @else
                                                <span class="badge text-bg-success"><i class="bi bi-google"></i> {{ $u->email }}</span>
                                            @endif
                                        </div>
                                        <form method="post" action="{{ route('admin.akun.email', $u) }}" class="d-flex gap-1">
                                            @csrf @method('PUT')
                                            <input type="email" name="email" class="form-control form-control-sm" style="max-width: 230px" placeholder="nama@gmail.com" value="{{ $belum ? '' : $u->email }}" required>
                                            <button class="btn btn-sm btn-outline-primary">Simpan</button>
                                        </form>
                                        @if ($u->isAdmin())
                                            <div class="text-muted mt-1" style="font-size: .75rem">Admin tetap bisa masuk dengan password lewat "Masuk Admin dengan password", memakai email yang sama.</div>
                                        @endif
                                    @elseif ($u->isAdmin())
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
                    <div class="form-text">{{ $modeGoogle ? 'Kasi & Pelayanan masuk dengan akun Google sesuai email di bawah.' : 'Kasi & Pelayanan masuk dengan menekan tombol bernama ini.' }} Untuk Kasi, buat satu akun per komputer/orang.</div>
                </div>
                <div id="isianEmail" class="mb-2">
                    <label class="form-label" id="labelEmail">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" class="form-control" placeholder="{{ $modeGoogle ? 'nama@gmail.com' : '' }}">
                </div>
                <div id="isianPassword" class="mb-3"><label class="form-label">Password (min. 8 karakter)</label><input type="password" name="password" class="form-control"></div>
                @unless ($modeGoogle)
                    <div id="isianPin" class="mb-3">
                        <label class="form-label">PIN (4–6 angka){{ config('app.wajib_pin') ? '' : ', opsional' }}</label>
                        <input type="password" name="pin" class="form-control" inputmode="numeric" pattern="\d{4,6}" maxlength="6" autocomplete="new-password">
                    </div>
                @endunless
                <button class="btn btn-primary w-100">Buat Akun</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('js')
<script>
    // Admin: email + password. Pelayanan/Kasi: email Google (mode Google) atau PIN (mode tombol).
    // Isian yang disembunyikan juga dinonaktifkan supaya tidak ikut terkirim.
    const modeGoogle = @json($modeGoogle);
    const peran = document.getElementById('pilihPeran');
    const tampil = (id, ya) => {
        const el = document.getElementById(id);
        if (!el) return;
        el.classList.toggle('d-none', !ya);
        el.querySelectorAll('input').forEach((i) => i.disabled = !ya);
    };
    const atur = () => {
        const admin = peran.value === 'admin';
        tampil('isianEmail', admin || modeGoogle);
        tampil('isianPassword', admin);
        tampil('isianPin', !admin);
        document.getElementById('labelEmail').textContent = admin ? 'Email' : 'Email Google';
    };
    peran.addEventListener('change', atur);
    atur();
</script>
@endpush
