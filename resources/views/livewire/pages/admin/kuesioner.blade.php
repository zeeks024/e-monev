<?php

use App\Models\Kategori;
use App\Models\Jadwal;
use App\Models\JadwalPertanyaan;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Carbon\Carbon;

new #[Layout('components.layouts.admin')] class extends Component
{
    public $kategoriList;
    public $activeKategoriId;
    public $activeKategori;
    public $jadwal;

    /**
     * Mount the component and load data.
     */
    public function mount(): void
    {
        $this->loadJadwal();
        $this->loadKategori();
    }

    /**
     * Load the schedule.
     */
    public function loadJadwal(): void
    {
        $this->jadwal = Jadwal::active()->first() ?? Jadwal::latest('tanggal_mulai')->first();
    }

    /**
     * Load all categories and set the first one as active.
     */
    public function loadKategori(): void
    {
        $this->kategoriList = Kategori::orderBy('id')->get();

        if ($this->kategoriList->isNotEmpty()) {
            if (!$this->activeKategoriId || !$this->kategoriList->contains('id', $this->activeKategoriId)) {
                $this->activeKategoriId = $this->kategoriList->first()->id;
            }
        } else {
            $this->activeKategoriId = null;
        }
        $this->updateActiveKategori();
    }

    public function getKategoriStats(int $kategoriId): array
    {
        if (! $this->jadwal) {
            return [
                'jumlah_pertanyaan' => 0,
                'total_skor' => 0,
            ];
        }

        $query = JadwalPertanyaan::query()
            ->where('jadwal_id', $this->jadwal->id)
            ->whereHas('pertanyaanTemplate', function ($q) use ($kategoriId) {
                $q->where('kategori_id', $kategoriId);
            });

        return [
            'jumlah_pertanyaan' => (clone $query)->count(),
            'total_skor' => (float) ((clone $query)->sum('skor_maks') ?? 0),
        ];
    }

    /**
     * Update the active category property.
     */
    public function updateActiveKategori(): void
    {
        $this->activeKategori = Kategori::find($this->activeKategoriId);
    }

    /**
     * Change the active category.
     */
    public function changeKategori($kategoriId): void
    {
        $this->activeKategoriId = $kategoriId;
        $this->updateActiveKategori();
    }

    /**
     * Delete the currently active category.
     */
    public function deleteKategori(): void
    {
        if ($this->activeKategori) {
            $this->activeKategori->delete();
            session()->flash('success', 'Kategori berhasil dihapus.');
            $this->loadKategori();
        }
    }

    public function deleteKategoriById(int $kategoriId): void
    {
        $kategori = Kategori::find($kategoriId);

        if (! $kategori) {
            return;
        }

        $kategori->delete();
        session()->flash('success', 'Kategori berhasil dihapus.');
        $this->loadKategori();
    }
}; ?>

