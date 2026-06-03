<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\User;
use App\Models\BadanPublik;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;

new #[Layout('components.layouts.admin')] class extends Component
{
    use WithPagination;

    // Search & filters
    public string $search = '';

    // Modal state
    public bool $showCreateModal = false;
    public bool $showEditModal = false;
    public bool $showDeleteModal = false;

    // User being edited/deleted
    public ?int $userId = null;

    // Form fields - User
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    // Form fields - BadanPublik
    public string $nama_badan_publik = '';
    public string $website = '';
    public string $telepon_badan_publik = '';
    public string $email_badan_publik = '';
    public string $alamat = '';
    public string $telepon_responden = '';
    public string $jabatan = '';
    public string $nama_ppid = '';
    public string $telepon_ppid = '';
    public string $email_ppid = '';

    public function with(): array
    {
        $query = User::query()
            ->where('role', 'dinas')
            ->with('badanPublik');

        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%')
                  ->orWhereHas('badanPublik', function ($bp) {
                      $bp->where('nama_badan_publik', 'like', '%' . $this->search . '%');
                  });
            });
        }

        return [
            'users' => $query->latest()->paginate(10),
        ];
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->showCreateModal = true;
    }

    public function openEditModal(int $id): void
    {
        $user = User::with('badanPublik')->findOrFail($id);
        $this->userId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->password = '';
        $this->password_confirmation = '';

        $bp = $user->badanPublik;
        $this->nama_badan_publik = $bp->nama_badan_publik ?? '';
        $this->website = $bp->website ?? '';
        $this->telepon_badan_publik = $bp->telepon_badan_publik ?? '';
        $this->email_badan_publik = $bp->email_badan_publik ?? '';
        $this->alamat = $bp->alamat ?? '';
        $this->telepon_responden = $bp->telepon_responden ?? '';
        $this->jabatan = $bp->jabatan ?? '';
        $this->nama_ppid = $bp->nama_ppid ?? '';
        $this->telepon_ppid = $bp->telepon_ppid ?? '';
        $this->email_ppid = $bp->email_ppid ?? '';

        $this->showEditModal = true;
    }

    public function openDeleteModal(int $id): void
    {
        $this->userId = $id;
        $this->showDeleteModal = true;
    }

    public function createUser(): void
    {
        $validated = $this->validateCreateForm();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'dinas',
            'email_verified_at' => now(),
        ]);

        BadanPublik::create([
            'user_id' => $user->id,
            'nama_badan_publik' => $validated['nama_badan_publik'],
            'website' => $validated['website'],
            'telepon_badan_publik' => $validated['telepon_badan_publik'],
            'email_badan_publik' => $validated['email_badan_publik'],
            'alamat' => $validated['alamat'],
            'telepon_responden' => $validated['telepon_responden'],
            'jabatan' => $validated['jabatan'],
            'nama_ppid' => $validated['nama_ppid'],
            'telepon_ppid' => $validated['telepon_ppid'],
            'email_ppid' => $validated['email_ppid'],
        ]);

        $this->showCreateModal = false;
        $this->resetForm();
        session()->flash('success', 'Pengguna berhasil ditambahkan.');
    }

    public function updateUser(): void
    {
        $validated = $this->validateEditForm();

        $user = User::findOrFail($this->userId);
        $user->name = $validated['name'];
        $user->email = $validated['email'];
        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }
        $user->save();

        $user->badanPublik()->update([
            'nama_badan_publik' => $validated['nama_badan_publik'],
            'website' => $validated['website'],
            'telepon_badan_publik' => $validated['telepon_badan_publik'],
            'email_badan_publik' => $validated['email_badan_publik'],
            'alamat' => $validated['alamat'],
            'telepon_responden' => $validated['telepon_responden'],
            'jabatan' => $validated['jabatan'],
            'nama_ppid' => $validated['nama_ppid'],
            'telepon_ppid' => $validated['telepon_ppid'],
            'email_ppid' => $validated['email_ppid'],
        ]);

        $this->showEditModal = false;
        $this->resetForm();
        session()->flash('success', 'Data pengguna berhasil diperbarui.');
    }

    public function deleteUser(): void
    {
        $user = User::findOrFail($this->userId);
        $user->badanPublik()->delete();
        $user->delete();

        $this->showDeleteModal = false;
        $this->resetForm();
        session()->flash('success', 'Pengguna berhasil dihapus.');
    }

    public function resetForm(): void
    {
        $this->reset([
            'userId', 'name', 'email', 'password', 'password_confirmation',
            'nama_badan_publik', 'website', 'telepon_badan_publik', 'email_badan_publik',
            'alamat', 'telepon_responden', 'jabatan',
            'nama_ppid', 'telepon_ppid', 'email_ppid',
        ]);
        $this->resetErrorBag();
    }

    protected function validateCreateForm(): array
    {
        return $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'nama_badan_publik' => 'required|string|max:255',
            'website' => 'nullable|url|max:255',
            'telepon_badan_publik' => 'required|string|max:20',
            'email_badan_publik' => 'required|email|max:255',
            'alamat' => 'required|string',
            'telepon_responden' => 'required|string|max:20',
            'jabatan' => 'required|string|max:255',
            'nama_ppid' => 'nullable|string|max:255',
            'telepon_ppid' => 'nullable|string|max:20',
            'email_ppid' => 'nullable|email|max:255',
        ]);
    }

    protected function validateEditForm(): array
    {
        return $this->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($this->userId)],
            'password' => 'nullable|string|min:8|confirmed',
            'nama_badan_publik' => 'required|string|max:255',
            'website' => 'nullable|url|max:255',
            'telepon_badan_publik' => 'required|string|max:20',
            'email_badan_publik' => 'required|email|max:255',
            'alamat' => 'required|string',
            'telepon_responden' => 'required|string|max:20',
            'jabatan' => 'required|string|max:255',
            'nama_ppid' => 'nullable|string|max:255',
            'telepon_ppid' => 'nullable|string|max:20',
            'email_ppid' => 'nullable|email|max:255',
        ]);
    }
}; ?>

