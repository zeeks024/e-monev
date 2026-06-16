<?php

use App\Models\BadanPublik;
use App\Models\HasilPenilaian;
use App\Models\Jadwal;
use App\Models\Jawaban;
use App\Models\Submission;
use App\Services\PenilaianService;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component
{
    public ?BadanPublik $badanPublik;
    public $jadwalAcuan = null;
    public $hasilPenilaian = null;
    public array $nilaiKategori = [];
    public array $catatanValidasi = [];

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

            $this->catatanValidasi = $this->loadCatatanValidasi($user->id, $this->jadwalAcuan->id);
        }
    }

    private function loadCatatanValidasi(int $userId, int $jadwalId): array
    {
        $submissionIds = Submission::where('user_id', $userId)
            ->where('jadwal_id', $jadwalId)
            ->pluck('id');

        if ($submissionIds->isEmpty()) {
            return [];
        }

        $jawabans = Jawaban::with(['jadwalPertanyaan', 'submission.kategori'])
            ->whereIn('submission_id', $submissionIds)
            ->whereNotNull('catatan')
            ->where('catatan', '!=', '')
            ->get();

        if ($jawabans->isEmpty()) {
            return [];
        }

        return $jawabans
            ->groupBy(fn($j) => $j->submission->kategori->nama ?? 'Lainnya')
            ->map(fn($group) => $group->map(fn($j) => [
                'pertanyaan' => $j->jadwalPertanyaan->teks_pertanyaan ?? '-',
                'is_valid' => $j->is_valid,
                'catatan' => $j->catatan,
            ])->values()->all())
            ->all();
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

                <div class="mt-6 flex flex-col gap-3 rounded-xl border border-gray-200 bg-gray-50 p-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <p class="text-sm font-semibold text-gray-900">Laporan Hasil Penilaian</p>
                        <p class="text-sm text-gray-600">
                            @if($hasilPenilaian && $hasilPenilaian->status_verifikasi === 'Terverifikasi' && $jadwalAcuan)
                                Laporan PDF untuk periode ini sudah tersedia dan bisa diunduh.
                            @else
                                Laporan PDF akan tersedia setelah hasil penilaian dinyatakan terverifikasi.
                            @endif
                        </p>
                    </div>

                    @if($hasilPenilaian && $hasilPenilaian->status_verifikasi === 'Terverifikasi' && $jadwalAcuan)
                        <a href="{{ route('laporan.unduh.saya', ['jadwalId' => $jadwalAcuan->id]) }}"
                           target="_blank"
                           class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
                            Unduh Laporan PDF
                        </a>
                    @else
                        <span class="inline-flex items-center justify-center rounded-lg bg-gray-200 px-4 py-2 text-sm font-medium text-gray-500">
                            Laporan Belum Tersedia
                        </span>
                    @endif
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
            @if(!empty($catatanValidasi))
                <div class="mt-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Catatan Validasi</h2>
                    @foreach($catatanValidasi as $kategoriNama => $catatanList)
                    <div class="mb-4">
                        <h3 class="text-sm font-semibold text-blue-700 mb-2">{{ $kategoriNama }}</h3>
                        <div class="space-y-2">
                            @foreach($catatanList as $item)
                            <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
                                <p class="text-sm text-gray-800 mb-1">{{ $item['pertanyaan'] }}</p>
                                <div class="flex items-start gap-2">
                                    @if($item['is_valid'] === true)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-emerald-100 text-emerald-800">Valid</span>
                                    @elseif($item['is_valid'] === false)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-rose-100 text-rose-800">Tidak Valid</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-amber-100 text-amber-800">Belum Ditinjau</span>
                                    @endif
                                    <p class="text-sm text-gray-600 italic">{{ $item['catatan'] }}</p>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>

            <div class="bg-white p-8 rounded-lg shadow-md space-y-8">
                <fieldset>
                    <div class="mb-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <legend class="text-lg font-semibold text-gray-900">Data Badan Publik</legend>
                        <a href="{{ route('biodata.edit') }}"
                           wire:navigate
                           class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
                            Edit Biodata
                        </a>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="py-3 px-4 border border-gray-200 rounded-md">
                            <p class="text-sm text-gray-500">Nama Badan Publik</p>
                            <p class="font-medium text-gray-800">{{ $badanPublik->nama_badan_publik ?? '-' }}</p>
                        </div>
                        <div class="py-3 px-4 border border-gray-200 rounded-md">
                            <p class="text-sm text-gray-500">Website</p>
                            <p class="font-medium text-gray-800">{{ $badanPublik->website ?? '-' }}</p>
                        </div>
                        <div class="py-3 px-4 border border-gray-200 rounded-md">
                            <p class="text-sm text-gray-500">No. Telepon</p>
                            <p class="font-medium text-gray-800">{{ $badanPublik->telepon_badan_publik ?? '-' }}</p>
                        </div>
                        <div class="py-3 px-4 border border-gray-200 rounded-md">
                            <p class="text-sm text-gray-500">Email</p>
                            <p class="font-medium text-gray-800">{{ $badanPublik->email_badan_publik ?? '-' }}</p>
                        </div>
                        <div class="md:col-span-2">
                            <div class="py-3 px-4 border border-gray-200 rounded-md">
                                <p class="text-sm text-gray-500">Alamat</p>
                                <p class="font-medium text-gray-800">{{ $badanPublik->alamat ?? '-' }}</p>
                            </div>
                        </div>
                    </div>
                </fieldset>
            </div>
        </div>
    </main>
</div>
