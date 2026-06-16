<?php

use App\Models\Kategori;
use App\Models\Jadwal;
use App\Models\JadwalPertanyaan;
use App\Models\Jawaban;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new #[Layout('components.layouts.app')] class extends Component
{
    use WithFileUploads;

    public $kategoriList;
    public $activeKategoriId;
    public $jadwal;
    public $jadwalPertanyaanList = [];

    public $jawaban = [];
    public $link_dokumen = [];
    public $upload_dokumen = [];

    /**
     * Mount the component, load categories and questions.
     */
    public function mount(): void
    {
        // Get active schedule
        $this->jadwal = Jadwal::active()->first();

        if (!$this->jadwal) {
            session()->flash('error', 'Tidak ada jadwal aktif saat ini.');
            return;
        }

        $this->kategoriList = Kategori::orderBy('id')->get();
        if ($this->kategoriList->isNotEmpty()) {
            $this->changeKategori($this->kategoriList->first()->id);
        }
    }

    /**
     * Change the active category and load its questions.
     */
    public function changeKategori($kategoriId): void
    {
        if (!$this->jadwal) {
            return;
        }

        $this->activeKategoriId = $kategoriId;

        // Load jadwal_pertanyaans for active schedule and this category
        $this->jadwalPertanyaanList = JadwalPertanyaan::where('jadwal_id', $this->jadwal->id)
            ->whereHas('pertanyaanTemplate', function($query) use ($kategoriId) {
                $query->where('kategori_id', $kategoriId);
            })
            ->orderBy('urutan')
            ->get();
    }

    /**
     * Save the answers and automatically move to the next category or finish.
     */
    public function simpanJawaban(): void
    {
        if (!$this->jadwal) {
            session()->flash('error', 'Tidak ada jadwal aktif.');
            return;
        }

        // Validasi input
        $this->validate([
            'jawaban.*' => ['nullable', 'in:Ya,Tidak'],
            'link_dokumen.*' => ['nullable', 'url'],
            'upload_dokumen.*' => ['nullable', 'file', 'mimes:pdf', 'max:20480'],
        ]);

        $submission = \App\Models\Submission::create([
            'user_id' => Auth::id(),
            'kategori_id' => $this->activeKategoriId,
            'jadwal_id' => $this->jadwal->id,
            'tanggal_submit' => now(),
        ]);

        // Proses penyimpanan jawaban ke database
        foreach ($this->jadwalPertanyaanList as $jadwalPertanyaan) {
            $jawabanData = [
                'submission_id' => $submission->id,
                'jadwal_pertanyaan_id' => $jadwalPertanyaan->id,
                'jawaban' => $this->jawaban[$jadwalPertanyaan->id] ?? null,
                'link_dokumen' => $this->link_dokumen[$jadwalPertanyaan->id] ?? null,
            ];

            if (isset($this->upload_dokumen[$jadwalPertanyaan->id])) {
                $path = $this->upload_dokumen[$jadwalPertanyaan->id]->store('dokumen_kuesioner', 'public');
                $jawabanData['upload_dokumen'] = $path;
            }

            \App\Models\Jawaban::updateOrCreate(
                [
                    'submission_id' => $submission->id,
                    'jadwal_pertanyaan_id' => $jadwalPertanyaan->id,
                ],
                $jawabanData
            );
        }

        // Logika untuk Perpindahan Otomatis
        $currentKategoriIndex = $this->kategoriList->search(function ($kategori) {
            return $kategori->id == $this->activeKategoriId;
        });

        if ($currentKategoriIndex !== false && isset($this->kategoriList[$currentKategoriIndex + 1])) {
            $nextKategori = $this->kategoriList[$currentKategoriIndex + 1];
            $this->changeKategori($nextKategori->id);
            session()->flash('status', 'Jawaban berhasil disimpan! Lanjut ke kategori berikutnya.');
        } else {
            session()->flash('status', 'Selamat! Anda telah menyelesaikan seluruh kuesioner.');
            $this->redirectRoute('kuesioner');
        }
    }

    /**
     * Log the current user out of the application.
     */
    public function logout(): void
    {
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();
        $this->redirect('/', navigate: true);
    }
}; ?>


    <div class="min-h-screen bg-gray-100">
        <main class="py-12">
            <div class="max-w-screen-xl mx-auto px-6 md:px-20">
                @if (session('status'))
                    <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                        <span class="block sm:inline">{{ session('status') }}</span>
                    </div>
                @endif

                @if (session('error'))
                    <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                        <strong class="font-bold">Error!</strong>
                        <span class="block sm:inline">{{ session('error') }}</span>
                    </div>
                @endif

                @if(!$jadwal)
                    <div class="text-center py-12 bg-white rounded-lg shadow-md">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Tidak Ada Jadwal Aktif</h3>
                        <p class="text-gray-500">Mohon kembali lagi ketika periode pengisian kuesioner telah dibuka.</p>
                    </div>
                @else
                    <div class="bg-white p-8 rounded-lg shadow-md">
                        <div class="mb-6">
                            <p class="text-sm text-gray-500">
                                Periode: <span class="font-semibold">{{ $jadwal->nama }} ({{ $jadwal->tahun }})</span>
                            </p>
                        </div>

                        <div class="mb-8">
                            <nav class="flex space-x-4 overflow-x-auto" aria-label="Tabs">
                                @forelse($kategoriList as $kategori)
                                    <button wire:click="changeKategori({{ $kategori->id }})"
                                            class="{{ $activeKategoriId == $kategori->id ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }} whitespace-nowrap py-2 px-5 rounded-md font-medium text-sm transition">
                                        {{ $kategori->nama }}
                                    </button>
                                @empty
                                    <p class="text-sm text-gray-500">Kategori belum tersedia. Silakan hubungi admin.</p>
                                @endforelse
                            </nav>
                        </div>

                        <div class="mt-8">
                            <form wire:submit="simpanJawaban">
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                         <thead class="bg-gray-50">
                                             <tr>
                                                 <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-12">No</th>
                                                 <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pertanyaan</th>
                                                 <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Definisi Operasional</th>
                                                 <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pilih Jawaban</th>
                                                 <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Link Dokumen</th>
                                                 <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Upload Dokumen</th>
                                             </tr>
                                         </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            @forelse($jadwalPertanyaanList as $index => $jadwalPertanyaan)
                                                 <tr>
                                                     <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $index + 1 }}</td>
                                                     <td class="px-6 py-4 whitespace-normal text-sm text-gray-900">{{ $jadwalPertanyaan->teks_pertanyaan }}</td>
                                                     <td class="px-6 py-4 whitespace-normal text-sm text-gray-600">{{ $jadwalPertanyaan->definisi_operasional ?: '-' }}</td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <div class="flex items-center space-x-4">
                                                            <label class="flex items-center">
                                                                <input wire:model="jawaban.{{ $jadwalPertanyaan->id }}" type="radio" value="Ya" class="form-radio h-4 w-4 text-blue-600">
                                                                <span class="ml-2 text-sm text-gray-700">Ya</span>
                                                            </label>
                                                            <label class="flex items-center">
                                                                <input wire:model="jawaban.{{ $jadwalPertanyaan->id }}" type="radio" value="Tidak" class="form-radio h-4 w-4 text-blue-600">
                                                                <span class="ml-2 text-sm text-gray-700">Tidak</span>
                                                            </label>
                                                        </div>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        @if($jadwalPertanyaan->butuh_link)
                                                            <input wire:model="link_dokumen.{{ $jadwalPertanyaan->id }}" type="url" placeholder="Masukkan link..." class="w-full border-gray-300 rounded-md shadow-sm text-sm">
                                                        @else
                                                            <span class="text-gray-400">-</span>
                                                        @endif
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        @if($jadwalPertanyaan->butuh_upload)
                                                            <input wire:model="upload_dokumen.{{ $jadwalPertanyaan->id }}" type="file" class="text-sm">
                                                            <p class="text-xs text-gray-500 mt-1">PDF (maks 20MB)</p>
                                                            <x-input-error :messages="$errors->get('upload_dokumen.' . $jadwalPertanyaan->id)" class="mt-1" />
                                                        @else
                                                            <span class="text-gray-400">-</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                             @empty
                                                 <tr>
                                                     <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">
                                                         Belum ada pertanyaan untuk kategori ini pada periode ini.
                                                     </td>
                                                 </tr>
                                             @endforelse
                                        </tbody>
                                    </table>
                                </div>
                                <div class="mt-8 flex justify-end">
                                    <button type="submit" class="px-6 py-2 bg-blue-600 text-white font-semibold rounded-md hover:bg-blue-700">
                                        Simpan Jawaban
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                @endif
            </div>
        </main>
    </div>
