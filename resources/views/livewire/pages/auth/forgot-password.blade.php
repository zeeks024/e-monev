<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Mail\SendResetCode; // Anda perlu membuat Mailable ini
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component
{
    public string $email = '';

    /**
     * Handle an incoming password reset link request.
     */
    public function sendPasswordResetLink(): void
    {
        $this->validate([
            'email' => ['required', 'email', 'exists:users,email'],
        ]);

        $this->ensureResetLinkIsNotRateLimited();
        RateLimiter::hit($this->resetLinkThrottleKey(), 300);

        // 1. Buat kode acak 6 digit.
        $code = Str::random(6);

        // 2. Hapus token lama & simpan kode baru di tabel 'password_reset_tokens'.
        DB::table('password_reset_tokens')->where('email', $this->email)->delete();
        DB::table('password_reset_tokens')->insert([
            'email' => $this->email,
            'token' => $code, // Simpan kode 6 digit di kolom token
            'created_at' => now(),
        ]);

        // 3. Kirim email ke pengguna yang berisi kode tersebut.
        // Catatan: Pastikan Anda sudah mengkonfigurasi email di file .env
        try {
            Mail::to($this->email)->send(new SendResetCode($code));
        } catch (\Exception $e) {
            // Jika email gagal dikirim, tampilkan error
            $this->addError('email', 'Gagal mengirim email verifikasi. Silakan coba lagi nanti.');
            return;
        }

        // Simpan email ke session agar halaman selanjutnya tahu email mana yang sedang diverifikasi.
        session(['email_for_verification' => $this->email]);

        // Arahkan pengguna ke halaman verifikasi kode.
        $this->redirect(route('password.verify-code'), navigate: true);
    }

    protected function ensureResetLinkIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->resetLinkThrottleKey(), 3)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->resetLinkThrottleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    protected function resetLinkThrottleKey(): string
    {
        return 'password-reset-mail|'.Str::transliterate(Str::lower($this->email).'|'.request()->ip());
    }
}; ?>

<div class="min-h-screen flex flex-col md:flex-row">
    <!-- Left Side: Form -->
    <div class="w-full md:w-1/2 flex flex-col justify-center items-center p-8 md:p-12 bg-white">
        <div class="w-full max-w-md">
            <!-- Logo -->
            <div class="flex items-center space-x-2 mb-12">
                <img src="/images/logobna.png" alt="Logo E-Monev" class="h-10 w-auto">
                <span class="text-xl font-bold text-gray-800">E-Monev KIP</span>
            </div>

            <!-- Back to Login Link -->
            <a href="{{ route('login') }}" wire:navigate class="inline-flex items-center text-sm font-medium text-gray-600 hover:text-gray-800 mb-6">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                Kembali ke Masuk
            </a>

            <!-- Header -->
            <h1 class="text-3xl font-bold text-gray-900">Lupa kata sandi Anda?</h1>
            <p class="mt-2 text-gray-600">Jangan khawatir, ini terjadi pada kita semua. Masukkan email Anda di bawah untuk memulihkan kata sandi Anda.</p>

            <!-- Session Status -->
            <div class="mt-6">
                <x-auth-session-status class="mb-4" :status="session('status')" />
            </div>

            <!-- Form -->
            <form wire:submit="sendPasswordResetLink" class="mt-8 space-y-6">
                <!-- Email Input -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                    <div class="mt-1">
                        <input wire:model="email" id="email" type="email" required autofocus
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                               placeholder="Masukkan email kamu">
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>
                </div>

                <!-- Submit Button -->
                <div>
                    <button type="submit"
                            class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Kirim
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Right Side: Image -->
    <div class="w-full md:w-1/2 hidden md:flex justify-center items-center p-12">
        <div class="w-full max-w-md">
            <img src="/images/forgot-password-illustration.png" alt="Forgot Password Illustration" class="w-full h-auto">
        </div>
    </div>
</div>
