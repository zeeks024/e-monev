<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\Kategori;
use App\Models\Jadwal;
use App\Models\JadwalPertanyaan;

new #[Layout('components.layouts.admin')] class extends Component
{
    public Kategori $kategori;
    public ?Jadwal $jadwal;
    public $jadwalPertanyaans;

    /**
     * Mount the component, load the category and its questions.
     */
    public function mount(Kategori $kategori): void
    {
        $this->kategori = $kategori;

        // Get active schedule
        $this->jadwal = Jadwal::active()->first();

        if (!$this->jadwal) {
            session()->flash('error', 'Tidak ada jadwal aktif. Silakan aktifkan jadwal terlebih dahulu.');
            $this->jadwalPertanyaans = collect();
            return;
        }

        $this->loadPertanyaans();
    }

    /**
     * Load questions for the current category and active schedule.
     */
    public function loadPertanyaans(): void
    {
        if (!$this->jadwal) {
            $this->jadwalPertanyaans = collect();
            return;
        }

        // Load jadwal_pertanyaans for active schedule and this category
        $this->jadwalPertanyaans = JadwalPertanyaan::where('jadwal_id', $this->jadwal->id)
            ->whereHas('pertanyaanTemplate', function($query) {
                $query->where('kategori_id', $this->kategori->id);
            })
            ->orderBy('urutan')
            ->get();
    }

    /**
     * Delete a question.
     */
    public function deletePertanyaan(JadwalPertanyaan $jadwalPertanyaan): void
    {
        $jadwalPertanyaan->delete();
        session()->flash('success', 'Pertanyaan berhasil dihapus.');
        $this->loadPertanyaans(); // Muat ulang daftar pertanyaan
    }
}; ?>

<div>
    <div class="p-4 sm:p-6 lg:p-8">
        @if (session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
        @endif

        @if (session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <strong class="font-bold">Error!</strong>
                <span class="block sm:inline">{{ session('error') }}</span>
            </div>
        @endif

        <div class="bg-white p-6 rounded-lg shadow-md">
            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <div class="flex items-center">
                    <a href="{{ route('admin.kuesioner') }}" wire:navigate class="text-gray-500 hover:text-gray-800">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </a>
                    <div>
                        <h1 class="text-2xl font-semibold text-gray-800 ml-4">
                            List Detail Pertanyaan {{ $kategori->nama }}
                        </h1>
                        @if($jadwal)
                            <p class="text-sm text-gray-500 ml-4 mt-1">
                                Jadwal: {{ $jadwal->nama }} ({{ $jadwal->tahun }})
                            </p>
                        @endif
                    </div>
                </div>

                <a href="{{ route('admin.kuesioner.pertanyaan.create', ['kategori' => $kategori->id]) }}" wire:navigate class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 inline-flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                    Buat Pertanyaan
                </a>
            </div>

            @if(!$jadwal)
                <div class="text-center py-12 bg-gray-50 rounded-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Tidak Ada Jadwal Aktif</h3>
                    <p class="text-gray-500 mb-4">Silakan aktifkan jadwal terlebih dahulu atau buat jadwal baru.</p>
                    <a href="{{ route('admin.kuesioner.jadwal') }}" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                        Kelola Jadwal
                    </a>
                </div>
            @else
                <!-- Tabel Pertanyaan -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                             <tr>
                                 <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-16">No</th>
                                 <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pertanyaan</th>
                                 <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Definisi Operasional</th>
                                 <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Pilih Jawaban</th>
                                 <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Link Dokumen</th>
                                 <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Upload Dokumen</th>
                                 <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-40">Aksi</th>
                             </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse ($jadwalPertanyaans as $index => $jadwalPertanyaan)
                                 <tr>
                                     <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $index + 1 }}</td>
                                     <td class="px-6 py-4 whitespace-normal text-sm text-gray-500">{{ $jadwalPertanyaan->teks_pertanyaan }}</td>
                                     <td class="px-6 py-4 whitespace-normal text-sm text-gray-500">{{ $jadwalPertanyaan->definisi_operasional ?: '-' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <div class="flex justify-center items-center space-x-4">
                                            <div class="flex items-center">
                                                <input type="radio" disabled class="h-4 w-4 text-indigo-600 border-gray-300">
                                                <label class="ml-2">Ya</label>
                                            </div>
                                            <div class="flex items-center">
                                                <input type="radio" disabled class="h-4 w-4 text-indigo-600 border-gray-300">
                                                <label class="ml-2">Tidak</label>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                        @if($jadwalPertanyaan->butuh_link)
                                            <input type="text" class="w-full border-gray-300 rounded-md shadow-sm text-sm" placeholder="Masukkan Link....">
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                        @if($jadwalPertanyaan->butuh_upload)
                                            <div>
                                                <input type="file" class="text-sm text-gray-500 file:mr-4 file:py-1 file:px-2 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                                                <p class="text-xs text-red-500 mt-1">*Mendukung ekstensi pdf (Maks 20MB)</p>
                                            </div>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                        <div class="flex justify-center items-center space-x-2">
                                            <a href="{{ route('admin.kuesioner.pertanyaan.edit', ['kategori' => $kategori->id, 'jadwalPertanyaan' => $jadwalPertanyaan->id]) }}" wire:navigate class="p-2 rounded-md bg-yellow-500 text-white hover:bg-yellow-600">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </a>
                                            <button
                                                wire:click="deletePertanyaan({{ $jadwalPertanyaan->id }})"
                                                wire:confirm="Anda yakin ingin menghapus pertanyaan ini?"
                                                class="p-2 rounded-md bg-red-600 text-white hover:bg-red-700">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                             @empty
                                 <tr>
                                     <td colspan="7" class="px-6 py-12 text-center text-sm text-gray-500">
                                         Belum ada pertanyaan yang dibuat untuk kategori ini pada jadwal aktif.
                                     </td>
                                 </tr>
                             @endforelse
                        </tbody>
                    </table>
                </div>
            @endif

        </div>
    </div>
</div>
