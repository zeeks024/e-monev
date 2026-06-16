<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\Jadwal;
use App\Models\JadwalPertanyaan;
use App\Models\PertanyaanTemplate;
use Carbon\Carbon;

new #[Layout('components.layouts.admin')] class extends Component
{
    public $jadwalList;
    public $jadwalIdToDelete;
    public ?Jadwal $selectedJadwalToDelete = null;
    public $jadwalIdToEdit;
    public bool $showDeleteModal = false;
    public bool $showEditModal = false;
    public bool $isEditMode = false;

    // Form properties
    public $nama;
    public $tahun;
    public $tanggal_mulai;
    public $tanggal_selesai;
    public $deskripsi;

    public function mount(): void
    {
        $this->loadJadwal();
    }

    public function loadJadwal(): void
    {
        $this->jadwalList = Jadwal::withCount(['jadwalPertanyaans', 'submissions'])
            ->orderBy('tahun', 'desc')
            ->orderBy('tanggal_mulai', 'desc')
            ->get();
    }

    public function createJadwal(): void
    {
        $validated = $this->validate([
            'nama' => 'required|string|max:255',
            'tahun' => 'required|integer|min:2000|max:2100',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after:tanggal_mulai',
            'deskripsi' => 'nullable|string',
        ]);

        $sourceJadwal = Jadwal::with('jadwalPertanyaans')
            ->orderBy('tanggal_mulai', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        // Set other jadwals to inactive
        Jadwal::query()->update(['is_active' => false]);

        // Create new jadwal
        $jadwal = Jadwal::create([
            ...$validated,
            'is_active' => true,
        ]);

        $copiedCount = 0;

        if ($sourceJadwal && $sourceJadwal->jadwalPertanyaans->isNotEmpty()) {
            $copiedCount = $sourceJadwal->jadwalPertanyaans->count();

            $sourceJadwal->jadwalPertanyaans
                ->sortBy('urutan')
                ->each(function (JadwalPertanyaan $jadwalPertanyaan) use ($jadwal): void {
                    JadwalPertanyaan::create([
                        'jadwal_id' => $jadwal->id,
                        'pertanyaan_template_id' => $jadwalPertanyaan->pertanyaan_template_id,
                        'teks_pertanyaan' => $jadwalPertanyaan->teks_pertanyaan,
                        'definisi_operasional' => $jadwalPertanyaan->definisi_operasional,
                        'urutan' => $jadwalPertanyaan->urutan,
                        'tipe_jawaban' => $jadwalPertanyaan->tipe_jawaban,
                        'butuh_link' => $jadwalPertanyaan->butuh_link,
                        'butuh_upload' => $jadwalPertanyaan->butuh_upload,
                        'skor_maks' => $jadwalPertanyaan->skor_maks,
                    ]);
                });
        }

        $message = 'Jadwal baru berhasil dibuat dan otomatis diaktifkan.';

        if ($copiedCount > 0) {
            $message .= " {$copiedCount} pertanyaan dari jadwal sebelumnya ikut disalin.";
        } else {
            $message .= ' Belum ada pertanyaan yang disalin, jadi Anda bisa menambahkan pertanyaan awal untuk periode ini.';
        }

        session()->flash('success', $message);
        $this->reset(['nama', 'tahun', 'tanggal_mulai', 'tanggal_selesai', 'deskripsi', 'isEditMode', 'jadwalIdToEdit']);
        $this->showEditModal = false;
        $this->loadJadwal();
    }

    public function editJadwal($jadwalId): void
    {
        $jadwal = Jadwal::find($jadwalId);
        if ($jadwal) {
            $this->isEditMode = true;
            $this->jadwalIdToEdit = $jadwalId;
            $this->nama = $jadwal->nama;
            $this->tahun = $jadwal->tahun;
            $this->tanggal_mulai = $jadwal->tanggal_mulai->format('Y-m-d\TH:i');
            $this->tanggal_selesai = $jadwal->tanggal_selesai->format('Y-m-d\TH:i');
            $this->deskripsi = $jadwal->deskripsi;
            $this->showEditModal = true;
        }
    }

    public function updateJadwal(): void
    {
        $validated = $this->validate([
            'nama' => 'required|string|max:255',
            'tahun' => 'required|integer|min:2000|max:2100',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after:tanggal_mulai',
            'deskripsi' => 'nullable|string',
        ]);

        $jadwal = Jadwal::find($this->jadwalIdToEdit);
        if ($jadwal) {
            $jadwal->update($validated);
            session()->flash('success', 'Jadwal berhasil diperbarui.');
        }

        $this->reset(['nama', 'tahun', 'tanggal_mulai', 'tanggal_selesai', 'deskripsi', 'isEditMode', 'jadwalIdToEdit']);
        $this->showEditModal = false;
        $this->loadJadwal();
    }

    public function cancelEdit(): void
    {
        $this->reset(['nama', 'tahun', 'tanggal_mulai', 'tanggal_selesai', 'deskripsi', 'isEditMode', 'jadwalIdToEdit']);
        $this->showEditModal = false;
    }

    public function cancelDelete(): void
    {
        $this->showDeleteModal = false;
        $this->selectedJadwalToDelete = null;
        $this->jadwalIdToDelete = null;
    }

    public function setActiveJadwal($jadwalId): void
    {
        Jadwal::query()->update(['is_active' => false]);
        Jadwal::find($jadwalId)->update(['is_active' => true]);
        session()->flash('success', 'Jadwal aktif berhasil diperbarui.');
        $this->loadJadwal();
    }

    public function confirmDelete($jadwalId): void
    {
        $this->jadwalIdToDelete = $jadwalId;
        $this->selectedJadwalToDelete = Jadwal::with(['jadwalPertanyaans', 'submissions'])->find($jadwalId);
        $this->showDeleteModal = true;
    }

    public function deleteJadwal(): void
    {
        $jadwal = Jadwal::find($this->jadwalIdToDelete);
        if (! $jadwal) {
            session()->flash('error', 'Jadwal tidak ditemukan.');
            $this->showDeleteModal = false;
            $this->selectedJadwalToDelete = null;
            $this->jadwalIdToDelete = null;
            return;
        }

        $jadwal->delete();
        session()->flash('success', 'Jadwal berhasil dihapus.');
        $this->showDeleteModal = false;
        $this->selectedJadwalToDelete = null;
        $this->jadwalIdToDelete = null;
        $this->loadJadwal();
    }
}; ?>

<div>
    <x-slot name="header">
        <div class="flex items-center space-x-4">
            <a href="{{ route('admin.kuesioner') }}" wire:navigate class="text-gray-500 hover:text-gray-800">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
            <h1 class="text-3xl font-bold text-gray-900">Manajemen Jadwal</h1>
        </div>
    </x-slot>

    <main class="p-8">
        <!-- Flash Messages -->
        @if (session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
                <strong class="font-bold">Sukses!</strong>
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
        @endif

        @if (session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                <strong class="font-bold">Error!</strong>
                <span class="block sm:inline">{{ session('error') }}</span>
            </div>
        @endif

        <!-- Form Create Jadwal -->
        <div class="bg-white p-6 rounded-lg shadow-md mb-8">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Buat Jadwal Baru</h2>
            <div class="mb-4 rounded-lg border border-blue-100 bg-blue-50 p-4 text-sm text-blue-900">
                Secara default, jadwal baru akan menyalin pertanyaan dari jadwal sebelumnya. Jadi biasanya Anda cukup membuat periode baru, lalu hanya menyesuaikan jika memang ada perubahan pertanyaan.
            </div>
            <form wire:submit.prevent="createJadwal">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="nama" class="block text-sm font-medium text-gray-700">Nama Jadwal</label>
                        <input wire:model="nama" id="nama" type="text"
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                            placeholder="Contoh: E-Monev KIP 2026">
                        @error('nama') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label for="tahun" class="block text-sm font-medium text-gray-700">Tahun</label>
                        <input wire:model="tahun" id="tahun" type="number"
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                            placeholder="2026">
                        @error('tahun') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label for="tanggal_mulai" class="block text-sm font-medium text-gray-700">Tanggal & Jam Mulai</label>
                        <input wire:model="tanggal_mulai" id="tanggal_mulai" type="datetime-local"
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        @error('tanggal_mulai') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label for="tanggal_selesai" class="block text-sm font-medium text-gray-700">Tanggal & Jam Selesai</label>
                        <input wire:model="tanggal_selesai" id="tanggal_selesai" type="datetime-local"
                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        @error('tanggal_selesai') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="mt-4">
                    <label for="deskripsi" class="block text-sm font-medium text-gray-700">Deskripsi</label>
                    <textarea wire:model="deskripsi" id="deskripsi" rows="2"
                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                        placeholder="Deskripsi periode evaluasi..."></textarea>
                    @error('deskripsi') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                </div>
                <p class="mt-3 text-xs text-gray-500">
                    Waktu jadwal mengikuti zona waktu WIB (`Asia/Jakarta`).
                </p>
                <div class="mt-4">
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Buat Jadwal
                    </button>
                </div>
            </form>
        </div>

        <!-- Daftar Jadwal -->
        <div class="bg-white p-6 rounded-lg shadow-md">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Daftar Jadwal</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tahun</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Periode</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pertanyaan</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Submission</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($jadwalList as $jadwal)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ $jadwal->nama }}
                                    @if($jadwal->deskripsi)
                                        <p class="text-xs text-gray-500 mt-1">{{ \Illuminate\Support\Str::limit($jadwal->deskripsi, 50) }}</p>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $jadwal->tahun }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ Carbon::parse($jadwal->tanggal_mulai)->timezone('Asia/Jakarta')->isoFormat('D MMM YYYY, HH:mm') }} WIB -
                                    {{ Carbon::parse($jadwal->tanggal_selesai)->timezone('Asia/Jakarta')->isoFormat('D MMM YYYY, HH:mm') }} WIB
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($jadwal->is_active)
                                        <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs font-medium">Aktif</span>
                                    @else
                                        <span class="px-2 py-1 bg-gray-100 text-gray-800 rounded-full text-xs font-medium">Non-Aktif</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $jadwal->jadwal_pertanyaans_count }} pertanyaan
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $jadwal->submissions_count }} submission
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex justify-center items-center space-x-2">
                                        <button wire:click="editJadwal({{ $jadwal->id }})"
                                            class="text-yellow-600 hover:text-yellow-900 font-medium"
                                            title="Edit">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>

                                        @if(!$jadwal->is_active)
                                            <button wire:click="setActiveJadwal({{ $jadwal->id }})"
                                                class="text-blue-600 hover:text-blue-900 font-medium"
                                                title="Set Aktif">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                            </button>
                                        @endif

                                        <button wire:click="confirmDelete({{ $jadwal->id }})"
                                            class="text-red-600 hover:text-red-900 font-medium"
                                            title="Hapus">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-sm text-gray-500">
                                    Belum ada jadwal yang dibuat.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Guide Section -->
            @if($jadwalList->isNotEmpty())
                <div class="mt-8 p-4 bg-blue-50 rounded-lg">
                    <h3 class="text-sm font-semibold text-blue-900 mb-2">Panduan Penggunaan Jadwal</h3>
                    <ol class="text-sm text-blue-800 space-y-1 list-decimal list-inside">
                        <li>Buat jadwal baru dengan mengisi form di atas</li>
                        <li>Jadwal baru otomatis menjadi jadwal aktif dan menyalin pertanyaan dari jadwal sebelumnya bila tersedia</li>
                        <li>Klik tombol edit (ikon pensil) untuk mengubah informasi jadwal</li>
                        <li>Admin biasanya cukup meninjau pertanyaan hasil salinan, lalu mengedit hanya jika ada perubahan periode</li>
                        <li>Untuk menonaktifkan jadwal aktif, klik tombol aktif pada jadwal lain</li>
                        <li>Menghapus jadwal akan ikut menghapus pertanyaan jadwal dan submission yang terkait</li>
                    </ol>
                </div>
            @endif
        </div>

        <!-- Delete Confirmation Modal -->
        @if($showDeleteModal)
            <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
                <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                    <div class="mt-3 text-center">
                        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                            <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.932-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.932 3z" />
                            </svg>
                        </div>
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Hapus Jadwal</h3>
                        <div class="mt-2 px-7 py-3 space-y-3">
                            <p class="text-sm text-gray-500">
                                Apakah Anda yakin ingin menghapus jadwal ini? Tindakan ini tidak dapat dibatalkan.
                            </p>
                            @if($selectedJadwalToDelete)
                                <div class="rounded-lg border border-red-200 bg-red-50 p-3 text-left">
                                    <p class="text-sm font-semibold text-red-900">{{ $selectedJadwalToDelete->nama }}</p>
                                    <p class="mt-1 text-xs text-red-700">
                                        Penghapusan ini juga akan menghapus {{ $selectedJadwalToDelete->jadwalPertanyaans->count() }} pertanyaan jadwal dan {{ $selectedJadwalToDelete->submissions->count() }} submission yang terkait.
                                    </p>
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="flex justify-center space-x-4 mt-6">
                        <button wire:click="cancelDelete"
                            class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                            Batal
                        </button>
                        <button wire:click="deleteJadwal"
                            class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                            Ya, Hapus
                        </button>
                    </div>
                </div>
            </div>
        @endif

        <!-- Edit Jadwal Modal -->
        @if($showEditModal)
            <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
                <div class="relative top-10 mx-auto p-6 border w-full max-w-2xl shadow-lg rounded-md bg-white">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">Edit Jadwal</h3>
                        <button wire:click="cancelEdit" class="text-gray-400 hover:text-gray-500">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form wire:submit.prevent="updateJadwal">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="edit_nama" class="block text-sm font-medium text-gray-700">Nama Jadwal</label>
                                <input wire:model="nama" id="edit_nama" type="text"
                                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    placeholder="Contoh: E-Monev KIP 2026">
                                @error('nama') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label for="edit_tahun" class="block text-sm font-medium text-gray-700">Tahun</label>
                                <input wire:model="tahun" id="edit_tahun" type="number"
                                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    placeholder="2026">
                                @error('tahun') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label for="edit_tanggal_mulai" class="block text-sm font-medium text-gray-700">Tanggal & Jam Mulai</label>
                                <input wire:model="tanggal_mulai" id="edit_tanggal_mulai" type="datetime-local"
                                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                @error('tanggal_mulai') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label for="edit_tanggal_selesai" class="block text-sm font-medium text-gray-700">Tanggal & Jam Selesai</label>
                                <input wire:model="tanggal_selesai" id="edit_tanggal_selesai" type="datetime-local"
                                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                @error('tanggal_selesai') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="mt-4">
                            <label for="edit_deskripsi" class="block text-sm font-medium text-gray-700">Deskripsi</label>
                            <textarea wire:model="deskripsi" id="edit_deskripsi" rows="3"
                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                placeholder="Deskripsi periode evaluasi..."></textarea>
                            @error('deskripsi') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>
                        <div class="flex justify-end space-x-3 mt-6">
                            <button type="button" wire:click="cancelEdit"
                                class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                                Batal
                            </button>
                            <button type="submit"
                                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    </main>
</div>
