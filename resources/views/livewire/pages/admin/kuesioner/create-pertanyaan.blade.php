<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\Kategori;
use App\Models\Jadwal;
use App\Models\JadwalPertanyaan;
use App\Models\PertanyaanTemplate;
use App\Models\Submission;

new #[Layout('components.layouts.admin')] class extends Component
{
    public Kategori $kategori;
    public ?Jadwal $jadwal = null;
    public $jadwalPertanyaanId = null;
    public bool $isEditMode = false;
    public bool $isLocked = false;

    public string $teks_pertanyaan = '';
    public string $definisi_operasional = '';
    public string $tipe_jawaban = 'Ya/Tidak';
    public bool $butuh_link = false;
    public bool $butuh_upload = false;
    public $skor_maks = 0;

    /**
     * Mount the component and handle both create and edit modes.
     */
    public function mount(Kategori $kategori, $jadwalPertanyaan = null): void
    {
        $this->kategori = $kategori;

        // Get active schedule
        $this->jadwal = Jadwal::active()->first();

        if (!$this->jadwal) {
            session()->flash('error', 'Tidak ada jadwal aktif. Silakan aktifkan jadwal terlebih dahulu.');
            return;
        }

        $this->isLocked = Submission::where('jadwal_id', $this->jadwal->id)->exists();

        // Check if edit mode (jadwalPertanyaan ID provided)
        if ($jadwalPertanyaan) {
            // Mode Edit - $jadwalPertanyaan is the ID as string
            $this->isEditMode = true;
            $this->jadwalPertanyaanId = $jadwalPertanyaan;

            // Query the model
            $jadwalPertanyaanModel = JadwalPertanyaan::find($jadwalPertanyaan);

            if ($jadwalPertanyaanModel) {
                $this->teks_pertanyaan = $jadwalPertanyaanModel->teks_pertanyaan;
                $this->definisi_operasional = $jadwalPertanyaanModel->pertanyaanTemplate->definisi_operasional ?? '';
                $this->butuh_link = $jadwalPertanyaanModel->butuh_link;
                $this->butuh_upload = $jadwalPertanyaanModel->butuh_upload;
                $this->skor_maks = $jadwalPertanyaanModel->skor_maks;
            }
        }
    }

    /**
     * Save (create or update) the question.
     */
    public function save()
    {
        $validated = $this->validate([
            'teks_pertanyaan' => 'required|string|min:10',
            'definisi_operasional' => 'nullable|string',
            'butuh_link' => 'required|boolean',
            'butuh_upload' => 'required|boolean',
            'skor_maks' => 'required|numeric|min:0',
        ]);

        if (!$this->jadwal) {
            session()->flash('error', 'Tidak ada jadwal aktif.');
            return;
        }

        if ($this->isLocked) {
            session()->flash('error', 'Pertanyaan dan skor pada jadwal aktif ini sudah dikunci karena submission sudah masuk. Buat jadwal baru jika ingin mengubah bobot.');
            return;
        }

        if ($this->isEditMode && $this->jadwalPertanyaanId) {
            // Mode Edit: Update jadwal_pertanyaan directly
            $jadwalPertanyaan = JadwalPertanyaan::find($this->jadwalPertanyaanId);
            if ($jadwalPertanyaan) {
                $jadwalPertanyaan->update([
                    'teks_pertanyaan' => $validated['teks_pertanyaan'],
                    'definisi_operasional' => $validated['definisi_operasional'],
                    'butuh_link' => $validated['butuh_link'],
                    'butuh_upload' => $validated['butuh_upload'],
                    'skor_maks' => $validated['skor_maks'],
                ]);
                session()->flash('success', 'Pertanyaan berhasil diperbarui.');
            }
        } else {
            // Mode Create: Create template first, then create snapshot
            $template = PertanyaanTemplate::create([
                'kategori_id' => $this->kategori->id,
                'teks_pertanyaan' => $validated['teks_pertanyaan'],
                'definisi_operasional' => $validated['definisi_operasional'],
                'tipe_jawaban' => $this->tipe_jawaban,
                'butuh_link' => $validated['butuh_link'],
                'butuh_upload' => $validated['butuh_upload'],
                'is_active' => true,
            ]);

            // Get next order number for this jadwal
            $maxUrutan = JadwalPertanyaan::where('jadwal_id', $this->jadwal->id)->max('urutan') ?? 0;

            // Create snapshot for active schedule
            JadwalPertanyaan::create([
                'jadwal_id' => $this->jadwal->id,
                'pertanyaan_template_id' => $template->id,
                'teks_pertanyaan' => $validated['teks_pertanyaan'],
                'definisi_operasional' => $validated['definisi_operasional'],
                'urutan' => $maxUrutan + 1,
                'tipe_jawaban' => $this->tipe_jawaban,
                'butuh_link' => $validated['butuh_link'],
                'butuh_upload' => $validated['butuh_upload'],
                'skor_maks' => $validated['skor_maks'],
            ]);

            session()->flash('success', 'Pertanyaan baru berhasil ditambahkan.');
        }

        return $this->redirectRoute('admin.kuesioner.pertanyaan.index', ['kategori' => $this->kategori->id]);
    }
}; ?>

