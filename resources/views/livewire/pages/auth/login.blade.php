<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.guest')] class extends Component
{
    public LoginForm $form;

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate();

        $this->form->authenticate();

        Session::regenerate();

        $this->redirectIntended(default: route('user.dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <div class="min-h-screen flex flex-col md:flex-row">
        <!-- Left Side: Login Form -->
        <div class="w-full md:w-1/2 flex flex-col justify-center items-center p-8 md:p-12 bg-white">
            <div class="w-full max-w-md">
                <!-- Logo -->
                <div class="flex items-center space-x-2 mb-12">
                    <img src="/images/logobna.png" alt="Logo E-Monev" class="h-10 w-auto">
                    <span class="text-xl font-bold text-gray-800">E-Monev KIP</span>
                </div>

                <!-- Header -->
                <h1 class="text-3xl font-bold text-gray-900">Masuk</h1>
                <p class="mt-2 text-gray-600">Masuk untuk mengakses website e-monev KIP</p>

                <!-- Session Status -->
                <x-auth-session-status class="my-4" :status="session('status')" />

                <!-- Login Form with Livewire directives -->
                <form wire:submit="login" class="mt-8 space-y-6">
                    <!-- Email Input -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                        <div class="mt-1">
                            <input wire:model="form.email" id="email" type="email" required autofocus autocomplete="username"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                   placeholder="Masukkan Email">
                            <x-input-error :messages="$errors->get('form.email')" class="mt-2" />
                        </div>
                    </div>

                    <!-- Password Input -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                        <div class="mt-1 relative">
                            <input wire:model="form.password" id="password" type="password" required autocomplete="current-password"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                   placeholder="Masukkan Password">
                            <!-- Show/Hide Password Icon -->
                            <button type="button" onclick="togglePasswordVisibility()" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400">
                                <svg id="eye-icon" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                <svg id="eye-off-icon" class="h-5 w-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7 1.274-4.057 5.064-7 9.542-7 .847 0 1.67.127 2.455.364m0 11.452A9.96 9.96 0 0112 17c-4.478 0-8.268-2.943-9.542-7a10.034 10.034 0 013.454-4.545m1.546-1.546A10.008 10.008 0 0112 5c4.478 0 8.268 2.943 9.542 7a9.97 9.97 0 01-.68 2.455m-1.455 1.455A10.05 10.05 0 0112 19c-1.654 0-3.21-.48-4.545-1.31M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            </button>
                        </div>
                        <x-input-error :messages="$errors->get('form.password')" class="mt-2" />
                    </div>

                    <!-- Remember Me & Forgot Password -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <input wire:model="form.remember" id="remember" type="checkbox"
                                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <label for="remember" class="ml-2 block text-sm text-gray-900">Ingatkan Saya</label>
                        </div>
                        @if (Route::has('password.request'))
                            <div class="text-sm">
                                <a href="{{ route('password.request') }}" wire:navigate class="font-medium text-blue-600 hover:text-blue-500">Lupa Password?</a>
                            </div>
                        @endif
                    </div>

                    <!-- Submit Button -->
                    <div>
                        <button type="submit"
                                class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Masuk
                        </button>
                    </div>
                </form>

            </div>
        </div>

        <!-- Right Side: Image -->
        <div class="w-full md:w-1/2 hidden md:flex justify-center items-center p-12 bg-gray-100">
            <div class="w-full max-w-md">
                <img src="/images/login-illustration.png" alt="Login Illustration" class="w-full h-auto">
            </div>
        </div>
    </div>

    {{-- DIUBAH: Memindahkan script agar selalu dimuat --}}
    <script>
        function initializePasswordToggle() {
            window.togglePasswordVisibility = function() {
                const passwordInput = document.getElementById('password');
                const eyeIcon = document.getElementById('eye-icon');
                const eyeOffIcon = document.getElementById('eye-off-icon');

                if (!passwordInput) return;

                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    eyeIcon.classList.add('hidden');
                    eyeOffIcon.classList.remove('hidden');
                } else {
                    passwordInput.type = 'password';
                    eyeIcon.classList.remove('hidden');
                    eyeOffIcon.classList.add('hidden');
                }
            }
        }

        document.addEventListener('livewire:navigated', () => {
            initializePasswordToggle();
        });

        // Jalankan juga saat halaman dimuat pertama kali
        initializePasswordToggle();
    </script>
</div>
