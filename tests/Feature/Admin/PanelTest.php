<?php

use App\Models\BadanPublik;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->admin = User::create([
        'name' => 'Test Admin',
        'email' => 'admin@test.com',
        'password' => Hash::make('password123'),
        'role' => 'admin',
    ]);

    Auth::guard('admin')->login($this->admin);
});

// ── Admin dashboard ───────────────────────────────────────────────────────

test('admin can access dashboard', function () {
    $response = $this->get('/admin/dashboard');

    $response->assertOk();
    $response->assertSee('Dashboard');
});

test('admin dashboard shows statistics', function () {
    $response = $this->get('/admin/dashboard');

    $response->assertSee('PPID Pelaksana Terdaftar');
    $response->assertSee('Dinas Menunggu Verifikasi');
    $response->assertSee('Dinas Terverifikasi');
    $response->assertSee('Rekap Hasil Penilaian');
});

test('admin dashboard shows verification list', function () {
    $response = $this->get('/admin/dashboard');

    $response->assertSee('List Verifikasi Nilai Dinas');
});

// ── Admin kuesioner pages ─────────────────────────────────────────────────

test('admin can access kuesioner page', function () {
    $response = $this->get('/admin/kuesioner');

    $response->assertOk();
});

test('admin can access kuesioner jadwal page', function () {
    $response = $this->get('/admin/kuesioner/jadwal');

    $response->assertOk();
});

test('admin can access create kategori page', function () {
    $response = $this->get('/admin/kuesioner/kategori/create');

    $response->assertOk();
});

// ── Admin penilaian pages ─────────────────────────────────────────────────

test('admin can access penilaian page', function () {
    $response = $this->get('/admin/penilaian');

    $response->assertOk();
});

// ── Admin badan publik pages ──────────────────────────────────────────────

test('admin can access badan publik list', function () {
    $response = $this->get('/admin/badan-publik');

    $response->assertOk();
});

test('admin can see list of registered badan publik', function () {
    $user = User::factory()->create(['role' => 'dinas']);
    BadanPublik::create([
        'user_id' => $user->id,
        'nama_badan_publik' => 'Dinas Komunikasi',
        'website' => 'https://diskominfo.example.go.id',
        'telepon_badan_publik' => '0286-123456',
        'email_badan_publik' => 'diskominfo@example.go.id',
        'alamat' => 'Jl. Test No. 1',
    ]);

    $response = $this->get('/admin/badan-publik');

    $response->assertOk();
    $response->assertSee('Dinas Komunikasi');
});

test('admin can view detail of a badan publik', function () {
    $user = User::factory()->create(['role' => 'dinas']);
    $bp = BadanPublik::create([
        'user_id' => $user->id,
        'nama_badan_publik' => 'Dinas Detail Test',
        'website' => 'https://detail.example.com',
        'telepon_badan_publik' => '0286-654321',
        'email_badan_publik' => 'detail@example.com',
        'alamat' => 'Jl. Detail No. 1',
    ]);

    $response = $this->get("/admin/badan-publik/{$user->id}/detail");

    $response->assertOk();
    $response->assertSee('Dinas Detail Test');
});

// ── Admin other pages ─────────────────────────────────────────────────────

test('admin can access laporan page', function () {
    $response = $this->get('/admin/laporan');

    $response->assertOk();
});

test('admin can access pesan page', function () {
    $response = $this->get('/admin/pesan');

    $response->assertOk();
});

test('admin can access pengaturan page', function () {
    $response = $this->get('/admin/pengaturan');

    $response->assertOk();
});

test('admin can access klasifikasi penilaian page', function () {
    $response = $this->get('/admin/klasifikasi-penilaian');

    $response->assertOk();
});

// ── Unauthenticated access to admin pages ─────────────────────────────────

test('unauthenticated user cannot access admin dashboard', function () {
    Auth::guard('admin')->logout();

    $response = $this->get('/admin/dashboard');

    $response->assertRedirect(route('admin.login', absolute: false));
});

test('unauthenticated user cannot access admin kuesioner', function () {
    Auth::guard('admin')->logout();

    $response = $this->get('/admin/kuesioner');

    $response->assertRedirect(route('admin.login', absolute: false));
});

test('unauthenticated user cannot access admin penilaian', function () {
    Auth::guard('admin')->logout();

    $response = $this->get('/admin/penilaian');

    $response->assertRedirect(route('admin.login', absolute: false));
});

test('unauthenticated user cannot access admin badan publik', function () {
    Auth::guard('admin')->logout();

    $response = $this->get('/admin/badan-publik');

    $response->assertRedirect(route('admin.login', absolute: false));
});

test('unauthenticated user cannot access admin laporan', function () {
    Auth::guard('admin')->logout();

    $response = $this->get('/admin/laporan');

    $response->assertRedirect(route('admin.login', absolute: false));
});

test('unauthenticated user cannot access admin pesan', function () {
    Auth::guard('admin')->logout();

    $response = $this->get('/admin/pesan');

    $response->assertRedirect(route('admin.login', absolute: false));
});

test('unauthenticated user cannot access admin pengaturan', function () {
    Auth::guard('admin')->logout();

    $response = $this->get('/admin/pengaturan');

    $response->assertRedirect(route('admin.login', absolute: false));
});
