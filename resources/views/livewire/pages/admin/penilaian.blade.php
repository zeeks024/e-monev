<?php

use App\Models\HasilPenilaian;
use App\Models\Submission;
use App\Services\PenilaianService;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.admin')] class extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function with(): array
    {
        $query = Submission::query()
            ->selectRaw('MAX(id) as id, user_id, jadwal_id, MAX(tanggal_submit) as tanggal_submit')
            ->whereNotNull('jadwal_id')
            ->with(['user.badanPublik', 'jadwal'])
            ->groupBy('user_id', 'jadwal_id')
            ->orderByDesc('tanggal_submit');

        if ($this->search !== '') {
            $search = '%' . trim($this->search) . '%';

            $query->where(function ($q) use ($search) {
                $q->whereHas('user.badanPublik', function ($bpQuery) use ($search) {
                    $bpQuery->where('nama_badan_publik', 'like', $search)
                        ->orWhere('email_badan_publik', 'like', $search);
                })->orWhereHas('user', function ($userQuery) use ($search) {
                    $userQuery->where('name', 'like', $search)
                        ->orWhere('email', 'like', $search);
                })->orWhereHas('jadwal', function ($jadwalQuery) use ($search) {
                    $jadwalQuery->where('nama', 'like', $search)
                        ->orWhere('tahun', 'like', $search);
                });
            });
        }

        $groups = $query->paginate(10);
        $service = app(PenilaianService::class);

        $rows = $groups->getCollection()->map(function (Submission $group) use ($service) {
            $kategoriAktif = $service->getKategoriAktifByJadwal((int) $group->jadwal_id);
            $nilaiKategori = $service->getNilaiKategoriMap((int) $group->user_id, (int) $group->jadwal_id);
            $hasil = HasilPenilaian::query()
                ->with('klasifikasiPenilaian')
                ->where('user_id', $group->user_id)
                ->where('jadwal_id', $group->jadwal_id)
                ->first();

            return [
                'user' => $group->user,
                'jadwal' => $group->jadwal,
                'user_id' => (int) $group->user_id,
                'jadwal_id' => (int) $group->jadwal_id,
                'tanggal_submit' => $group->tanggal_submit,
                'total_kategori' => $kategoriAktif->count(),
                'dinilai_kategori' => $nilaiKategori->whereNotNull('nilai')->count(),
                'status_verifikasi' => $hasil?->status_verifikasi ?? 'Menunggu',
                'klasifikasi' => $hasil?->klasifikasiPenilaian?->nama,
                'nilai_akhir' => $hasil?->nilai_akhir,
            ];
        });

        $groups->setCollection($rows);

        return ['groups' => $groups];
    }
}; ?>

<div>
    <x-slot name="header">
        <div class="flex items-center space-x-8">
            <h1 class="text-3xl font-bold text-gray-900">Penilaian</h1>
        </div>
    </x-slot>

    <main class="p-8">
        <div class="bg-white p-6 rounded-lg shadow-md">
            <h2 class="text-xl font-semibold text-gray-800 mb-6">List Verifikasi Nilai Dinas</h2>

            <div class="mb-6 grid gap-4 md:grid-cols-[minmax(0,1fr)_260px]">
                <div class="relative">
                    <input
                        wire:model.live.debounce.300ms="search"
                        type="text"
                        placeholder="Cari badan publik, user, email, atau jadwal..."
                        class="w-full rounded-lg border border-gray-300 py-2.5 pl-10 pr-4 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                    >
                    <svg class="absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                <div class="text-sm text-gray-500 md:text-right">
                    Pencarian memfilter daftar verifikasi nilai pada tabel ini.
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">No</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">PPID Pelaksana</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jadwal</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kategori Dinilai</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nilai Akhir</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Klasifikasi</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($groups as $group)
                            <tr>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $loop->iteration + $groups->firstItem() - 1 }}</td>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $group['user']->badanPublik->nama_badan_publik ?? $group['user']->name }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    {{ $group['jadwal']->nama ?? '-' }}
                                    @if($group['jadwal'])
                                        <span class="text-xs text-gray-400">({{ $group['jadwal']->tahun }})</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700">{{ $group['dinilai_kategori'] }}/{{ $group['total_kategori'] }}</td>
                                <td class="px-6 py-4 text-sm font-semibold text-gray-800">{{ $group['nilai_akhir'] !== null ? number_format($group['nilai_akhir'], 2) : '-' }}</td>
                                <td class="px-6 py-4 text-sm">
                                    <span class="px-3 py-1 inline-flex text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                        {{ $group['klasifikasi'] ?? 'Belum terklasifikasi' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-3 py-1 inline-flex text-xs font-semibold rounded-full {{ $group['status_verifikasi'] === 'Terverifikasi' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                        {{ $group['status_verifikasi'] }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm font-medium">
                                    <a href="{{ route('admin.penilaian.verifikasi', ['user' => $group['user_id'], 'jadwal' => $group['jadwal_id']]) }}" wire:navigate class="px-4 py-2 bg-gray-800 rounded-md font-semibold text-xs text-white uppercase hover:bg-gray-700">
                                        Verifikasi
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-4 text-center text-sm text-gray-500">
                                    Tidak ada data untuk ditampilkan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-6">
                {{ $groups->links() }}
            </div>
        </div>
    </main>
</div>
