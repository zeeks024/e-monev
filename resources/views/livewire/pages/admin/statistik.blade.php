<?php

use App\Models\Jadwal;
use App\Services\StatistikService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;

new #[Layout('components.layouts.admin')] class extends Component
{
    public ?int $jadwalId = null;

    public function mount(): void
    {
        $this->jadwalId = Jadwal::query()->latest('tanggal_mulai')->value('id');
    }

    public function with(): array
    {
        $jadwals = Jadwal::query()->orderByDesc('tahun')->orderByDesc('tanggal_mulai')->get();

        return [
            'jadwals' => $jadwals,
        ];
    }

    #[Computed]
    public function perCategoryScores(): \Illuminate\Support\Collection
    {
        if (! $this->jadwalId) {
            return collect();
        }

        return app(StatistikService::class)->getPerCategoryScores($this->jadwalId);
    }

    #[Computed]
    public function overallDistribution(): \Illuminate\Support\Collection
    {
        if (! $this->jadwalId) {
            return collect();
        }

        return app(StatistikService::class)->getOverallDistribution($this->jadwalId);
    }

    #[Computed]
    public function topBadanPublik(): \Illuminate\Support\Collection
    {
        if (! $this->jadwalId) {
            return collect();
        }

        return app(StatistikService::class)->getTopBadanPublik($this->jadwalId);
    }

    #[Computed]
    public function bottomBadanPublik(): \Illuminate\Support\Collection
    {
        if (! $this->jadwalId) {
            return collect();
        }

        return app(StatistikService::class)->getBottomBadanPublik($this->jadwalId);
    }

    #[Computed]
    public function perQuestionStatistics(): \Illuminate\Support\Collection
    {
        if (! $this->jadwalId) {
            return collect();
        }

        return app(StatistikService::class)->getPerQuestionStatistics($this->jadwalId);
    }

    #[Computed]
    public function yearOverYearTrends(): \Illuminate\Support\Collection
    {
        return app(StatistikService::class)->getYearOverYearTrends();
    }

    #[Computed]
    public function verificationProgress(): array
    {
        if (! $this->jadwalId) {
            return [
                'total_submissions' => 0,
                'verified_submissions' => 0,
                'unverified_submissions' => 0,
                'verification_percentage' => 0.0,
            ];
        }

        return app(StatistikService::class)->getVerificationProgress($this->jadwalId);
    }

    public function updatedJadwalId(): void
    {
        // Invalidate all computed properties when jadwal changes
        unset($this->perCategoryScores);
        unset($this->overallDistribution);
        unset($this->topBadanPublik);
        unset($this->bottomBadanPublik);
        unset($this->perQuestionStatistics);
        unset($this->verificationProgress);
    }
}; ?>