<div>
    <x-slot name="header">
        <div class="flex items-center">
            <h1 class="text-3xl font-bold text-gray-900">Kuesioner</h1>
        </div>
    </x-slot>

    <main class="p-8">
        @if (session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
        @endif

        <div class="mb-6 grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
            <section class="rounded-2xl bg-white p-6 shadow-md">
                <div class="flex items-start justify-between gap-6">
                    <div>
                        <p class="text-sm font-medium text-blue-700">Jadwal Aktif</p>
                        @if($jadwal)
                            <h2 class="mt-2 text-2xl font-bold text-gray-900">{{ $jadwal->nama }}</h2>
                            <p class="mt-2 text-sm text-gray-600">
                                {{ Carbon::parse($jadwal->tanggal_mulai)->timezone('Asia/Jakarta')->isoFormat('D MMMM YYYY, HH:mm') }} WIB
                                sampai
                                {{ Carbon::parse($jadwal->tanggal_selesai)->timezone('Asia/Jakarta')->isoFormat('D MMMM YYYY, HH:mm') }} WIB
                            </p>
                            <p class="mt-3 max-w-2xl text-sm text-gray-500">
                                Perubahan pertanyaan dan bobot pada halaman ini akan mengikuti jadwal aktif. Kelola jadwal terlebih dahulu jika Anda ingin berpindah periode evaluasi.
                            </p>
                        @else
                            <h2 class="mt-2 text-2xl font-bold text-gray-900">Belum ada jadwal</h2>
                            <p class="mt-3 text-sm text-gray-500">
                                Buat jadwal terlebih dahulu agar kategori dan pertanyaan punya konteks periode yang jelas.
                            </p>
                        @endif
                    </div>
                    <a href="{{ route('admin.kuesioner.jadwal') }}" wire:navigate class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                        Kelola Jadwal
                    </a>
                </div>
            </section>

            <section class="rounded-2xl bg-white p-6 shadow-md">
                <p class="text-sm font-medium text-gray-500">Alur Pengelolaan</p>
                <ol class="mt-4 space-y-3 text-sm text-gray-700">
                    <li><span class="font-semibold text-gray-900">1.</span> Pastikan jadwal aktif sudah benar.</li>
                    <li><span class="font-semibold text-gray-900">2.</span> Tambah atau pilih kategori yang ingin dikelola.</li>
                    <li><span class="font-semibold text-gray-900">3.</span> Buka detail pertanyaan untuk mengatur isi, bobot, dan bukti.</li>
                </ol>
            </section>
        </div>

        <div class="rounded-2xl bg-white p-6 shadow-md">
            <div class="flex flex-col gap-4 border-b border-gray-200 pb-5 md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-gray-800">Kategori Kuesioner</h2>
                    <p class="mt-1 text-sm text-gray-500">
                        Pilih kategori untuk melihat ringkasan dan lanjut mengelola pertanyaan pada jadwal aktif.
                    </p>
                </div>
                <a href="{{ route('admin.kuesioner.kategori.create') }}" wire:navigate class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                    Tambah Kategori
                </a>
            </div>

            @if($kategoriList->isNotEmpty())
                <div class="mt-6 grid gap-4 lg:grid-cols-2">
                    @foreach($kategoriList as $kategori)
                        @php($stats = $this->getKategoriStats($kategori->id))
                        <div class="rounded-2xl border p-5 transition {{ $activeKategoriId == $kategori->id ? 'border-blue-500 bg-blue-50 shadow-sm' : 'border-gray-200 bg-white hover:border-gray-300 hover:bg-gray-50' }}">
                            <div class="flex items-start justify-between gap-4">
                                <div class="min-w-0">
                                    <button
                                        type="button"
                                        wire:click="changeKategori({{ $kategori->id }})"
                                        class="inline-flex rounded-full bg-white px-3 py-1 text-xs font-semibold text-blue-700 ring-1 ring-blue-100 transition hover:bg-blue-100"
                                    >
                                        {{ $kategori->nama }}
                                    </button>
                                    <h3 class="mt-3 text-lg font-semibold text-gray-900">{{ $kategori->judul }}</h3>
                                    <p class="mt-4 text-sm leading-7 text-gray-600">
                                        {{ \Illuminate\Support\Str::limit($kategori->deskripsi, 120) }}
                                    </p>
                                </div>
                            </div>

                            <div class="mt-6 grid grid-cols-2 gap-3">
                                <div class="rounded-xl bg-white/80 p-3 ring-1 ring-gray-100">
                                    <p class="text-xs uppercase tracking-wide text-gray-500">Jumlah Pertanyaan</p>
                                    <p class="mt-1 text-lg font-semibold text-gray-900">{{ $stats['jumlah_pertanyaan'] }}</p>
                                </div>
                                <div class="rounded-xl bg-white/80 p-3 ring-1 ring-gray-100">
                                    <p class="text-xs uppercase tracking-wide text-gray-500">Total Skor</p>
                                    <p class="mt-1 text-lg font-semibold text-gray-900">{{ number_format($stats['total_skor'], 2) }}</p>
                                </div>
                            </div>

                            <div class="mt-6 grid gap-2 sm:grid-cols-3">
                                <a
                                    href="{{ route('admin.kuesioner.kategori.edit', ['kategori' => $kategori->id]) }}"
                                    wire:navigate
                                    class="inline-flex w-full items-center justify-center rounded-lg bg-blue-600 px-3 py-2 text-xs font-medium text-white hover:bg-blue-700"
                                >
                                    Edit Kategori
                                </a>
                                <a
                                    href="{{ route('admin.kuesioner.pertanyaan.index', ['kategori' => $kategori->id]) }}"
                                    wire:navigate
                                    class="inline-flex w-full items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50"
                                >
                                    Kelola Pertanyaan
                                </a>
                                <button
                                    type="button"
                                    wire:click="deleteKategoriById({{ $kategori->id }})"
                                    wire:confirm="Apakah Anda yakin ingin menghapus kategori ini?"
                                    class="inline-flex w-full items-center justify-center rounded-lg bg-red-50 px-3 py-2 text-xs font-medium text-red-700 ring-1 ring-red-200 hover:bg-red-100"
                                >
                                    Hapus
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>

            @else
                <div class="py-16 text-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-16 w-16 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                    <h3 class="mt-4 text-lg font-medium text-gray-900">Belum ada kategori yang dibuat</h3>
                    <p class="mt-2 text-sm text-gray-500">Silakan klik "Tambah Kategori" untuk memulai menyusun struktur kuesioner.</p>
                </div>
            @endif
        </div>
    </main>
</div>
