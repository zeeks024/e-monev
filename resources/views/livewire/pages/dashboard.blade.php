<?php

use App\Models\BadanPublik;
use App\Models\HasilPenilaian;
use App\Models\Jadwal;
use App\Services\PenilaianService;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component
{
    public ?BadanPublik $badanPublik;
    public $jadwalAcuan = null;
    public $hasilPenilaian = null;
    public array $nilaiKategori = [];

    public function mount(): void
    {
        $user = Auth::user()->load('badanPublik');
        $this->badanPublik = $user->badanPublik;

        $this->jadwalAcuan = Jadwal::active()->first() ?? Jadwal::latest('tanggal_mulai')->first();

        if ($this->jadwalAcuan) {
            $this->hasilPenilaian = HasilPenilaian::query()
                ->with('klasifikasiPenilaian')
                ->where('user_id', $user->id)
                ->where('jadwal_id', $this->jadwalAcuan->id)
                ->first();

            $this->nilaiKategori = app(PenilaianService::class)
                ->getNilaiKategoriMap($user->id, $this->jadwalAcuan->id)
                ->map(function ($item) {
                    return [
                        'nama' => $item['kategori_nama'],
                        'nilai' => $item['nilai'],
                    ];
                })->values()->all();
        }
    }

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
        <div class="max-w-screen-xl mx-auto px-6 md:px-20 space-y-6">
            <div class="bg-white p-8 rounded-lg shadow-md">
                <h1 class="text-3xl font-bold text-gray-900 mb-4">Ringkasan Hasil Penilaian</h1>
                @if($jadwalAcuan)
                    <p class="text-sm text-gray-500 mb-6">Periode: {{ $jadwalAcuan->nama }} ({{ $jadwalAcuan->tahun }})</p>
                @endif

                @php
                    $nilaiAkhir = $hasilPenilaian ? number_format($hasilPenilaian->nilai_akhir, 2) : '-';
                    $klasifikasi = $hasilPenilaian?->klasifikasiPenilaian?->nama ?? 'Belum terklasifikasi';
                    $statusVerifikasi = $hasilPenilaian?->status_verifikasi ?? 'Menunggu';

                    $statusLower = strtolower((string) $statusVerifikasi);
                    $statusVerifikasiClass = str_contains($statusLower, 'setuju') || str_contains($statusLower, 'terverifikasi')
                        ? 'bg-emerald-100 text-emerald-700 ring-emerald-200'
                        : (str_contains($statusLower, 'tolak') || str_contains($statusLower, 'revisi')
                            ? 'bg-rose-100 text-rose-700 ring-rose-200'
                            : 'bg-amber-100 text-amber-700 ring-amber-200');
                @endphp

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="py-4 px-5 rounded-xl border border-blue-200 bg-gradient-to-br from-blue-50 via-white to-blue-100 shadow-sm">
                        <p class="text-sm font-medium text-blue-700">Nilai Akhir</p>
                        <p class="mt-2 text-2xl font-bold text-blue-900">{{ $nilaiAkhir }}</p>
                    </div>
                    <div class="py-4 px-5 rounded-xl border border-teal-200 bg-gradient-to-br from-teal-50 via-white to-cyan-100 shadow-sm">
                        <p class="text-sm font-medium text-teal-700">Klasifikasi / Status</p>
                        <p class="mt-2 font-semibold text-teal-900">{{ $klasifikasi }}</p>
                    </div>
                    <div class="py-4 px-5 rounded-xl border border-amber-200 bg-gradient-to-br from-amber-50 via-white to-orange-100 shadow-sm">
                        <p class="text-sm font-medium text-amber-700">Status Verifikasi</p>
                        <p class="mt-2">
                            <span class="inline-flex items-center rounded-full px-3 py-1 text-sm font-semibold ring-1 {{ $statusVerifikasiClass }}">
                                {{ $statusVerifikasi }}
                            </span>
                        </p>
                    </div>
                </div>

                <div class="mt-6 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Kategori</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Nilai</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($nilaiKategori as $item)
                                <tr>
                                    <td class="px-4 py-3 text-sm text-gray-800">{{ $item['nama'] }}</td>
                                    <td class="px-4 py-3 text-sm font-semibold text-gray-800">{{ $item['nilai'] !== null ? number_format($item['nilai'], 2) : '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="px-4 py-3 text-sm text-gray-500 text-center">Belum ada nilai per kategori.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white p-8 rounded-lg shadow-md space-y-8">
                @php
                    function dataItem($label, $value) {
                        echo '<div class="py-3 px-4 border border-gray-200 rounded-md">';
                        echo '<p class="text-sm text-gray-500">' . htmlspecialchars($label) . '</p>';
                        echo '<p class="font-medium text-gray-800">' . htmlspecialchars($value ?? '-') . '</p>';
                        echo '</div>';
                    }
                @endphp

                <fieldset>
                    <legend class="text-lg font-semibold text-gray-900 mb-4">Data Badan Publik</legend>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {{ dataItem('Nama Badan Publik', $badanPublik->nama_badan_publik ?? '-') }}
                        {{ dataItem('Website', $badanPublik->website ?? '-') }}
                        {{ dataItem('No. Telepon', $badanPublik->telepon_badan_publik ?? '-') }}
                        {{ dataItem('Email', $badanPublik->email_badan_publik ?? '-') }}
                        <div class="md:col-span-2">
                            {{ dataItem('Alamat', $badanPublik->alamat ?? '-') }}
                        </div>
                    </div>
                </fieldset>
            </div>
        </div>
    </main>
</div>
