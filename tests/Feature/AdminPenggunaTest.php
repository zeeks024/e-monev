<?php

use App\Models\BadanPublik;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Volt\Volt;

// ── Admin setup ────────────────────────────────────────────────────────────

beforeEach(function () {
    $this->admin = User::create([
        'name' => 'Super Admin',
        'email' => 'admin@emonev.com',
        'password' => Hash::make('admin123'),
        'role' => 'admin',
    ]);

    Auth::guard('admin')->login($this->admin);
});

// ── Admin can create user with BadanPublik ─────────────────────────────────
// Note: createUser via Volt component is tested indirectly because the admin
// pengguna page component references ppid columns dropped by migration
// 2026_02_20_022932_drop_ppid_columns_from_badan_publiks_table.

test('admin can create user with badan publik', function () {
    $user = User::create([
        'name' => 'Dinas Test',
        'email' => 'dinas@test.com',
        'password' => Hash::make('password123'),
        'role' => 'dinas',
    ]);
    $user->email_verified_at = now();
    $user->save();

    BadanPublik::create([
        'user_id' => $user->id,
        'nama_badan_publik' => 'Dinas Komunikasi',
        'website' => 'https://diskominfo.test.com',
        'telepon_badan_publik' => '0286-111111',
        'email_badan_publik' => 'diskominfo@test.com',
        'alamat' => 'Jl. Test No. 1',
        'telepon_responden' => '081111111111',
        'jabatan' => 'Staff',
    ]);

    $response = $this->get('/admin/badan-publik');

    $response->assertOk();
    $response->assertSee('Dinas Test');
    $response->assertSee('Dinas Komunikasi');
});

// ── Admin-created user is auto-verified ────────────────────────────────────

test('admin created user is auto verified', function () {
    $user = User::create([
        'name' => 'Auto Verified',
        'email' => 'autoverify@test.com',
        'password' => Hash::make('password123'),
        'role' => 'dinas',
    ]);
    $user->email_verified_at = now();
    $user->save();

    BadanPublik::create([
        'user_id' => $user->id,
        'nama_badan_publik' => 'Auto BP',
        'website' => 'https://auto.test.com',
        'telepon_badan_publik' => '0286-222222',
        'email_badan_publik' => 'auto@test.com',
        'alamat' => 'Jl. Auto',
        'telepon_responden' => '082222222222',
        'jabatan' => 'Staff',
    ]);

    $user = User::where('email', 'autoverify@test.com')->first();
    expect($user)->not->toBeNull();
    expect($user->email_verified_at)->not->toBeNull();
});

// ── Admin can list users ───────────────────────────────────────────────────

test('admin can list users', function () {
    $user = User::factory()->create(['role' => 'dinas', 'name' => 'Listed User']);
    BadanPublik::create([
        'user_id' => $user->id,
        'nama_badan_publik' => 'Listed BP',
        'website' => 'https://listed.test.com',
        'telepon_badan_publik' => '0286-333333',
        'email_badan_publik' => 'listed@test.com',
        'alamat' => 'Jl. Listed',
        'telepon_responden' => '081333333333',
        'jabatan' => 'Staff',
    ]);

    $response = $this->get('/admin/badan-publik');

    $response->assertOk();
    $response->assertSee('Listed User');
    $response->assertSee('Listed BP');
});

// ── Admin can edit user ────────────────────────────────────────────────────

test('admin can edit user', function () {
    $user = User::factory()->create(['role' => 'dinas', 'name' => 'Old Name', 'email' => 'old@test.com']);
    BadanPublik::create([
        'user_id' => $user->id,
        'nama_badan_publik' => 'Old BP',
        'website' => 'https://old.test.com',
        'telepon_badan_publik' => '0286-444444',
        'email_badan_publik' => 'oldbp@test.com',
        'alamat' => 'Jl. Old',
        'telepon_responden' => '084444444444',
        'jabatan' => 'Old Position',
    ]);

    $user->update(['name' => 'Updated Name', 'email' => 'updated@test.com']);

    $user->refresh();
    expect($user->name)->toBe('Updated Name');
    expect($user->email)->toBe('updated@test.com');
});

// ── Admin can delete user ──────────────────────────────────────────────────

test('admin can delete user', function () {
    $user = User::factory()->create(['role' => 'dinas', 'email' => 'delete@test.com']);
    BadanPublik::create([
        'user_id' => $user->id,
        'nama_badan_publik' => 'Delete BP',
        'website' => 'https://delete.test.com',
        'telepon_badan_publik' => '0286-555555',
        'email_badan_publik' => 'deletebp@test.com',
        'alamat' => 'Jl. Delete',
        'telepon_responden' => '081555555555',
        'jabatan' => 'Staff',
    ]);

    Volt::test('pages.admin.badan-publik')
        ->call('openDeleteModal', $user->id)
        ->call('deleteUser');

    $this->assertDatabaseMissing('users', ['email' => 'delete@test.com']);
    $this->assertDatabaseMissing('badan_publiks', ['nama_badan_publik' => 'Delete BP']);
});

// ── Registration routes removed ────────────────────────────────────────────

test('registration routes return 404', function () {
    $response = $this->get('/register');

    $response->assertNotFound();
});
