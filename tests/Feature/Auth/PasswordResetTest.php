<?php

namespace Tests\Feature\Auth;

use App\Mail\SendResetCode;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Volt\Volt;

test('reset password link screen can be rendered', function () {
    $response = $this->get('/forgot-password');

    $response
        ->assertSeeVolt('pages.auth.forgot-password')
        ->assertStatus(200);
});

test('reset password link can be requested', function () {
    Mail::fake();

    $user = User::factory()->create();

    $component = Volt::test('pages.auth.forgot-password')
        ->set('email', $user->email);

    $component->call('sendPasswordResetLink');

    $component->assertRedirect(route('password.verify-code', absolute: false));
    expect(session('email_for_verification'))->toBe($user->email);
    expect(DB::table('password_reset_tokens')->where('email', $user->email)->exists())->toBeTrue();

    Mail::assertSent(SendResetCode::class);
});

test('reset password link request is rate limited after too many attempts', function () {
    Mail::fake();

    $user = User::factory()->create(['email' => 'limited-reset@example.com']);
    $throttleKey = 'password-reset-mail|'.Str::transliterate('limited-reset@example.com|127.0.0.1');

    RateLimiter::clear($throttleKey);

    foreach (range(1, 3) as $attempt) {
        Volt::test('pages.auth.forgot-password')
            ->set('email', $user->email)
            ->call('sendPasswordResetLink')
            ->assertRedirect(route('password.verify-code', absolute: false));
    }

    Volt::test('pages.auth.forgot-password')
        ->set('email', $user->email)
        ->call('sendPasswordResetLink')
        ->assertHasErrors([
            'email' => trans('auth.throttle', ['seconds' => 300, 'minutes' => 5]),
        ]);

    Mail::assertSent(SendResetCode::class, 3);
});

test('verify code page can be rendered after requesting reset link', function () {
    session(['email_for_verification' => 'user@example.com']);

    $response = $this->get('/forgot-password/verify-code');

    $response
        ->assertSeeVolt('pages.auth.verify-code')
        ->assertStatus(200);
});

test('password can be reset with valid token', function () {
    $user = User::factory()->create(['email' => 'reset@example.com']);
    $token = app('auth.password.broker')->createToken($user);

    $component = Volt::test('pages.auth.reset-password', ['token' => $token])
        ->set('email', $user->email)
        ->set('password', 'password123')
        ->set('password_confirmation', 'password123');

    $component->call('resetPassword');

    $component
        ->assertRedirect('/login')
        ->assertHasNoErrors();
});

test('password reset code verification is rate limited after too many invalid attempts', function () {
    $user = User::factory()->create(['email' => 'verify-limit@example.com']);
    $throttleKey = 'password-reset-verify|'.Str::transliterate('verify-limit@example.com|127.0.0.1');

    RateLimiter::clear($throttleKey);

    foreach (range(1, 5) as $attempt) {
        session(['email_for_verification' => $user->email]);

        Volt::test('pages.auth.verify-code')
            ->set('code', 'ABC123')
            ->call('verifyCode')
            ->assertHasErrors(['code']);
    }

    session(['email_for_verification' => $user->email]);

    Volt::test('pages.auth.verify-code')
        ->set('code', 'ABC123')
        ->call('verifyCode')
        ->assertHasErrors([
            'code' => trans('auth.throttle', ['seconds' => 300, 'minutes' => 5]),
        ]);
});
