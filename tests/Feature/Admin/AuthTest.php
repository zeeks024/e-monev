<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

// ── Admin login page ──────────────────────────────────────────────────────

test('admin can access login page', function () {
    $response = $this->get('/admin/login');

    $response->assertOk();
    $response->assertSee('E-Monev KIP');
    $response->assertSee('Masuk');
});

test('admin login form has correct action', function () {
    $response = $this->get('/admin/login');

    $response->assertSee(route('admin.login.store'));
});

// ── Admin authentication ──────────────────────────────────────────────────

test('admin can log in with valid credentials', function () {
    $user = User::create([
        'name' => 'Super Admin',
        'email' => 'admin@emonev.com',
        'password' => Hash::make('admin12345678'),
        'role' => 'admin',
    ]);

    $response = $this->post('/admin/login', [
        'email' => 'admin@emonev.com',
        'password' => 'admin12345678',
    ]);

    $response->assertRedirect(route('admin.dashboard', absolute: false));
    $this->assertAuthenticated('admin');
});

test('admin is redirected to dashboard after login', function () {
    User::create([
        'name' => 'Admin',
        'email' => 'admin2@emonev.com',
        'password' => Hash::make('password123'),
        'role' => 'admin',
    ]);

    $response = $this->post('/admin/login', [
        'email' => 'admin2@emonev.com',
        'password' => 'password123',
    ]);

    $response->assertRedirect(route('admin.dashboard', absolute: false));
});

test('admin cannot login with wrong password', function () {
    User::create([
        'name' => 'Admin',
        'email' => 'wrongpass@emonev.com',
        'password' => Hash::make('correct-password'),
        'role' => 'admin',
    ]);

    $response = $this->post('/admin/login', [
        'email' => 'wrongpass@emonev.com',
        'password' => 'wrong-password',
    ]);

    $response->assertSessionHasErrors(['email']);
    $this->assertGuest('admin');
});

test('unregistered email cannot login as admin', function () {
    $response = $this->post('/admin/login', [
        'email' => 'nonexistent@emonev.com',
        'password' => 'password123',
    ]);

    $response->assertSessionHasErrors(['email']);
    $this->assertGuest('admin');
});

test('admin login validates email format', function () {
    $response = $this->post('/admin/login', [
        'email' => 'not-an-email',
        'password' => 'password123',
    ]);

    $response->assertSessionHasErrors(['email']);
});

// ── Admin session ─────────────────────────────────────────────────────────

test('authenticated admin can access admin routes via guard', function () {
    $user = User::create([
        'name' => 'Admin',
        'email' => 'session@emonev.com',
        'password' => Hash::make('password123'),
        'role' => 'admin',
    ]);

    Auth::guard('admin')->login($user);

    $response = $this->get('/admin/dashboard');

    $response->assertOk();
});

test('admin can logout', function () {
    $user = User::create([
        'name' => 'Admin',
        'email' => 'logout@emonev.com',
        'password' => Hash::make('password123'),
        'role' => 'admin',
    ]);

    Auth::guard('admin')->login($user);

    $response = $this->post('/admin/logout');

    $response->assertRedirect(route('admin.login', absolute: false));
    $this->assertGuest('admin');
});

// ── Non-admin access ──────────────────────────────────────────────────────

test('regular user cannot access admin panel', function () {
    $user = User::factory()->create(['role' => 'dinas']);

    $this->actingAs($user);

    $response = $this->get('/admin/dashboard');

    // Admin middleware redirects to admin.login if not authenticated via admin guard
    $response->assertRedirect(route('admin.login', absolute: false));
});

test('guest cannot access admin panel', function () {
    $response = $this->get('/admin/dashboard');

    $response->assertRedirect(route('admin.login', absolute: false));
});

test('guest cannot access admin kuesioner page', function () {
    $response = $this->get('/admin/kuesioner');

    $response->assertRedirect(route('admin.login', absolute: false));
});

test('guest cannot access admin penilaian page', function () {
    $response = $this->get('/admin/penilaian');

    $response->assertRedirect(route('admin.login', absolute: false));
});

test('guest cannot access admin badan publik page', function () {
    $response = $this->get('/admin/badan-publik');

    $response->assertRedirect(route('admin.login', absolute: false));
});
