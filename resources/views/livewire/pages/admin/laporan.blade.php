<?php

use App\Models\BadanPublik;
use App\Models\HasilPenilaian;
use App\Models\Jadwal;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.admin')] class extends Component
{
    use WithPagination;

    public $jadwalId = 'semua';
    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedJadwalId(): void
    {
        $this->resetPage();
    }

    public function with(): array
    {
        $jadwals = Jadwal::query()->orderBy('tahun', 'desc')->get();

        $query = BadanPublik::query()
            ->with(['user' => function ($q) {
                $q->with(['hasilPenilaians' => function ($hq) {
                    $hq->where('status_verifikasi', 'Terverifikasi')
                        ->with('jadwal', 'klasifikasiPenilaian');
                }]);
            }])
            ->whereHas('user.hasilPenilaians', function ($q) {
                $q->where('status_verifikasi', 'Terverifikasi');

                if ($this->jadwalId && $this->jadwalId !== 'semua') {
                    $q->where('jadwal_id', $this->jadwalId);
                }
            });

        if (!empty($this->search)) {
            $search = '%' . trim($this->search) . '%';

            $query->where(function ($q) use ($search) {
                $q->where('nama_badan_publik', 'like', $search)
                    ->orWhere('email_badan_publik', 'like', $search)
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', $search)
                            ->orWhere('email', 'like', $search);
                    });
            });
        }

        $badanPubliks = $query->orderBy('nama_badan_publik')->paginate(10);

        return [
            'badanPubliks' => $badanPubliks,
            'jadwals' => $jadwals,
        ];
    }

    public function getHasilPenilaian($userId, $jadwalId)
    {
        $query = HasilPenilaian::query()
            ->with('jadwal', 'klasifikasiPenilaian')
            ->where('user_id', $userId)
            ->where('status_verifikasi', 'Terverifikasi');

        if ($this->jadwalId && $this->jadwalId !== 'semua') {
            $query->where('jadwal_id', $this->jadwalId);
        }

        return $query->first();
    }
}; ?>

<div>
    <x-slot name="header">
        <div class="flex items-center space-x-8">
            <h1 class="text-3xl font-bold text-gray-900">Laporan</h1>
        </div>
    </x-slot>

    <main class="p-8">
        <div class="bg-white p-6 rounded-lg shadow-md">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-semibold text-gray-800">Laporan Hasil E-Monev KIP</h2>
            </div>

            <div class="mb-6 grid gap-4 md:grid-cols-[minmax(0,1fr)_260px]">
                <div class="relative">
                    <input
                        wire:model.live.debounce.300ms="search"
                        type="text"
                        placeholder="Cari badan publik, nama user, atau email..."
                        class="w-full rounded-lg border border-gray-300 py-2.5 pl-10 pr-4 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                    >
                    <svg class="absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                <div class="text-sm text-gray-500 md:text-right">
                    Pencarian memfilter data laporan yang tampil di tabel.
                </div>
            </div>

            <div class="mb-6 border-b border-gray-200">
                <nav class="-mb-px flex space-x-6 overflow-x-auto" aria-label="Tabs">
                    <button wire:click="$set('jadwalId', 'semua')" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm {{ $jadwalId === 'semua' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">Semua Jadwal</button>
                    @foreach ($jadwals as $jadwal)
                        <button wire:click="$set('jadwalId', {{ $jadwal->id }})" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm {{ $jadwalId == $jadwal->id ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                            {{ $jadwal->nama }} ({{ $jadwal->tahun }})
                        </button>
                    @endforeach
                </nav>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">No</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Badan Publik</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jadwal</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nilai Akhir</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Klasifikasi</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($badanPubliks as $badanPublik)
                            @php
                                $hasil = $this->getHasilPenilaian($badanPublik->user_id, $jadwalId);
                            @endphp
                            @if ($hasil)
                                <tr>
                                    <td class="px-6 py-4 text-sm">{{ $loop->iteration + $badanPubliks->firstItem() - 1 }}</td>
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $badanPublik->nama_badan_publik ?? 'N/A' }}</td>
                                    <td class="px-6 py-4 text-sm">{{ $hasil->jadwal->nama ?? '-' }}</td>
                                    <td class="px-6 py-4 text-sm font-bold text-gray-800">{{ number_format($hasil->nilai_akhir, 2) }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-700">{{ $hasil->klasifikasiPenilaian->nama ?? 'Belum terklasifikasi' }}</td>
                                    <td class="px-6 py-4 text-right">
                                        <a href="{{ route('admin.laporan.unduh.per-badan-publik', ['userId' => $badanPublik->user_id, 'jadwalId' => ($jadwalId === 'semua' ? $hasil->jadwal_id : $jadwalId)]) }}" target="_blank" class="inline-flex items-center px-3 py-1.5 bg-blue-600 text-white text-xs font-medium rounded hover:bg-blue-700">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                            </svg>
                                            Unduh
                                        </a>
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">Tidak ada data badan publik yang ditemukan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-6">{{ $badanPubliks->links() }}</div>
        </div>
    </main>
</div>
