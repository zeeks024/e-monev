<?php

use App\Models\Jadwal;
use App\Services\StatistikService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;

new #[Layout('components.layouts.admin')] class extends Component
{
    public ?int $jadwalId = null;
    public ?int $selectedJadwalId = null;

    public function mount(): void
    {
        $this->jadwalId = Jadwal::query()
            ->whereHas('hasilPenilaians', fn($q) => $q->whereNotNull('nilai_akhir'))
            ->latest('tanggal_mulai')
            ->value('id')
            ?? Jadwal::query()->latest('tanggal_mulai')->value('id');
        $this->selectedJadwalId = $this->jadwalId;
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

    public function gantiPeriode(): void
    {
        $this->jadwalId = $this->selectedJadwalId;
        unset($this->perCategoryScores);
        unset($this->overallDistribution);
        unset($this->topBadanPublik);
        unset($this->bottomBadanPublik);
        unset($this->perQuestionStatistics);
        unset($this->verificationProgress);
        $this->dispatch('charts-update');
    }
}; ?>

<div>
    <x-slot name="header">
        <h1 class="text-3xl font-bold text-gray-900">Statistik</h1>
    </x-slot>

    <main class="p-8 space-y-8">
        <div class="flex items-center gap-3">
            <select
                wire:model="selectedJadwalId"
                class="border border-gray-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            >
                @foreach($jadwals as $jadwal)
                    <option value="{{ $jadwal->id }}">{{ $jadwal->nama }} ({{ $jadwal->tahun }})</option>
                @endforeach
            </select>
            <button
                wire:click="gantiPeriode"
                class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors"
            >
                Ganti
            </button>
        </div>

        @if(!$jadwalId)
            <div class="bg-white p-12 rounded-lg shadow-md text-center">
                <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                <p class="text-gray-500 text-lg">Pilih periode jadwal untuk melihat statistik.</p>
            </div>
        @else
            {{-- Verification Progress --}}
            <div wire:loading>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    @for($i = 0; $i < 4; $i++)
                        <div class="bg-white p-6 rounded-lg shadow-md animate-pulse">
                            <div class="h-4 bg-gray-200 rounded w-24 mb-3"></div>
                            <div class="h-8 bg-gray-200 rounded w-16"></div>
                        </div>
                    @endfor
                </div>
            </div>
            <div wire:loading.remove>
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
            </div>

            {{-- Charts: Per Category & Distribution --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Per Category Scores --}}
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Rata-rata Nilai per Kategori</h2>
                    <div id="chart-per-category" class="min-h-[300px]"></div>
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
                    <div id="chart-distribution" class="min-h-[300px]"></div>
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
                    <div wire:loading>
                        <div class="min-h-[250px] flex items-center justify-center">
                            <svg class="animate-spin h-8 w-8 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        </div>
                    </div>
                    <div wire:loading.remove>
                        <div id="chart-top-bp" class="min-h-[250px]"></div>
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
                </div>

                {{-- Bottom 10 --}}
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">10 Badan Publik Terbawah</h2>
                    <div wire:loading>
                        <div class="min-h-[250px] flex items-center justify-center">
                            <svg class="animate-spin h-8 w-8 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        </div>
                    </div>
                    <div wire:loading.remove>
                        <div id="chart-bottom-bp" class="min-h-[250px]"></div>
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
            </div>

            {{-- Per Question Statistics --}}
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Statistik per Pertanyaan</h2>
                <div id="chart-per-question" class="min-h-[300px] mb-6"></div>
                <div wire:loading>
                    <div class="flex items-center justify-center py-8">
                        <svg class="animate-spin h-8 w-8 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    </div>
                </div>
                <div wire:loading.remove>
                    <div class="overflow-x-auto">
                        <table class="w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">No</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Pertanyaan</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Skor Maks</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">% Ya</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">% Tervalidasi</th>
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
            </div>

            {{-- Year-over-Year Trends --}}
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Tren Tahunan</h2>
                <div id="chart-yoy-trends" class="min-h-[300px]"></div>
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

@script
<script>
    const brandBlue = '#438AFF';
    const brandSecondary = '#3B82F6';
    const colorSuccess = '#10B981';
    const colorWarning = '#F59E0B';
    const colorDanger = '#EF4444';
    const colorPurple = '#8B5CF6';
    const colorCyan = '#06B6D4';
    const colorGray = '#9CA3AF';

    const chartColors = [brandBlue, brandSecondary, colorSuccess, colorWarning, colorDanger, colorPurple, colorCyan];

    function destroyExistingCharts() {
        if (window.statistikCharts) {
            window.statistikCharts.forEach(chart => {
                if (chart && typeof chart.destroy === 'function') {
                    chart.destroy();
                }
            });
        }
        window.statistikCharts = [];
        // Clear chart containers so old SVGs don't stack
        ['chart-per-category', 'chart-distribution', 'chart-top-bp', 'chart-bottom-bp', 'chart-per-question', 'chart-yoy-trends'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.innerHTML = '';
        });
    }

    function initCharts() {
        destroyExistingCharts();

        const perCategoryScores = @js($this->perCategoryScores);
        const overallDistribution = @js($this->overallDistribution);
        const topBadanPublik = @js($this->topBadanPublik);
        const bottomBadanPublik = @js($this->bottomBadanPublik);
        const perQuestionStatistics = @js($this->perQuestionStatistics);
        const yearOverYearTrends = @js($this->yearOverYearTrends);

        // 1. Per Category Scores — Grouped Bar Chart
        if (perCategoryScores.length > 0) {
            const categories = perCategoryScores.map(s => s.kategori_nama);
            const avgScores = perCategoryScores.map(s => parseFloat(s.average_score.toFixed(2)));
            const maxScores = perCategoryScores.map(s => s.max_score);

            const chartPerCategory = new ApexCharts(document.querySelector('#chart-per-category'), {
                series: [
                    { name: 'Rata-rata Nilai', data: avgScores },
                    { name: 'Skor Maksimum', data: maxScores }
                ],
                chart: {
                    type: 'bar',
                    height: 300,
                    fontFamily: 'Poppins, sans-serif',
                    toolbar: { show: false }
                },
                plotOptions: {
                    bar: {
                        horizontal: false,
                        columnWidth: '55%',
                        borderRadius: 4
                    }
                },
                dataLabels: { enabled: false },
                stroke: { show: true, width: 2, colors: ['transparent'] },
                xaxis: {
                    categories: categories,
                    labels: { style: { fontFamily: 'Poppins, sans-serif', fontSize: '12px' } }
                },
                yaxis: {
                    labels: { style: { fontFamily: 'Poppins, sans-serif', fontSize: '12px' } }
                },
                colors: [brandBlue, brandSecondary],
                fill: { opacity: 1 },
                legend: {
                    position: 'top',
                    fontFamily: 'Poppins, sans-serif',
                    markers: { radius: 4 }
                },
                tooltip: {
                    y: {
                        formatter: function (val) {
                            return val;
                        }
                    }
                }
            });
            chartPerCategory.render();
            window.statistikCharts.push(chartPerCategory);
        } else {
            document.querySelector('#chart-per-category').innerHTML = '<p class="text-gray-400 text-sm text-center py-12">Belum ada data.</p>';
        }

        // 2. Klasifikasi Distribution — Donut Chart
        if (overallDistribution.length > 0) {
            const labels = overallDistribution.map(d => d.nama);
            const series = overallDistribution.map(d => d.count);

            const chartDistribution = new ApexCharts(document.querySelector('#chart-distribution'), {
                series: series,
                chart: {
                    type: 'donut',
                    height: 300,
                    fontFamily: 'Poppins, sans-serif'
                },
                labels: labels,
                colors: chartColors.slice(0, labels.length),
                plotOptions: {
                    pie: {
                        donut: {
                            size: '65%',
                            labels: {
                                show: true,
                                name: { show: true, fontFamily: 'Poppins, sans-serif' },
                                value: { show: true, fontFamily: 'Poppins, sans-serif' },
                                total: {
                                    show: true,
                                    label: 'Total',
                                    fontFamily: 'Poppins, sans-serif',
                                    formatter: function (w) {
                                        return w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                                    }
                                }
                            }
                        }
                    }
                },
                legend: {
                    position: 'bottom',
                    fontFamily: 'Poppins, sans-serif'
                },
                dataLabels: { enabled: false },
                tooltip: {
                    y: {
                        formatter: function (val) {
                            return val + ' entitas';
                        }
                    }
                }
            });
            chartDistribution.render();
            window.statistikCharts.push(chartDistribution);
        } else {
            document.querySelector('#chart-distribution').innerHTML = '<p class="text-gray-400 text-sm text-center py-12">Belum ada data.</p>';
        }

        // 3. Top 10 Badan Publik — Horizontal Bar Chart
        if (topBadanPublik.length > 0) {
            const topLabels = topBadanPublik.map(b => b.nama_badan_publik).reverse();
            const topData = topBadanPublik.map(b => parseFloat(b.nilai_akhir.toFixed(2))).reverse();

            const chartTop = new ApexCharts(document.querySelector('#chart-top-bp'), {
                series: [{ name: 'Nilai Akhir', data: topData }],
                chart: {
                    type: 'bar',
                    height: 250,
                    fontFamily: 'Poppins, sans-serif',
                    toolbar: { show: false }
                },
                plotOptions: {
                    bar: {
                        horizontal: true,
                        borderRadius: 4,
                        barHeight: '60%'
                    }
                },
                dataLabels: { enabled: false },
                xaxis: {
                    categories: topLabels,
                    labels: { style: { fontFamily: 'Poppins, sans-serif', fontSize: '11px' } }
                },
                yaxis: {
                    labels: { style: { fontFamily: 'Poppins, sans-serif', fontSize: '11px' } }
                },
                colors: [colorSuccess],
                tooltip: {
                    y: {
                        formatter: function (val) {
                            return val;
                        }
                    }
                }
            });
            chartTop.render();
            window.statistikCharts.push(chartTop);
        } else {
            document.querySelector('#chart-top-bp').innerHTML = '<p class="text-gray-400 text-sm text-center py-12">Belum ada data.</p>';
        }

        // 4. Bottom 10 Badan Publik — Horizontal Bar Chart
        if (bottomBadanPublik.length > 0) {
            const bottomLabels = bottomBadanPublik.map(b => b.nama_badan_publik).reverse();
            const bottomData = bottomBadanPublik.map(b => parseFloat(b.nilai_akhir.toFixed(2))).reverse();

            const chartBottom = new ApexCharts(document.querySelector('#chart-bottom-bp'), {
                series: [{ name: 'Nilai Akhir', data: bottomData }],
                chart: {
                    type: 'bar',
                    height: 250,
                    fontFamily: 'Poppins, sans-serif',
                    toolbar: { show: false }
                },
                plotOptions: {
                    bar: {
                        horizontal: true,
                        borderRadius: 4,
                        barHeight: '60%'
                    }
                },
                dataLabels: { enabled: false },
                xaxis: {
                    categories: bottomLabels,
                    labels: { style: { fontFamily: 'Poppins, sans-serif', fontSize: '11px' } }
                },
                yaxis: {
                    labels: { style: { fontFamily: 'Poppins, sans-serif', fontSize: '11px' } }
                },
                colors: [colorDanger],
                tooltip: {
                    y: {
                        formatter: function (val) {
                            return val;
                        }
                    }
                }
            });
            chartBottom.render();
            window.statistikCharts.push(chartBottom);
        } else {
            document.querySelector('#chart-bottom-bp').innerHTML = '<p class="text-gray-400 text-sm text-center py-12">Belum ada data.</p>';
        }

        // 5. Per Question Statistics — Bar Chart (Pass Rate)
        if (perQuestionStatistics.length > 0) {
            const qLabels = perQuestionStatistics.map((q, i) => 'Q' + (i + 1));
            const qPassRates = perQuestionStatistics.map(q => parseFloat(q.pass_rate));
            const qYaPercentages = perQuestionStatistics.map(q => parseFloat(q.ya_percentage));

            const chartPerQuestion = new ApexCharts(document.querySelector('#chart-per-question'), {
                series: [
                    { name: 'Pass Rate (%)', data: qPassRates },
                    { name: '% Ya', data: qYaPercentages }
                ],
                chart: {
                    type: 'bar',
                    height: 300,
                    fontFamily: 'Poppins, sans-serif',
                    toolbar: { show: false }
                },
                plotOptions: {
                    bar: {
                        horizontal: false,
                        columnWidth: '55%',
                        borderRadius: 4
                    }
                },
                dataLabels: { enabled: false },
                xaxis: {
                    categories: qLabels,
                    labels: { style: { fontFamily: 'Poppins, sans-serif', fontSize: '11px' } },
                    title: { text: 'Pertanyaan', style: { fontFamily: 'Poppins, sans-serif' } }
                },
                yaxis: {
                    max: 100,
                    labels: { style: { fontFamily: 'Poppins, sans-serif', fontSize: '12px' } },
                    title: { text: 'Persentase (%)', style: { fontFamily: 'Poppins, sans-serif' } }
                },
                colors: [brandBlue, colorCyan],
                legend: {
                    position: 'top',
                    fontFamily: 'Poppins, sans-serif'
                },
                tooltip: {
                    x: {
                        formatter: function (val, opts) {
                            const idx = opts.dataPointIndex;
                            const text = perQuestionStatistics[idx]?.teks_pertanyaan ?? val;
                            return text.length > 60 ? text.substring(0, 60) + '...' : text;
                        }
                    },
                    y: {
                        formatter: function (val) {
                            return val + '%';
                        }
                    }
                }
            });
            chartPerQuestion.render();
            window.statistikCharts.push(chartPerQuestion);
        } else {
            document.querySelector('#chart-per-question').innerHTML = '<p class="text-gray-400 text-sm text-center py-12">Belum ada data.</p>';
        }

        // 6. Year-over-Year Trends — Line Chart
        if (yearOverYearTrends.length > 0) {
            const trendLabels = yearOverYearTrends.map(t => t.nama + ' (' + t.tahun + ')');
            const trendScores = yearOverYearTrends.map(t => parseFloat(t.average_score.toFixed(2)));
            const trendParticipants = yearOverYearTrends.map(t => t.total_participants);

            const chartYoY = new ApexCharts(document.querySelector('#chart-yoy-trends'), {
                series: [
                    {
                        name: 'Rata-rata Nilai',
                        type: 'line',
                        data: trendScores
                    },
                    {
                        name: 'Jumlah Peserta',
                        type: 'column',
                        data: trendParticipants
                    }
                ],
                chart: {
                    height: 300,
                    fontFamily: 'Poppins, sans-serif',
                    toolbar: { show: false },
                    type: 'line'
                },
                stroke: {
                    width: [3, 0],
                    curve: 'smooth'
                },
                plotOptions: {
                    bar: {
                        columnWidth: '40%',
                        borderRadius: 4
                    }
                },
                dataLabels: { enabled: false },
                xaxis: {
                    categories: trendLabels,
                    labels: { style: { fontFamily: 'Poppins, sans-serif', fontSize: '11px' }, rotate: -30 }
                },
                yaxis: [
                    {
                        title: { text: 'Rata-rata Nilai', style: { fontFamily: 'Poppins, sans-serif' } },
                        labels: { style: { fontFamily: 'Poppins, sans-serif', fontSize: '12px' } }
                    },
                    {
                        opposite: true,
                        title: { text: 'Jumlah Peserta', style: { fontFamily: 'Poppins, sans-serif' } },
                        labels: { style: { fontFamily: 'Poppins, sans-serif', fontSize: '12px' } }
                    }
                ],
                colors: [brandBlue, colorPurple],
                legend: {
                    position: 'top',
                    fontFamily: 'Poppins, sans-serif'
                },
                tooltip: {
                    shared: true,
                    intersect: false,
                    y: {
                        formatter: function (y, opts) {
                            if (typeof y !== 'undefined') {
                                return opts.seriesIndex === 0 ? y.toFixed(2) : y + ' peserta';
                            }
                            return y;
                        }
                    }
                }
            });
            chartYoY.render();
            window.statistikCharts.push(chartYoY);
        } else {
            document.querySelector('#chart-yoy-trends').innerHTML = '<p class="text-gray-400 text-sm text-center py-12">Belum ada data.</p>';
        }
    }

    initCharts();

    document.addEventListener('livewire:initialized', () => {
        Livewire.hook('morph.updated', ({ component }) => {
            if (component && component.name === 'pages.admin.statistik') {
                setTimeout(() => initCharts(), 50);
            }
        });
    });
</script>
@endscript