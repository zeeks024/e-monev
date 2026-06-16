<?php

use App\Models\User;
use App\Models\BadanPublik;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component
{
    // Data Badan Publik
    public string $nama_badan_publik = '';
    public string $website = '';
    public string $telepon_badan_publik = '';
    public string $email_badan_publik = '';
    public string $alamat = '';

    // Data Responden
    public string $nama_responden = '';
    public string $telepon_responden = '';
    public string $jabatan = '';

    /**
     * Mount the component and pre-fill the form with existing data.
     */
    public function mount(): void
    {
        $user = Auth::user();
        $badanPublik = $user->badanPublik;

        if ($badanPublik) {
            $this->nama_badan_publik = $badanPublik->nama_badan_publik;
            $this->website = $badanPublik->website;
            $this->telepon_badan_publik = $badanPublik->telepon_badan_publik;
            $this->email_badan_publik = $badanPublik->email_badan_publik;
            $this->alamat = $badanPublik->alamat;

            $this->nama_responden = $user->name;
            $this->telepon_responden = $badanPublik->telepon_responden;
            $this->jabatan = $badanPublik->jabatan;
        }
    }

    /**
     * Handle the update request.
     */
    public function updateBiodata(): void
    {
        $user = Auth::user();
        $badanPublik = $user->badanPublik;

        $validated = $this->validate([
            'nama_badan_publik' => ['required', 'string', 'max:255'],
            'website' => ['required', 'string', 'max:255'],
            'telepon_badan_publik' => ['required', 'string', 'max:20'],
            'email_badan_publik' => ['required', 'string', 'email', 'max:255'],
            'alamat' => ['required', 'string'],
            'nama_responden' => ['required', 'string', 'max:255'],
            'telepon_responden' => ['required', 'string', 'max:20'],
            'jabatan' => ['required', 'string', 'max:255'],
        ]);

        // Update user's name if it has changed
        if ($user->name !== $this->nama_responden) {
            $user->name = $this->nama_responden;
            $user->save();
        }

        // Update the BadanPublik data
        $badanPublik->update($validated);

        session()->flash('status', 'Biodata berhasil diperbarui!');

        $this->redirect(route('dashboard'), navigate: true);
    }
}; ?>


    <div class="min-h-screen bg-gray-100">
        <main class="py-12">
            <div class="max-w-screen-xl mx-auto px-6 md:px-20">
                <h1 class="text-3xl font-bold text-gray-900 mb-6">Ubah Biodata Peserta</h1>

                <div class="bg-white p-8 rounded-lg shadow-md">
                    <form wire:submit="updateBiodata" class="space-y-8">
                        <!-- Data Badan Publik -->
                        <fieldset class="space-y-6">
                            <legend class="text-lg font-semibold text-gray-900">Data Badan Publik</legend>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="nama_badan_publik" class="block text-sm font-medium text-gray-700">Nama Badan Publik</label>
                                    <input wire:model="nama_badan_publik" id="nama_badan_publik" type="text" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    <x-input-error :messages="$errors->get('nama_badan_publik')" class="mt-2" />
                                </div>
                                <div>
                                    <label for="website" class="block text-sm font-medium text-gray-700">Website</label>
                                    <input wire:model="website" id="website" type="text" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    <x-input-error :messages="$errors->get('website')" class="mt-2" />
                                </div>
                                <div>
                                    <label for="telepon_badan_publik" class="block text-sm font-medium text-gray-700">No. Telepon</label>
                                    <input wire:model="telepon_badan_publik" id="telepon_badan_publik" type="text" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    <x-input-error :messages="$errors->get('telepon_badan_publik')" class="mt-2" />
                                </div>
                                <div>
                                    <label for="email_badan_publik" class="block text-sm font-medium text-gray-700">Email</label>
                                    <input wire:model="email_badan_publik" id="email_badan_publik" type="email" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    <x-input-error :messages="$errors->get('email_badan_publik')" class="mt-2" />
                                </div>
                                <div class="md:col-span-2">
                                    <label for="alamat" class="block text-sm font-medium text-gray-700">Alamat</label>
                                    <textarea wire:model="alamat" id="alamat" rows="3" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"></textarea>
                                    <x-input-error :messages="$errors->get('alamat')" class="mt-2" />
                                </div>
                            </div>
                        </fieldset>

                        <!-- Data Responden -->
                        <fieldset class="space-y-6">
                            <legend class="text-lg font-semibold text-gray-900">Data Responden</legend>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="nama_responden" class="block text-sm font-medium text-gray-700">Nama Responden</label>
                                    <input wire:model="nama_responden" id="nama_responden" type="text" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    <x-input-error :messages="$errors->get('nama_responden')" class="mt-2" />
                                </div>
                                <div>
                                    <label for="telepon_responden" class="block text-sm font-medium text-gray-700">No. Telepon</label>
                                    <input wire:model="telepon_responden" id="telepon_responden" type="text" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    <x-input-error :messages="$errors->get('telepon_responden')" class="mt-2" />
                                </div>
                                <div>
                                    <label for="jabatan" class="block text-sm font-medium text-gray-700">Jabatan</label>
                                    <input wire:model="jabatan" id="jabatan" type="text" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    <x-input-error :messages="$errors->get('jabatan')" class="mt-2" />
                                </div>
                            </div>
                        </fieldset>

                        <div class="pt-5">
                            <div class="flex justify-end space-x-3">
                                <a href="{{ route('dashboard') }}" wire:navigate class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50">
                                    Batal
                                </a>
                                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-md focus:outline-none focus:shadow-outline">
                                    Simpan Perubahan
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