<div>
    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
            <strong class="font-bold">Error!</strong>
            <span class="block sm:inline">{{ session('error') }}</span>
        </div>
    @endif

    <div class="p-4 sm:p-6 lg:p-8">
        <div class="bg-white p-6 rounded-lg shadow-md">

            <!-- Header Form (Dinamis) -->
            <div class="flex items-center mb-6">
                <a href="{{ route('admin.kuesioner.pertanyaan.index', ['kategori' => $kategori->id]) }}" wire:navigate class="text-gray-500 hover:text-gray-800">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>
                <h1 class="text-2xl font-semibold text-gray-800 ml-4">
                    {{ $isEditMode ? 'Edit Pertanyaan' : 'Buat Pertanyaan' }}
                </h1>
                @if($jadwal)
                    <span class="ml-4 px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-xs">
                        Jadwal: {{ $jadwal->nama }}
                    </span>
                @endif
            </div>

            @if($isLocked)
                <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                    Jadwal aktif ini sudah memiliki submission, jadi pertanyaan dan skor bobotnya dikunci. Jika ada perubahan bobot, buat jadwal baru agar nilai periode berjalan tidak berubah.
                </div>
            @endif

            <!-- Form -->
            <form wire:submit.prevent="save">
                <div class="space-y-6">

                    <!-- Pertanyaan -->
                    <div>
                        <label for="teks_pertanyaan" class="block text-sm font-medium text-gray-700">Pertanyaan</label>
                        <textarea id="teks_pertanyaan" wire:model="teks_pertanyaan" rows="4"
                                  class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                  @disabled($isLocked)
                                  placeholder="Masukkan Pertanyaan"></textarea>
                        @error('teks_pertanyaan') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>

                    <!-- Definisi Operasional -->
                    <div>
                        <label for="definisi_operasional" class="block text-sm font-medium text-gray-700">Definisi Operasional</label>
                        <textarea id="definisi_operasional" wire:model="definisi_operasional" rows="3"
                                  class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                  @disabled($isLocked)
                                  placeholder="Masukkan Definisi Operasional (opsional)"></textarea>
                        @error('definisi_operasional') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>

                    <!-- Tipe Jawaban -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Tipe Jawaban</label>
                        <div class="mt-2 text-sm text-gray-500">
                            Ya / Tidak
                        </div>
                    </div>

                    <!-- Persyaratan Bukti -->
                    <div>
                        <label for="skor_maks" class="block text-sm font-medium text-gray-700">Skor Maksimum</label>
                        <input id="skor_maks" wire:model="skor_maks" type="number" min="0" step="0.01"
                               class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                               @disabled($isLocked)
                               placeholder="Contoh: 10">
                        <p class="mt-1 text-xs text-gray-500">
                            Bobot ini dipakai langsung dalam penilaian untuk jadwal aktif.
                        </p>
                        @error('skor_maks') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Persyaratan Bukti</label>
                        <div class="mt-2 space-y-2">
                            <div class="flex items-center">
                                <input id="butuh_link" wire:model="butuh_link" type="checkbox" class="h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500" @disabled($isLocked)>
                                <label for="butuh_link" class="ml-2 block text-sm text-gray-900">Link Dokumen</label>
                            </div>
                            <div class="flex items-center">
                                <input id="butuh_upload" wire:model="butuh_upload" type="checkbox" class="h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500" @disabled($isLocked)>
                                <label for="butuh_upload" class="ml-2 block text-sm text-gray-900">Upload File Dokumen</label>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Tombol Aksi (Dinamis) -->
                <div class="mt-8 flex justify-end space-x-4">
                    <a href="{{ route('admin.kuesioner.pertanyaan.index', ['kategori' => $kategori->id]) }}" wire:navigate
                        class="px-6 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                        Batal
                    </a>
                    <button type="submit"
                            @disabled($isLocked)
                            class="px-6 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-gray-400">
                        <span wire:loading.remove>{{ $isLocked ? 'Terkunci' : ($isEditMode ? 'Simpan Perubahan' : 'Simpan') }}</span>
                        <span wire:loading>Menyimpan...</span>
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>
