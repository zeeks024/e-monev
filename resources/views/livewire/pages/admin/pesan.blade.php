<?php

use Livewire\Volt\Component;
use App\Mail\AdminPesanMail;
use App\Models\HasilPenilaian;
use App\Models\Jadwal;
use App\Models\Pesan;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;

new #[Layout('components.layouts.admin')] class extends Component
{
    use WithPagination;

    // Form properties
    public $kanal = 'aplikasi';
    public $judul = '';
    public $isi = '';
    public $targetMode = 'semua';
    public array $targetUserIds = [];
    public $targetCondition = 'belum_mengisi';

    // CRUD properties
    public ?Pesan $selectedPesan = null;
    public bool $isEditMode = false;
    public bool $showDeleteModal = false;
    public $pesanIdToDelete = null;

    /**
     * Mount the component
     */
    public function mount(): void
    {
        // Pagination is handled by Livewire WithPagination trait
    }

    /**
     * Get all messages with pagination
     */
    public function getPesanListProperty()
    {
        return Pesan::withCount('users')
            ->orderBy('created_at', 'desc')
            ->paginate(10);
    }

    public function getDinasOptionsProperty()
    {
        return User::query()
            ->with('badanPublik')
            ->where('role', 'dinas')
            ->orderBy('name')
            ->get();
    }

    private function getJadwalAcuan(): ?Jadwal
    {
        return Jadwal::active()->first() ?? Jadwal::latest('tanggal_mulai')->first();
    }

    private function getTargetUsers()
    {
        $query = User::query()
            ->with('badanPublik')
            ->where('role', 'dinas');

        if ($this->targetMode === 'tertentu') {
            return $query->whereIn('id', $this->targetUserIds)->get();
        }

        if ($this->targetMode !== 'kondisi') {
            return $query->get();
        }

        $jadwal = $this->getJadwalAcuan();
        if (!$jadwal) {
            return collect();
        }

        return match ($this->targetCondition) {
            'belum_mengisi' => $query->whereDoesntHave('submissions', function ($submissionQuery) use ($jadwal) {
                $submissionQuery->where('jadwal_id', $jadwal->id);
            })->get(),
            'sudah_mengisi' => $query->whereHas('submissions', function ($submissionQuery) use ($jadwal) {
                $submissionQuery->where('jadwal_id', $jadwal->id);
            })->get(),
            'menunggu_verifikasi' => $query
                ->whereHas('submissions', function ($submissionQuery) use ($jadwal) {
                    $submissionQuery->where('jadwal_id', $jadwal->id);
                })
                ->whereDoesntHave('hasilPenilaians', function ($hasilQuery) use ($jadwal) {
                    $hasilQuery->where('jadwal_id', $jadwal->id)
                        ->where('status_verifikasi', 'Terverifikasi');
                })
                ->get(),
            'terverifikasi' => $query->whereHas('hasilPenilaians', function ($hasilQuery) use ($jadwal) {
                $hasilQuery->where('jadwal_id', $jadwal->id)
                    ->where('status_verifikasi', 'Terverifikasi');
            })->get(),
            default => $query->get(),
        };
    }

    /**
     * Create a new message
     */
    public function kirimPesan()
    {
        $this->validate([
            'kanal' => 'required|in:aplikasi,email,keduanya',
            'judul' => 'required|string|max:255',
            'isi' => 'required|string',
            'targetMode' => 'required|in:semua,tertentu,kondisi',
            'targetUserIds' => 'array',
            'targetUserIds.*' => 'integer|exists:users,id',
            'targetCondition' => 'required|in:belum_mengisi,sudah_mengisi,menunggu_verifikasi,terverifikasi',
        ]);

        if ($this->targetMode === 'tertentu' && empty($this->targetUserIds)) {
            $this->addError('targetUserIds', 'Pilih minimal satu akun dinas.');
            return;
        }

        $kirimEmail = in_array($this->kanal, ['email', 'keduanya'], true);
        $kirimAplikasi = in_array($this->kanal, ['aplikasi', 'keduanya'], true);
        $users = $this->getTargetUsers();
        $jumlahTarget = $users->count();

        if ($users->isEmpty()) {
            session()->flash('warning', 'Tidak ada akun dinas yang sesuai dengan target penerima.');
            return;
        }

        // Buat pesan baru
        $pesan = Pesan::create([
            'jenis' => 'custom',
            'judul' => $this->judul,
            'isi' => $this->isi,
            'kirim_email' => $kirimEmail,
            'kirim_aplikasi' => $kirimAplikasi,
            'jumlah_penerima' => $jumlahTarget,
        ]);

        if ($kirimAplikasi && $users->isNotEmpty()) {
            $pesan->users()->attach($users->pluck('id'));
        }

        $emailTerkirim = false;
        $emailGagal = false;
        $jumlahEmail = 0;
        if ($kirimEmail && $users->isNotEmpty()) {
            try {
                $emails = $users->pluck('email')
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();

                if (!empty($emails)) {
                    Mail::to(config('mail.from.address'))
                        ->bcc($emails)
                        ->send(new AdminPesanMail($pesan, null, 'Bapak/Ibu Pengguna E-Monev KIP'));

                    $emailTerkirim = true;
                    $jumlahEmail = count($emails);
                }
            } catch (\Throwable $e) {
                report($e);
                $emailGagal = true;
            }
        }

        if ($emailTerkirim) {
            $pesan->update([
                'email_dikirim_pada' => now(),
                'jumlah_email_terkirim' => $jumlahEmail,
            ]);
        }

        $selectedKanal = $this->kanal;

        // Reset form dan beri notifikasi sukses
        $this->reset(['kanal', 'judul', 'isi', 'targetMode', 'targetUserIds', 'targetCondition']);
        $this->kanal = 'aplikasi';
        $this->targetMode = 'semua';
        $this->targetCondition = 'belum_mengisi';
        session()->flash(
            $emailGagal ? 'warning' : 'success',
            $emailGagal
                ? 'Pesan berhasil disimpan, tetapi email gagal dikirim. Periksa konfigurasi mail server.'
                : match ($selectedKanal) {
                    'email' => 'Pesan email berhasil dikirim ke ' . $jumlahEmail . ' alamat.',
                    'keduanya' => 'Pesan berhasil dikirim ke notifikasi aplikasi dan email untuk ' . $jumlahEmail . ' alamat.',
                    default => 'Pesan notifikasi berhasil dikirim ke ' . $jumlahTarget . ' akun dinas di dalam aplikasi.',
                }
        );
    }

    /**
     * Enter edit mode for a message
     */
    public function editPesan($id): void
    {
        $this->selectedPesan = Pesan::find($id);
        $this->kanal = $this->selectedPesan->kirim_email && $this->selectedPesan->kirim_aplikasi
            ? 'keduanya'
            : ($this->selectedPesan->kirim_email ? 'email' : 'aplikasi');
        $this->judul = $this->selectedPesan->judul;
        $this->isi = $this->selectedPesan->isi;
        $this->isEditMode = true;
    }

    /**
     * Update the selected message
     */
    public function updatePesan(): void
    {
        $validated = $this->validate([
            'kanal' => 'required|in:aplikasi,email,keduanya',
            'judul' => 'required|string|max:255',
            'isi' => 'required|string',
        ]);

        $this->selectedPesan->update([
            'jenis' => 'custom',
            'judul' => $validated['judul'],
            'isi' => $validated['isi'],
            'kirim_email' => in_array($validated['kanal'], ['email', 'keduanya'], true),
            'kirim_aplikasi' => in_array($validated['kanal'], ['aplikasi', 'keduanya'], true),
        ]);
        session()->flash('success', 'Pesan berhasil diperbarui.');
        $this->cancelEdit();
    }

    /**
     * Show delete confirmation modal
     */
    public function confirmDelete($id): void
    {
        $this->pesanIdToDelete = $id;
        $this->showDeleteModal = true;
    }

    /**
     * Delete a message
     */
    public function deletePesan(): void
    {
        $pesan = Pesan::find($this->pesanIdToDelete);
        $pesan->delete();

        session()->flash('success', 'Pesan berhasil dihapus.');
        $this->showDeleteModal = false;
        $this->pesanIdToDelete = null;
    }

    /**
     * Cancel edit mode
     */
    public function cancelEdit(): void
    {
        $this->reset(['kanal', 'judul', 'isi', 'targetMode', 'targetUserIds', 'targetCondition', 'selectedPesan', 'isEditMode']);
        $this->kanal = 'aplikasi';
        $this->targetMode = 'semua';
        $this->targetCondition = 'belum_mengisi';
    }

    /**
     * Cancel delete operation
     */
    public function cancelDelete(): void
    {
        $this->showDeleteModal = false;
        $this->pesanIdToDelete = null;
    }

    /**
     * Handle form submission based on mode
     */
    public function submit(): void
    {
        if ($this->isEditMode) {
            $this->updatePesan();
        } else {
            $this->kirimPesan();
        }
    }
}; ?>