<div>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h1 class="text-3xl font-bold text-gray-900">Pengguna</h1>
            <button wire:click="openCreateModal" class="px-4 py-2 bg-blue-600 text-white rounded-md text-sm font-medium hover:bg-blue-700 transition">
                + Tambah Pengguna
            </button>
        </div>
    </x-slot>

    <main class="p-8">
        @if(session('success'))
            <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white p-6 rounded-lg shadow-md">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-semibold text-gray-800">Daftar Pengguna Dinas</h2>
                <div class="relative">
                    <input wire:model.live.debounce.300ms="search" type="text" placeholder="Cari nama, email, atau badan publik..."
                           class="pl-10 pr-4 py-2 border border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500 w-80">
                    <svg class="absolute left-3 top-2.5 h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">No</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama Responden</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Badan Publik</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Telepon</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($users as $user)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $loop->iteration + $users->firstItem() - 1 }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $user->name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ $user->email }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ $user->badanPublik->nama_badan_publik ?? 'Belum diisi' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ $user->badanPublik->telepon_badan_publik ?? '-' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                    <button wire:click="openEditModal({{ $user->id }})" class="px-3 py-1 text-xs font-medium text-white bg-yellow-500 rounded-md hover:bg-yellow-600 transition">
                                        Edit
                                    </button>
                                    <button wire:click="openDeleteModal({{ $user->id }})" class="px-3 py-1 text-xs font-medium text-white bg-red-600 rounded-md hover:bg-red-700 transition">
                                        Hapus
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">
                                    Tidak ada data pengguna yang ditemukan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-6">
                {{ $users->links() }}
            </div>
        </div>
    </main>

    {{-- Create Modal --}}
    @if($showCreateModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" wire:key="create-modal">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="$toggle('showCreateModal')"></div>
            <div class="relative bg-white rounded-lg shadow-xl max-w-3xl w-full mx-auto z-10">
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-900">Tambah Pengguna Baru</h3>
                    <button wire:click="$toggle('showCreateModal')" class="text-gray-400 hover:text-gray-600">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                <form wire:submit="createUser">
                    <div class="px-6 py-4 space-y-6 max-h-[70vh] overflow-y-auto">
                        {{-- Akun Pengguna --}}
                        <fieldset>
                            <legend class="text-base font-semibold text-gray-900 mb-2">Informasi Akun</legend>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="c_name" class="block text-sm font-medium text-gray-700">Nama Responden <span class="text-red-500">*</span></label>
                                    <input wire:model="name" id="c_name" type="text" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                    @error('name') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label for="c_email" class="block text-sm font-medium text-gray-700">Email (Login) <span class="text-red-500">*</span></label>
                                    <input wire:model="email" id="c_email" type="email" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                    @error('email') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label for="c_password" class="block text-sm font-medium text-gray-700">Password <span class="text-red-500">*</span></label>
                                    <input wire:model="password" id="c_password" type="password" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                    @error('password') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label for="c_password_confirmation" class="block text-sm font-medium text-gray-700">Konfirmasi Password <span class="text-red-500">*</span></label>
                                    <input wire:model="password_confirmation" id="c_password_confirmation" type="password" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                    @error('password_confirmation') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </fieldset>

                        {{-- Informasi Badan Publik --}}
                        <fieldset>
                            <legend class="text-base font-semibold text-gray-900 mb-2">Informasi Badan Publik</legend>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="c_nama_badan_publik" class="block text-sm font-medium text-gray-700">Nama Badan Publik <span class="text-red-500">*</span></label>
                                    <input wire:model="nama_badan_publik" id="c_nama_badan_publik" type="text" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                    @error('nama_badan_publik') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label for="c_website" class="block text-sm font-medium text-gray-700">Website</label>
                                    <input wire:model="website" id="c_website" type="url" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                    @error('website') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label for="c_telepon_badan_publik" class="block text-sm font-medium text-gray-700">Telepon Badan Publik <span class="text-red-500">*</span></label>
                                    <input wire:model="telepon_badan_publik" id="c_telepon_badan_publik" type="text" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                    @error('telepon_badan_publik') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label for="c_email_badan_publik" class="block text-sm font-medium text-gray-700">Email Badan Publik <span class="text-red-500">*</span></label>
                                    <input wire:model="email_badan_publik" id="c_email_badan_publik" type="email" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                    @error('email_badan_publik') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                </div>
                                <div class="md:col-span-2">
                                    <label for="c_alamat" class="block text-sm font-medium text-gray-700">Alamat <span class="text-red-500">*</span></label>
                                    <input wire:model="alamat" id="c_alamat" type="text" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                    @error('alamat') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </fieldset>

                        {{-- Informasi Responden --}}
                        <fieldset>
                            <legend class="text-base font-semibold text-gray-900 mb-2">Informasi Responden</legend>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="c_telepon_responden" class="block text-sm font-medium text-gray-700">Telepon Responden <span class="text-red-500">*</span></label>
                                    <input wire:model="telepon_responden" id="c_telepon_responden" type="text" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                    @error('telepon_responden') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label for="c_jabatan" class="block text-sm font-medium text-gray-700">Jabatan <span class="text-red-500">*</span></label>
                                    <input wire:model="jabatan" id="c_jabatan" type="text" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                    @error('jabatan') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </fieldset>

                        {{-- Informasi PPID --}}
                        <fieldset>
                            <legend class="text-base font-semibold text-gray-900 mb-2">Informasi PPID</legend>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="c_nama_ppid" class="block text-sm font-medium text-gray-700">Nama PPID</label>
                                    <input wire:model="nama_ppid" id="c_nama_ppid" type="text" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                    @error('nama_ppid') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label for="c_telepon_ppid" class="block text-sm font-medium text-gray-700">Telepon PPID</label>
                                    <input wire:model="telepon_ppid" id="c_telepon_ppid" type="text" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                    @error('telepon_ppid') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label for="c_email_ppid" class="block text-sm font-medium text-gray-700">Email PPID</label>
                                    <input wire:model="email_ppid" id="c_email_ppid" type="email" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                    @error('email_ppid') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </fieldset>
                    </div>

                    <div class="px-6 py-4 border-t border-gray-200 flex justify-end space-x-3">
                        <button type="button" wire:click="$toggle('showCreateModal')" class="px-4 py-2 bg-white border border-gray-300 rounded-md text-sm font-medium hover:bg-gray-50">
                            Batal
                        </button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md text-sm font-medium hover:bg-blue-700">
                            Simpan Pengguna
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    {{-- Edit Modal --}}
    @if($showEditModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" wire:key="edit-modal">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="$toggle('showEditModal')"></div>
            <div class="relative bg-white rounded-lg shadow-xl max-w-3xl w-full mx-auto z-10">
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-900">Edit Pengguna</h3>
                    <button wire:click="$toggle('showEditModal')" class="text-gray-400 hover:text-gray-600">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                <form wire:submit="updateUser">
                    <div class="px-6 py-4 space-y-6 max-h-[70vh] overflow-y-auto">
                        {{-- Akun Pengguna --}}
                        <fieldset>
                            <legend class="text-base font-semibold text-gray-900 mb-2">Informasi Akun</legend>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="e_name" class="block text-sm font-medium text-gray-700">Nama Responden <span class="text-red-500">*</span></label>
                                    <input wire:model="name" id="e_name" type="text" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                    @error('name') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label for="e_email" class="block text-sm font-medium text-gray-700">Email (Login) <span class="text-red-500">*</span></label>
                                    <input wire:model="email" id="e_email" type="email" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                    @error('email') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label for="e_password" class="block text-sm font-medium text-gray-700">Password Baru (opsional)</label>
                                    <input wire:model="password" id="e_password" type="password" placeholder="Kosongkan jika tidak diubah" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                    @error('password') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label for="e_password_confirmation" class="block text-sm font-medium text-gray-700">Konfirmasi Password</label>
                                    <input wire:model="password_confirmation" id="e_password_confirmation" type="password" placeholder="Kosongkan jika tidak diubah" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                    @error('password_confirmation') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </fieldset>

                        {{-- Informasi Badan Publik --}}
                        <fieldset>
                            <legend class="text-base font-semibold text-gray-900 mb-2">Informasi Badan Publik</legend>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="e_nama_badan_publik" class="block text-sm font-medium text-gray-700">Nama Badan Publik <span class="text-red-500">*</span></label>
                                    <input wire:model="nama_badan_publik" id="e_nama_badan_publik" type="text" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                    @error('nama_badan_publik') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label for="e_website" class="block text-sm font-medium text-gray-700">Website</label>
                                    <input wire:model="website" id="e_website" type="url" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                    @error('website') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label for="e_telepon_badan_publik" class="block text-sm font-medium text-gray-700">Telepon Badan Publik <span class="text-red-500">*</span></label>
                                    <input wire:model="telepon_badan_publik" id="e_telepon_badan_publik" type="text" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                    @error('telepon_badan_publik') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label for="e_email_badan_publik" class="block text-sm font-medium text-gray-700">Email Badan Publik <span class="text-red-500">*</span></label>
                                    <input wire:model="email_badan_publik" id="e_email_badan_publik" type="email" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                    @error('email_badan_publik') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                </div>
                                <div class="md:col-span-2">
                                    <label for="e_alamat" class="block text-sm font-medium text-gray-700">Alamat <span class="text-red-500">*</span></label>
                                    <input wire:model="alamat" id="e_alamat" type="text" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                    @error('alamat') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </fieldset>

                        {{-- Informasi Responden --}}
                        <fieldset>
                            <legend class="text-base font-semibold text-gray-900 mb-2">Informasi Responden</legend>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="e_telepon_responden" class="block text-sm font-medium text-gray-700">Telepon Responden <span class="text-red-500">*</span></label>
                                    <input wire:model="telepon_responden" id="e_telepon_responden" type="text" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                    @error('telepon_responden') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label for="e_jabatan" class="block text-sm font-medium text-gray-700">Jabatan <span class="text-red-500">*</span></label>
                                    <input wire:model="jabatan" id="e_jabatan" type="text" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                    @error('jabatan') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </fieldset>

                        {{-- Informasi PPID --}}
                        <fieldset>
                            <legend class="text-base font-semibold text-gray-900 mb-2">Informasi PPID</legend>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="e_nama_ppid" class="block text-sm font-medium text-gray-700">Nama PPID</label>
                                    <input wire:model="nama_ppid" id="e_nama_ppid" type="text" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                    @error('nama_ppid') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label for="e_telepon_ppid" class="block text-sm font-medium text-gray-700">Telepon PPID</label>
                                    <input wire:model="telepon_ppid" id="e_telepon_ppid" type="text" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                    @error('telepon_ppid') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label for="e_email_ppid" class="block text-sm font-medium text-gray-700">Email PPID</label>
                                    <input wire:model="email_ppid" id="e_email_ppid" type="email" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                    @error('email_ppid') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </fieldset>
                    </div>

                    <div class="px-6 py-4 border-t border-gray-200 flex justify-end space-x-3">
                        <button type="button" wire:click="$toggle('showEditModal')" class="px-4 py-2 bg-white border border-gray-300 rounded-md text-sm font-medium hover:bg-gray-50">
                            Batal
                        </button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md text-sm font-medium hover:bg-blue-700">
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    {{-- Delete Confirmation Modal --}}
    @if($showDeleteModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" wire:key="delete-modal">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="$toggle('showDeleteModal')"></div>
            <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full mx-auto z-10 p-6">
                <div class="text-center">
                    <svg class="mx-auto h-12 w-12 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                    <h3 class="mt-4 text-lg font-semibold text-gray-900">Hapus Pengguna</h3>
                    <p class="mt-2 text-sm text-gray-500">Apakah Anda yakin ingin menghapus pengguna ini? Semua data terkait termasuk data Badan Publik akan dihapus secara permanen.</p>
                </div>
                <div class="mt-6 flex justify-center space-x-3">
                    <button wire:click="$toggle('showDeleteModal')" class="px-4 py-2 bg-white border border-gray-300 rounded-md text-sm font-medium hover:bg-gray-50">
                        Batal
                    </button>
                    <button wire:click="deleteUser" class="px-4 py-2 bg-red-600 text-white rounded-md text-sm font-medium hover:bg-red-700">
                        Ya, Hapus
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>