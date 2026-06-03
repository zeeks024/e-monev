<?php

use App\Models\User;
use Livewire\Volt\Volt;

// ── Registration page rendering ───────────────────────────────────────────

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response
        ->assertOk()
        ->assertSeeVolt('pages.auth.register');
});

test('guest can access registration page', function () {
    $response = $this->get('/register');

    $response->assertOk();
});

// ── Successful registration ───────────────────────────────────────────────

test('new users can register with valid data', function () {
    $component = Volt::test('pages.auth.register')
        ->set('nama_badan_publik', 'Dinas Komunikasi dan Informatika')
        ->set('website', 'https://diskominfo.example.go.id')
        ->set('telepon_badan_publik', '0286-123456')
        ->set('email_badan_publik', 'diskominfo@example.go.id')
        ->set('alamat', 'Jl. Contoh No. 1, Banjarnegara')
        ->set('nama_responden', 'John Doe')
        ->set('telepon_responden', '081234567890')
        ->set('jabatan', 'Staff Informasi')
        ->set('email_responden', 'john@example.com')
        ->set('password', 'password123')
        ->set('password_confirmation', 'password123');

    $component->call('register');

    $this->assertDatabaseHas('users', [
        'email' => 'john@example.com',
        'name' => 'John Doe',
        'role' => 'dinas',
    ]);

    $this->assertDatabaseHas('badan_publiks', [
        'nama_badan_publik' => 'Dinas Komunikasi dan Informatika',
        'website' => 'https://diskominfo.example.go.id',
    ]);

    $this->assertAuthenticated();
});

test('registration creates linked badan publik record', function () {
    Volt::test('pages.auth.register')
        ->set('nama_badan_publik', 'Test BP')
        ->set('website', 'https://test.example.com')
        ->set('telepon_badan_publik', '0286-000000')
        ->set('email_badan_publik', 'bp@test.com')
        ->set('alamat', 'Test Address')
        ->set('nama_responden', 'Respondent')
        ->set('telepon_responden', '081111111111')
        ->set('jabatan', 'Manager')
        ->set('email_responden', 'respondent@test.com')
        ->set('password', 'password123')
        ->set('password_confirmation', 'password123')
        ->call('register');

    $user = User::where('email', 'respondent@test.com')->first();

    expect($user->badanPublik)->not->toBeNull();
    expect($user->badanPublik->nama_badan_publik)->toBe('Test BP');
    expect($user->badanPublik->jabatan)->toBe('Manager');
});

// ── Registration validation failures ──────────────────────────────────────

test('cannot register with invalid email format', function () {
    $component = Volt::test('pages.auth.register')
        ->set('nama_badan_publik', 'Test BP')
        ->set('website', 'https://test.com')
        ->set('telepon_badan_publik', '0286-000000')
        ->set('email_badan_publik', 'bp@test.com')
        ->set('alamat', 'Test Address')
        ->set('nama_responden', 'Test')
        ->set('telepon_responden', '081111111111')
        ->set('jabatan', 'Staff')
        ->set('email_responden', 'not-an-email')
        ->set('password', 'password123')
        ->set('password_confirmation', 'password123');

    $component->call('register');

    $component->assertHasErrors(['email_responden']);
});

test('cannot register with duplicate email', function () {
    User::factory()->create(['email' => 'duplicate@example.com']);

    $component = Volt::test('pages.auth.register')
        ->set('nama_badan_publik', 'Test BP')
        ->set('website', 'https://test.com')
        ->set('telepon_badan_publik', '0286-000000')
        ->set('email_badan_publik', 'bp@test.com')
        ->set('alamat', 'Test Address')
        ->set('nama_responden', 'Test')
        ->set('telepon_responden', '081111111111')
        ->set('jabatan', 'Staff')
        ->set('email_responden', 'duplicate@example.com')
        ->set('password', 'password123')
        ->set('password_confirmation', 'password123');

    $component->call('register');

    $component->assertHasErrors(['email_responden']);
});

test('cannot register with mismatched passwords', function () {
    $component = Volt::test('pages.auth.register')
        ->set('nama_badan_publik', 'Test BP')
        ->set('website', 'https://test.com')
        ->set('telepon_badan_publik', '0286-000000')
        ->set('email_badan_publik', 'bp@test.com')
        ->set('alamat', 'Test Address')
        ->set('nama_responden', 'Test')
        ->set('telepon_responden', '081111111111')
        ->set('jabatan', 'Staff')
        ->set('email_responden', 'unique@test.com')
        ->set('password', 'password123')
        ->set('password_confirmation', 'different123');

    $component->call('register');

    $component->assertHasErrors(['password']);
});

test('cannot register with password shorter than 8 characters', function () {
    $component = Volt::test('pages.auth.register')
        ->set('nama_badan_publik', 'Test BP')
        ->set('website', 'https://test.com')
        ->set('telepon_badan_publik', '0286-000000')
        ->set('email_badan_publik', 'bp@test.com')
        ->set('alamat', 'Test Address')
        ->set('nama_responden', 'Test')
        ->set('telepon_responden', '081111111111')
        ->set('jabatan', 'Staff')
        ->set('email_responden', 'short@test.com')
        ->set('password', 'short')
        ->set('password_confirmation', 'short');

    $component->call('register');

    $component->assertHasErrors(['password']);
});

test('cannot register with missing required fields', function () {
    $component = Volt::test('pages.auth.register')
        ->set('nama_badan_publik', '')
        ->set('website', '')
        ->set('telepon_badan_publik', '')
        ->set('email_badan_publik', '')
        ->set('alamat', '')
        ->set('nama_responden', '')
        ->set('telepon_responden', '')
        ->set('jabatan', '')
        ->set('email_responden', '')
        ->set('password', '')
        ->set('password_confirmation', '');

    $component->call('register');

    $component->assertHasErrors([
        'nama_badan_publik',
        'website',
        'telepon_badan_publik',
        'email_badan_publik',
        'alamat',
        'nama_responden',
        'telepon_responden',
        'jabatan',
        'email_responden',
        'password',
    ]);
});

// ── Post-registration redirect ────────────────────────────────────────────

test('redirects to register success page after registration', function () {
    $component = Volt::test('pages.auth.register')
        ->set('nama_badan_publik', 'Test BP')
        ->set('website', 'https://test.com')
        ->set('telepon_badan_publik', '0286-000000')
        ->set('email_badan_publik', 'bp@test.com')
        ->set('alamat', 'Test Address')
        ->set('nama_responden', 'Test')
        ->set('telepon_responden', '081111111111')
        ->set('jabatan', 'Staff')
        ->set('email_responden', 'redirect@test.com')
        ->set('password', 'password123')
        ->set('password_confirmation', 'password123');

    $component->call('register');

    $component->assertRedirect(route('register.success'));
});
