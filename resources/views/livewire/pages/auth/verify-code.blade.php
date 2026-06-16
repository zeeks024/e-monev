<?php

use App\Models\User;
use App\Mail\SendResetCode; // Anda perlu membuat Mailable ini
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component
{
    public string $code = '';
    public string $email = '';

    /**
     * Mount the component and retrieve the email from the session.
     */
    public function mount()
    {
        $this->email = session('email_for_verification', '');

        if (!$this->email) {
            return $this->redirect(route('password.request'));
        }
    }

    /**
     * Verify the code.
     */
    public function verifyCode(): void
    {
        $this->validate([
            'code' => ['required', 'string', 'min:6', 'max:6'],
        ]);

        $this->ensureCodeVerificationIsNotRateLimited();

        // 1. Cari token di database.
        $tokenData = DB::table('password_reset_tokens')
            ->where('email', $this->email)
            ->where('token', $this->code)
            ->first();

        // 2. Cek jika token ada dan belum kedaluwarsa (contoh: 10 menit).
        if ($tokenData && now()->subMinutes(10)->lt($tokenData->created_at)) {
            // Jika valid, hapus token lama agar tidak bisa dipakai lagi.
            DB::table('password_reset_tokens')->where('email', $this->email)->delete();
            RateLimiter::clear($this->verificationThrottleKey());

            // Buat token reset password yang sebenarnya (yang panjang dan aman).
            $user = User::where('email', $this->email)->first();
            $longToken = Password::createToken($user);

            // Arahkan ke halaman reset password dengan token yang aman.
            $this->redirect(route('password.reset', ['token' => $longToken, 'email' => $this->email]));
        } else {
            // Jika tidak valid, tampilkan error.
            RateLimiter::hit($this->verificationThrottleKey(), 300);
            $this->addError('code', 'Kode verifikasi tidak valid atau sudah kedaluwarsa.');
        }
    }

    /**
     * Resend the verification code.
     */
     public function resendCode(): void
     {
        $this->ensureResendIsNotRateLimited();
        RateLimiter::hit($this->resetLinkThrottleKey(), 300);

        // 1. Buat kode acak 6 digit baru.
        $newCode = Str::random(6);

        // 2. Hapus token lama & simpan kode baru.
        DB::table('password_reset_tokens')->where('email', $this->email)->delete();
        DB::table('password_reset_tokens')->insert([
            'email' => $this->email,
            'token' => $newCode,
            'created_at' => now(),
        ]);

        // 3. Kirim email baru.
        try {
            Mail::to($this->email)->send(new SendResetCode($newCode));
            session()->flash('status', 'Kode baru telah dikirimkan ke email Anda.');
        } catch (\Exception $e) {
            $this->addError('code', 'Gagal mengirim ulang kode. Coba lagi nanti.');
        }
     }

    protected function ensureCodeVerificationIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->verificationThrottleKey(), 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->verificationThrottleKey());

        throw ValidationException::withMessages([
            'code' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    protected function ensureResendIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->resetLinkThrottleKey(), 3)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->resetLinkThrottleKey());

        throw ValidationException::withMessages([
            'code' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    protected function verificationThrottleKey(): string
    {
        return 'password-reset-verify|'.Str::transliterate(Str::lower($this->email).'|'.request()->ip());
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
            <h1 class="text-3xl font-bold text-gray-900">Verifikasi Kode</h1>
            <p class="mt-2 text-gray-600">Kode otentikasi telah dikirim ke email Anda.</p>

            <!-- Session Status for resend -->
             <div class="mt-6">
                <x-auth-session-status class="mb-4" :status="session('status')" />
            </div>

            <!-- Form -->
            <form wire:submit="verifyCode" class="mt-8 space-y-6">
                <!-- Code Input -->
                <div>
                    <label for="code" class="block text-sm font-medium text-gray-700">Masukan Kode</label>
                    <div class="mt-1 relative">
                        <input wire:model="code" id="code" type="text" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                               placeholder="Masukkan kode">
                        <!-- This is just a visual placeholder, not a functional show/hide button -->
                        <span class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400">
                           <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                        </span>
                    </div>
                    <x-input-error :messages="$errors->get('code')" class="mt-2" />
                </div>

                <!-- Resend Code -->
                <div class="text-sm">
                    <span>Tidak menerima kode?</span>
                    <button type="button" wire:click="resendCode" class="font-medium text-blue-600 hover:text-blue-500">Kirim ulang</button>
                </div>


                <!-- Submit Button -->
                <div>
                    <button type="submit"
                            class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Verifikasi
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Right Side: Image -->
    <div class="w-full md:w-1/2 hidden md:flex justify-center items-center p-12">
        <div class="w-full max-w-md">
            <img src="/images/login-illustration.png" alt="Verification Illustration" class="w-full h-auto">
        </div>
    </div>
</div>
