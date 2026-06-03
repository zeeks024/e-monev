<?php

use App\Models\User;
use Livewire\Volt\Volt;

// ── Login page rendering ──────────────────────────────────────────────────

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response
        ->assertOk()
        ->assertSeeVolt('pages.auth.login');
});

test('guest can access login page', function () {
    $response = $this->get('/login');

    $response->assertOk();
});

// ── Successful login ──────────────────────────────────────────────────────

test('users can authenticate with valid credentials', function () {
    $user = User::factory()->create([
        'email' => 'user@example.com',
        'password' => bcrypt('password123'),
    ]);

    $component = Volt::test('pages.auth.login')
        ->set('form.email', 'user@example.com')
        ->set('form.password', 'password123');

    $component->call('login');

    $component
        ->assertHasNoErrors()
        ->assertRedirect(route('user.dashboard', absolute: false));

    $this->assertAuthenticated();
});

test('user is redirected to dashboard after login', function () {
    $user = User::factory()->create([
        'email' => 'redirect@example.com',
        'password' => bcrypt('password123'),
    ]);

    $component = Volt::test('pages.auth.login')
        ->set('form.email', 'redirect@example.com')
        ->set('form.password', 'password123');

    $component->call('login');

    $component->assertRedirect(route('user.dashboard', absolute: false));
});

// ── Failed login ──────────────────────────────────────────────────────────

test('users cannot authenticate with wrong password', function () {
    $user = User::factory()->create([
        'email' => 'wrongpass@example.com',
        'password' => bcrypt('correct-password'),
    ]);

    $component = Volt::test('pages.auth.login')
        ->set('form.email', 'wrongpass@example.com')
        ->set('form.password', 'wrong-password');

    $component->call('login');

    $component->assertHasErrors(['form.email']);

    $this->assertGuest();
});

test('unregistered user cannot login', function () {
    $component = Volt::test('pages.auth.login')
        ->set('form.email', 'nonexistent@example.com')
        ->set('form.password', 'password123');

    $component->call('login');

    $component->assertHasErrors(['form.email']);

    $this->assertGuest();
});

test('login fails with invalid email format', function () {
    $component = Volt::test('pages.auth.login')
        ->set('form.email', 'not-an-email')
        ->set('form.password', 'password123');

    $component->call('login');

    $component->assertHasErrors(['form.email']);
});

// ── Authenticated access ──────────────────────────────────────────────────

test('authenticated user can access dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = $this->get('/dashboard');

    $response->assertOk();
});

test('guest cannot access dashboard and is redirected to login', function () {
    $response = $this->get('/dashboard');

    $response->assertRedirect('/login');
});

test('authenticated user is redirected from login to dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = $this->get('/login');

    $response->assertRedirect(route('user.dashboard', absolute: false));
});
