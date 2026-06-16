<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component
{
    /**
     * Log the current user out of the application.
     */
    public function logout(): void
    {
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();

        $this->redirect('/', navigate: false);
    }

    /**
     * Redirect the user back to the dashboard.
     */
    public function cancel(): void
    {
        $this->redirect(route('dashboard'), navigate: true);
    }
}; ?>


    <div class="min-h-screen bg-gray-100">
        <!-- Main Content -->
        <main class="flex items-center justify-center" style="height: calc(100vh - 68px);">
            <div class="text-center">
                <img src="/images/logout.png" alt="Logout Illustration" class="mx-auto mb-8 w-64 h-auto">

                <h1 class="text-4xl font-bold text-blue-600">Kamu Yakin Mau Keluar?</h1>

                <div class="mt-8 flex justify-center space-x-4">
                    <button wire:click="cancel" class="px-10 py-3 bg-gray-200 text-gray-700 font-semibold rounded-lg hover:bg-gray-300">
                        Batal
                    </button>
                    <button wire:click="logout" class="px-10 py-3 bg-red-500 text-white font-semibold rounded-lg hover:bg-red-600">
                        Keluar
                    </button>
                </div>
            </div>
        </main>
    </div>