<div>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h1 class="text-3xl font-bold text-gray-900">Statistik</h1>
            <div class="flex items-center space-x-3">
                <label for="jadwal-select" class="text-sm font-medium text-gray-600">Periode Jadwal:</label>
                <select
                    id="jadwal-select"
                    wire:model.live="jadwalId"
                    class="border border-gray-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                >
                    <option value="">-- Pilih Jadwal --</option>
                    @foreach($jadwals as $jadwal)
                        <option value="{{ $jadwal->id }}">{{ $jadwal->nama }} ({{ $jadwal->tahun }})</option>
                    @endforeach
                </select>
            </div>
        </div>
    </x-slot>

    <main class="p-8 space-y-8">
        @if(!$jadwalId)
            <div class="bg-white p-12 rounded-lg shadow-md text-center">
                <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                <p class="text-gray-500 text-lg">Pilih periode jadwal untuk melihat statistik.</p>
            </div>
        @else
            {{-- Verification Progress --}}
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="bg-white p-6 rounded-lg shadow-md flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Total Submissions</p>
                        <p class="text-3xl font-bold text-gray-900">{{ $this->verificationProgress['total_submissions'] }}</p>
                    </div>
                    <div class="bg-blue-100 p-3 rounded-full">
                        <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                    </div>
                </div>
                <div class="bg-white p-6 rounded-lg shadow-md flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Terverifikasi</p>
                        <p class="text-3xl font-bold text-green-600">{{ $this->verificationProgress['verified_submissions'] }}</p>
                    </div>
                    <div class="bg-green-100 p-3 rounded-full">
                        <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                </div>
                <div class="bg-white p-6 rounded-lg shadow-md flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Belum Diverifikasi</p>
                        <p class="text-3xl font-bold text-yellow-600">{{ $this->verificationProgress['unverified_submissions'] }}</p>
                    </div>
                    <div class="bg-yellow-100 p-3 rounded-full">
                        <svg class="w-6 h-6 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                </div>
                <div class="bg-white p-6 rounded-lg shadow-md flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Progres Verifikasi</p>
                        <p class="text-3xl font-bold text-blue-600">{{ $this->verificationProgress['verification_percentage'] }}%</p>
                    </div>
                    <div class="bg-indigo-100 p-3 rounded-full">
                        <svg class="w-6 h-6 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                    </div>
                </div>
            </div>

            {{-- Placeholder: Chart sections (Task 10 will add ApexCharts) --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Per Category Scores --}}
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Rata-rata Nilai per Kategori</h2>
                    {{-- Chart placeholder --}}
                    <div id="chart-per-category" class="min-h-[300px] flex items-center justify-center bg-gray-50 rounded-lg border-2 border-dashed border-gray-200">
                        <p class="text-gray-400 text-sm">Chart akan ditampilkan di sini</p>
                    </div>
                    {{-- Data table --}}
                    <div class="mt-4 overflow-x-auto">
                        <table class="w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Kategori</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Rata-rata</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Skor Maks</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">%</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @forelse($this->perCategoryScores as $score)
                                    <tr>
                                        <td class="px-4 py-2 text-sm text-gray-900">{{ $score['kategori_nama'] }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ number_format($score['average_score'], 2) }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ $score['max_score'] }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ $score['max_score'] > 0 ? number_format(($score['average_score'] / $score['max_score']) * 100, 1) : 0 }}%</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-4 py-2 text-center text-sm text-gray-500">Belum ada data.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Overall Distribution --}}
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Distribusi Klasifikasi Penilaian</h2>
                    {{-- Chart placeholder --}}
                    <div id="chart-distribution" class="min-h-[300px] flex items-center justify-center bg-gray-50 rounded-lg border-2 border-dashed border-gray-200">
                        <p class="text-gray-400 text-sm">Chart akan ditampilkan di sini</p>
                    </div>
                    {{-- Data table --}}
                    <div class="mt-4 overflow-x-auto">
                        <table class="w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Klasifikasi</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Rentang Nilai</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Jumlah</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @forelse($this->overallDistribution as $item)
                                    <tr>
                                        <td class="px-4 py-2 text-sm text-gray-900">{{ $item['nama'] }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-700">{{ number_format($item['min_nilai'], 0) }} - {{ number_format($item['max_nilai'], 0) }}</td>
                                        <td class="px-4 py-2 text-sm font-semibold text-gray-800">{{ $item['count'] }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="px-4 py-2 text-center text-sm text-gray-500">Belum ada data.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Top & Bottom Badan Publik --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Top 10 --}}
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Top 10 Badan Publik</h2>
                    {{-- Chart placeholder --}}
                    <div id="chart-top-bp" class="min-h-[250px] flex items-center justify-center bg-gray-50 rounded-lg border-2 border-dashed border-gray-200">
                        <p class="text-gray-400 text-sm">Chart akan ditampilkan di sini</p>
                    </div>
                    <div class="mt-4 overflow-x-auto">
                        <table class="w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Badan Publik</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Nilai Akhir</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Klasifikasi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @forelse($this->topBadanPublik as $item)
                                    <tr>
                                        <td class="px-4 py-2 text-sm text-gray-500">{{ $item['rank'] }}</td>
                                        <td class="px-4 py-2 text-sm font-medium text-gray-900">{{ $item['nama_badan_publik'] }}</td>
                                        <td class="px-4 py-2 text-sm font-semibold text-green-600">{{ number_format($item['nilai_akhir'], 2) }}</td>
                                        <td class="px-4 py-2 text-sm">
                                            <span class="px-2 py-0.5 inline-flex text-xs font-semibold rounded-full bg-blue-100 text-blue-800">{{ $item['klasifikasi'] ?? '-' }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-4 py-2 text-center text-sm text-gray-500">Belum ada data.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Bottom 10 --}}
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">10 Badan Publik Terbawah</h2>
                    {{-- Chart placeholder --}}
                    <div id="chart-bottom-bp" class="min-h-[250px] flex items-center justify-center bg-gray-50 rounded-lg border-2 border-dashed border-gray-200">
                        <p class="text-gray-400 text-sm">Chart akan ditampilkan di sini</p>
                    </div>
                    <div class="mt-4 overflow-x-auto">
                        <table class="w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Badan Publik</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Nilai Akhir</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Klasifikasi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @forelse($this->bottomBadanPublik as $item)
                                    <tr>
                                        <td class="px-4 py-2 text-sm text-gray-500">{{ $item['rank'] }}</td>
                                        <td class="px-4 py-2 text-sm font-medium text-gray-900">{{ $item['nama_badan_publik'] }}</td>
                                        <td class="px-4 py-2 text-sm font-semibold text-red-600">{{ number_format($item['nilai_akhir'], 2) }}</td>
                                        <td class="px-4 py-2 text-sm">
                                            <span class="px-2 py-0.5 inline-flex text-xs font-semibold rounded-full bg-blue-100 text-blue-800">{{ $item['klasifikasi'] ?? '-' }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-4 py-2 text-center text-sm text-gray-500">Belum ada data.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Per Question Statistics --}}
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Statistik per Pertanyaan</h2>
                {{-- Placeholder: Filter section (Task 11 will add filters) --}}
                <div class="mb-4 p-3 bg-gray-50 rounded-lg border-2 border-dashed border-gray-200">
                    <p class="text-gray-400 text-sm">Filter akan ditambahkan di sini</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">No</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Pertanyaan</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Skor Maks</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">% Ya</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Pass Rate</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($this->perQuestionStatistics as $index => $q)
                                <tr>
                                    <td class="px-4 py-2 text-sm text-gray-500">{{ $index + 1 }}</td>
                                    <td class="px-4 py-2 text-sm text-gray-900 max-w-md truncate">{{ $q['teks_pertanyaan'] }}</td>
                                    <td class="px-4 py-2 text-sm text-gray-700">{{ $q['skor_maks'] }}</td>
                                    <td class="px-4 py-2 text-sm text-gray-700">{{ $q['ya_percentage'] }}%</td>
                                    <td class="px-4 py-2 text-sm text-gray-700">{{ $q['pass_rate'] }}%</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-2 text-center text-sm text-gray-500">Belum ada data.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Year-over-Year Trends --}}
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Tren Tahunan</h2>
                {{-- Chart placeholder --}}
                <div id="chart-yoy-trends" class="min-h-[300px] flex items-center justify-center bg-gray-50 rounded-lg border-2 border-dashed border-gray-200">
                    <p class="text-gray-400 text-sm">Chart akan ditampilkan di sini</p>
                </div>
                <div class="mt-4 overflow-x-auto">
                    <table class="w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Jadwal</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tahun</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Rata-rata Nilai</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Jumlah Peserta</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($this->yearOverYearTrends as $trend)
                                <tr>
                                    <td class="px-4 py-2 text-sm text-gray-900">{{ $trend['nama'] }}</td>
                                    <td class="px-4 py-2 text-sm text-gray-700">{{ $trend['tahun'] }}</td>
                                    <td class="px-4 py-2 text-sm font-semibold text-gray-800">{{ number_format($trend['average_score'], 2) }}</td>
                                    <td class="px-4 py-2 text-sm text-gray-700">{{ $trend['total_participants'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-2 text-center text-sm text-gray-500">Belum ada data.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </main>
</div>
