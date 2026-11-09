<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as UserGoogle;
use Mockery;
use Tests\TestCase;

/** Login Google versi online: email Google dicocokkan dengan daftar petugas yang diisi Admin. */
class MasukGoogleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'app.mode_masuk' => 'google',
            'services.google.client_id' => 'id-uji',
            'services.google.client_secret' => 'rahasia-uji',
        ]);
    }

    /** Pura-pura Google mengembalikan akun dengan email ini. */
    private function googleMengembalikan(string $email, bool $terverifikasi = true): void
    {
        $akun = (new UserGoogle)->setRaw(['email_verified' => $terverifikasi])->map(['email' => $email]);
        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->andReturn($akun);
        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
    }

    private function petugas(string $peran, string $email): User
    {
        return User::create(['name' => ucfirst($peran).' Uji', 'email' => $email, 'password' => 'rahasia-panjang', 'role' => $peran]);
    }

    public function test_layar_masuk_mode_google_tanpa_pilihan_pelayanan_kasi(): void
    {
        $this->get('/login')->assertOk()
            ->assertSee('Masuk dengan Google')
            ->assertDontSee('Perangkat ini dipakai sebagai');
    }

    public function test_kasi_terdaftar_masuk_ke_antrean(): void
    {
        $kasi = $this->petugas('kasi', 'kasi.satu@gmail.com');
        $this->googleMengembalikan('kasi.satu@gmail.com');

        $this->get('/auth/google/callback')->assertRedirect(route('admin.antrean'));
        $this->assertAuthenticatedAs($kasi);
    }

    public function test_pelayanan_terdaftar_masuk_ke_buat_permohonan(): void
    {
        $pelayanan = $this->petugas('pelayanan', 'meja.depan@gmail.com');
        $this->googleMengembalikan('meja.depan@gmail.com');

        $this->get('/auth/google/callback')->assertRedirect(route('admin.pengajuan.index'));
        $this->assertAuthenticatedAs($pelayanan);
    }

    public function test_email_tidak_dibedakan_huruf_besar_kecil(): void
    {
        $kasi = $this->petugas('kasi', 'kasi.dua@gmail.com');
        $this->googleMengembalikan('Kasi.Dua@Gmail.com');

        $this->get('/auth/google/callback');
        $this->assertAuthenticatedAs($kasi);
    }

    public function test_email_belum_terdaftar_ditolak(): void
    {
        $this->petugas('kasi', 'kasi.satu@gmail.com');
        $this->googleMengembalikan('orang.lain@gmail.com');

        $this->get('/auth/google/callback')
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('google');
        $this->assertGuest();
    }

    public function test_akun_tamu_tidak_bisa_masuk_lewat_google(): void
    {
        $tamu = User::where('role', 'tamu')->firstOrFail();
        $this->googleMengembalikan($tamu->email);

        $this->get('/auth/google/callback')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_email_google_belum_terverifikasi_ditolak(): void
    {
        $this->petugas('kasi', 'kasi.satu@gmail.com');
        $this->googleMengembalikan('kasi.satu@gmail.com', terverifikasi: false);

        $this->get('/auth/google/callback')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_login_google_belum_diatur_kembali_ke_login(): void
    {
        config(['services.google.client_id' => null]);

        $this->get('/auth/google')->assertRedirect(route('login'))->assertSessionHasErrors('google');
    }
}
