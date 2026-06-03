<?php

use App\Models\BadanPublik;
use App\Models\HasilPenilaian;
use App\Models\Jadwal;
use App\Models\KlasifikasiPenilaian;
use App\Models\User;

// ── Dashboard access ──────────────────────────────────────────────────────

test('authenticated user can access dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = $this->get('/dashboard');

    $response->assertOk();
});

test('guest cannot access dashboard', function () {
    $response = $this->get('/dashboard');

    $response->assertRedirect('/login');
});

// ── Dashboard content ─────────────────────────────────────────────────────

test('dashboard shows welcome heading', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = $this->get('/dashboard');

    $response->assertSee('Ringkasan Hasil Penilaian');
});

test('dashboard shows user badan publik data when available', function () {
    $user = User::factory()->create();
    BadanPublik::create([
        'user_id' => $user->id,
        'nama_badan_publik' => 'Dinas Test',
        'website' => 'https://test.example.com',
        'telepon_badan_publik' => '0286-123456',
        'email_badan_publik' => 'test@example.com',
        'alamat' => 'Test Address',
    ]);

    $this->actingAs($user);

    $response = $this->get('/dashboard');

    $response->assertSee('Dinas Test');
    $response->assertSee('Edit Biodata');
});

test('dashboard shows edit biodata link', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = $this->get('/dashboard');

    $response->assertSee(route('biodata.edit'));
});

// ── Dashboard with scoring data ───────────────────────────────────────────

test('dashboard shows hasil penilaian when available', function () {
    $user = User::factory()->create();
    $jadwal = Jadwal::create([
        'nama' => 'Monev TA 2026',
        'tahun' => 2026,
        'tanggal_mulai' => now()->subDay(),
        'tanggal_selesai' => now()->addYear(),
        'is_active' => true,
    ]);
    $klasifikasi = KlasifikasiPenilaian::create([
        'nama' => 'Baik',
        'min_nilai' => 70.00,
        'max_nilai' => 100.00,
        'urutan' => 1,
        'is_active' => true,
    ]);
    HasilPenilaian::create([
        'user_id' => $user->id,
        'jadwal_id' => $jadwal->id,
        'nilai_akhir' => 85.50,
        'klasifikasi_penilaian_id' => $klasifikasi->id,
        'status_verifikasi' => 'Terverifikasi',
    ]);

    $this->actingAs($user);

    $response = $this->get('/dashboard');

    $response->assertSee('85.50');
    $response->assertSee('Baik');
    $response->assertSee('Terverifikasi');
});

test('dashboard shows default values when no penilaian exists', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = $this->get('/dashboard');

    $response->assertSee('Belum terklasifikasi');
});

// ── Dashboard layout ──────────────────────────────────────────────────────

test('dashboard loads with correct layout structure', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = $this->get('/dashboard');

    $response->assertSee('Data Badan Publik');
    $response->assertSee('Nilai Akhir');
    $response->assertSee('Klasifikasi');
    $response->assertSee('Status Verifikasi');
});