<div>
    <x-slot name="header">
        <div class="flex items-center space-x-8">
            <h1 class="text-3xl font-bold text-gray-900">Pesan</h1>
        </div>
    </x-slot>

    <main class="p-8">
        @if (session('success'))
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
                <p>{{ session('success') }}</p>
            </div>
        @endif
        @if (session('warning'))
            <div class="bg-amber-100 border-l-4 border-amber-500 text-amber-700 p-4 mb-6" role="alert">
                <p>{{ session('warning') }}</p>
            </div>
        @endif

        <!-- Daftar Pesan -->
        <div class="bg-white p-6 rounded-lg shadow-md mb-8">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Daftar Pesan Notifikasi</h2>

            @if($this->pesanList->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-16">No</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Judul</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-36">Channel</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Isi</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-32">Penerima</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-36">Email</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-48">Tanggal Dibuat</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-40">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($this->pesanList->items() as $index => $pesan)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ ($this->pesanList->currentPage() - 1) * $this->pesanList->perPage() + $index + 1 }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-semibold">
                                        {{ $pesan->judul }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                        <span class="px-2 py-1 rounded-full text-xs font-medium
                                            {{ $pesan->kirim_email && $pesan->kirim_aplikasi ? 'bg-indigo-100 text-indigo-800' : ($pesan->kirim_email ? 'bg-cyan-100 text-cyan-800' : 'bg-blue-100 text-blue-800') }}">
                                            {{ $pesan->kirim_email && $pesan->kirim_aplikasi ? 'Keduanya' : ($pesan->kirim_email ? 'Email' : 'Aplikasi') }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate">
                                        {{ \Illuminate\Support\Str::limit($pesan->isi, 100) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                        @php
                                            $jumlahPenerima = $pesan->jumlah_penerima ?? ($pesan->kirim_aplikasi ? $pesan->users_count : null);
                                        @endphp
                                        @if($jumlahPenerima)
                                            <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-medium">
                                                {{ $jumlahPenerima }} User
                                            </span>
                                        @else
                                            <span class="px-2 py-1 bg-gray-100 text-gray-600 rounded-full text-xs font-medium">
                                                Tidak ada
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-500">
                                        @if($pesan->kirim_email)
                                            <span class="px-2 py-1 bg-emerald-100 text-emerald-800 rounded-full text-xs font-medium">
                                                Dikirim
                                            </span>
                                        @else
                                            <span class="px-2 py-1 bg-gray-100 text-gray-600 rounded-full text-xs font-medium">
                                                Tidak
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                        {{ $pesan->created_at }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                        <div class="flex justify-center items-center space-x-2">
                                            <button wire:click="editPesan({{ $pesan->id }})" class="p-2 rounded-md bg-yellow-500 text-white hover:bg-yellow-600" title="Edit">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </button>
                                            <button wire:click="confirmDelete({{ $pesan->id }})" class="p-2 rounded-md bg-red-600 text-white hover:bg-red-700" title="Hapus">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if($this->pesanList->hasPages())
                    <div class="mt-4">
                        {{ $this->pesanList->links() }}
                    </div>
                @endif
            @else
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">Belum ada pesan</h3>
                    <p class="mt-1 text-sm text-gray-500">Mulai dengan membuat pesan notifikasi baru.</p>
                </div>
            @endif
        </div>

        <!-- Form Create/Edit Pesan -->
        <div class="bg-white p-6 rounded-lg shadow-md">
            <div class="border-b pb-4 mb-4">
                <h2 class="text-lg font-semibold text-gray-800">
                    {{ $isEditMode ? 'Edit Pesan Notifikasi' : 'Kirim Pesan Notifikasi' }}
                </h2>
                <p class="mt-1 text-sm text-gray-600">
                    {{ $isEditMode ? 'Perbarui pesan notifikasi yang sudah ada.' : 'Kirim pemberitahuan penting, peringatan, atau info deadline kepada seluruh akun dinas yang terdaftar di sistem E-Monev KIP.' }}
                </p>
            </div>

            <form wire:submit="submit">
                <div class="space-y-4">
                    <div>
                        <label for="judul" class="block text-sm font-medium text-gray-700">Judul Notifikasi</label>
                        <input wire:model="judul" id="judul" type="text"
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                            placeholder="Masukkan judul notifikasi">
                        @error('judul') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label for="kanal" class="block text-sm font-medium text-gray-700">Channel Pengiriman</label>
                        <select wire:model="kanal" id="kanal"
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            <option value="aplikasi">Notifikasi aplikasi saja</option>
                            <option value="email">Email saja</option>
                            <option value="keduanya">Email + notifikasi aplikasi</option>
                        </select>
                        @error('kanal') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>
                    @if(!$isEditMode)
                        <div>
                            <label for="targetMode" class="block text-sm font-medium text-gray-700">Target Penerima</label>
                            <select wire:model.live="targetMode" id="targetMode"
                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                <option value="semua">Semua akun dinas</option>
                                <option value="tertentu">Pilih akun dinas tertentu</option>
                                <option value="kondisi">Akun dinas dengan kondisi tertentu</option>
                            </select>
                            @error('targetMode') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>

                        @if($targetMode === 'tertentu')
                            <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                                <p class="text-sm font-medium text-gray-800 mb-3">Pilih Akun Dinas</p>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 max-h-72 overflow-y-auto pr-1">
                                    @foreach($this->dinasOptions as $dinas)
                                        <label class="flex items-start gap-3 rounded-md border border-gray-200 bg-white p-3">
                                            <input wire:model="targetUserIds" type="checkbox" value="{{ $dinas->id }}" class="mt-1 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                            <span class="min-w-0">
                                                <span class="block text-sm font-medium text-gray-900">
                                                    {{ $dinas->badanPublik->nama_badan_publik ?? $dinas->name }}
                                                </span>
                                                <span class="block text-xs text-gray-500 truncate">
                                                    {{ $dinas->email }}
                                                </span>
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                                @error('targetUserIds') <span class="text-red-500 text-xs mt-2 block">{{ $message }}</span> @enderror
                            </div>
                        @endif

                        @if($targetMode === 'kondisi')
                            <div>
                                <label for="targetCondition" class="block text-sm font-medium text-gray-700">Kondisi Akun Dinas</label>
                                <select wire:model="targetCondition" id="targetCondition"
                                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    <option value="belum_mengisi">Belum mengisi kuesioner pada jadwal acuan</option>
                                    <option value="sudah_mengisi">Sudah mengisi kuesioner pada jadwal acuan</option>
                                    <option value="menunggu_verifikasi">Sudah mengisi dan belum terverifikasi</option>
                                    <option value="terverifikasi">Hasil sudah terverifikasi</option>
                                </select>
                                @error('targetCondition') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                <p class="mt-2 text-xs text-gray-500">
                                    Jadwal acuan memakai jadwal aktif. Jika tidak ada jadwal aktif, sistem memakai jadwal terbaru.
                                </p>
                            </div>
                        @endif
                    @endif
                    <div>
                        <label for="isi" class="block text-sm font-medium text-gray-700">Isi Pesan</label>
                        <textarea wire:model="isi" id="isi" rows="8"
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                            placeholder="Masukkan isi pesan atau peringatan yang ingin dikirim"></textarea>
                        @error('isi') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    @if($isEditMode)
                        <button type="button" wire:click="cancelEdit"
                            class="px-6 py-2 border border-gray-300 text-gray-700 font-semibold rounded-md hover:bg-gray-50">
                            Batal
                        </button>
                        <button type="submit"
                            class="px-6 py-2 bg-yellow-500 text-white font-semibold rounded-md hover:bg-yellow-600">
                            Simpan Perubahan
                        </button>
                    @else
                        <button type="submit"
                            class="px-6 py-2 bg-blue-600 text-white font-semibold rounded-md hover:bg-blue-700">
                            Kirim
                        </button>
                    @endif
                </div>
            </form>
        </div>
    </main>

    <!-- Delete Confirmation Modal -->
    @if($showDeleteModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4">
                <!-- Backdrop -->
                <div class="fixed inset-0 bg-gray-900 bg-opacity-50 transition-opacity" aria-hidden="true" wire:click="cancelDelete"></div>

                <!-- Modal panel -->
                <div class="relative bg-white rounded-lg shadow-xl max-w-lg w-full mx-auto transform transition-all">
                    <!-- Header -->
                    <div class="flex items-center justify-between p-6 border-b border-gray-200">
                        <div class="flex items-center space-x-3">
                            <div class="flex-shrink-0 flex items-center justify-center h-10 w-10 rounded-full bg-red-100">
                                <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <h3 class="text-xl font-semibold text-gray-900" id="modal-title">
                                Konfirmasi Hapus
                            </h3>
                        </div>
                        <button wire:click="cancelDelete" class="text-gray-400 hover:text-gray-500 transition-colors">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="p-6">
                        <p class="text-sm text-gray-600 mb-6">
                            Apakah Anda yakin ingin menghapus pesan notifikasi ini? Tindakan ini tidak dapat dibatalkan.
                        </p>

                        @if($pesanIdToDelete)
                            @php
                                $pesanToDelete = \App\Models\Pesan::find($pesanIdToDelete);
                            @endphp
                            @if($pesanToDelete)
                                <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                                    <div class="flex items-start space-x-3">
                                        <svg class="h-5 w-5 text-red-600 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <div class="flex-1">
                                            <p class="text-sm font-bold text-red-900 mb-1">
                                                {{ $pesanToDelete->judul }}
                                            </p>
                                            <p class="text-sm text-red-700">
                                                {{ \Illuminate\Support\Str::limit($pesanToDelete->isi, 200) }}
                                            </p>
                                            <div class="mt-3 flex items-center space-x-4 text-xs text-red-600">
                                                <span class="flex items-center space-x-1">
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                    <span>{{ $pesanToDelete->created_at }}</span>
                                                </span>
                                                <span class="flex items-center space-x-1">
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                                    </svg>
                                                    <span>{{ $pesanToDelete->users_count }} Penerima</span>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endif
                    </div>

                    <!-- Footer -->
                    <div class="bg-gray-50 px-6 py-4 flex flex-row-reverse space-x-3 space-x-reverse border-t border-gray-200">
                        <button type="button" wire:click="deletePesan"
                            class="inline-flex items-center justify-center rounded-md border border-transparent shadow-sm px-6 py-2.5 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:text-sm transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            Ya, Hapus Pesan
                        </button>
                        <button type="button" wire:click="cancelDelete"
                            class="inline-flex items-center justify-center rounded-md border border-gray-300 shadow-sm px-6 py-2.5 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:text-sm transition-colors">
                            Batal
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
