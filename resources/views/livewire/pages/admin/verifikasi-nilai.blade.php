<?php

use App\Models\HasilPenilaian;
use App\Models\Jadwal;
use App\Models\Jawaban;
use App\Models\User;
use App\Services\PenilaianService;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.admin')] class extends Component
{
    public User $user;
    public Jadwal $jadwal;
    public array $kategoriSections = [];
    public array $validasiJawaban = [];

    public function mount(User $user, Jadwal $jadwal): void
    {
        $this->user = $user->load('badanPublik');
        $this->jadwal = $jadwal;
        $this->loadKategoriSections();
    }

    public function loadKategoriSections(): void
    {
        $service = app(PenilaianService::class);
        $kategoris = $service->getKategoriAktifByJadwal($this->jadwal->id);

        $sections = [];
        $validasi = [];

        foreach ($kategoris as $kategori) {
            $submission = $service->getLatestSubmissionForCategory($this->user->id, $this->jadwal->id, (int) $kategori->id);
            $answers = $submission
                ? $submission->jawaban()->with('jadwalPertanyaan')->orderBy('jadwal_pertanyaan_id')->get()
                : collect();

            $jawabanData = [];
            foreach ($answers as $answer) {
                $jawabanData[] = [
                    'id' => $answer->id,
                    'jawaban' => $answer->jawaban,
                    'link_dokumen' => $answer->link_dokumen,
                    'upload_dokumen' => $answer->upload_dokumen,
                    'skor_maks' => (float) ($answer->jadwalPertanyaan->skor_maks ?? 0),
                    'teks_pertanyaan' => $answer->jadwalPertanyaan->teks_pertanyaan ?? 'Pertanyaan tidak ditemukan',
                    'definisi_operasional' => $answer->jadwalPertanyaan->definisi_operasional ?? null,
                ];

                $validasi[$answer->id] = [
                    'is_valid' => $answer->is_valid,
                    'catatan' => $answer->catatan ?? '',
                ];
            }

            $sections[] = [
                'kategori_id' => (int) $kategori->id,
                'kategori_nama' => $kategori->nama,
                'submission_id' => $submission?->id,
                'tanggal_submit' => $submission?->tanggal_submit,
                'jawabans' => $jawabanData,
            ];
        }

        $this->kategoriSections = $sections;
        $this->validasiJawaban = $validasi;
    }

    public function toggleValidasi(int $jawabanId, string $value): void
    {
        if (!isset($this->validasiJawaban[$jawabanId])) {
            return;
        }

        if ($value === 'valid') {
            $this->validasiJawaban[$jawabanId]['is_valid'] =
                $this->validasiJawaban[$jawabanId]['is_valid'] === true ? null : true;
        } elseif ($value === 'tidak_valid') {
            $this->validasiJawaban[$jawabanId]['is_valid'] =
                $this->validasiJawaban[$jawabanId]['is_valid'] === false ? null : false;
        }
    }

    private function syncNilaiKategori(): void
    {
        $service = app(PenilaianService::class);

        foreach ($this->kategoriSections as $section) {
            if (!$section['submission_id']) {
                continue;
            }

            $score = 0;
            foreach ($section['jawabans'] as $jawaban) {
                $validasi = $this->validasiJawaban[$jawaban['id']] ?? null;
                if ($validasi && $validasi['is_valid'] === true && $jawaban['jawaban'] === 'Ya') {
                    $score += $jawaban['skor_maks'];
                }
            }

            $service->simpanNilaiKategori((int) $section['submission_id'], (float) $score);
        }
    }

    public function simpan(): void
    {
        foreach ($this->validasiJawaban as $jawabanId => $data) {
            Jawaban::where('id', $jawabanId)->update([
                'is_valid' => $data['is_valid'],
                'catatan' => $data['catatan'] !== '' ? $data['catatan'] : null,
            ]);
        }

        $this->syncNilaiKategori();
        app(PenilaianService::class)->syncHasilPenilaian($this->user->id, $this->jadwal->id);

        session()->flash('success', 'Progress verifikasi berhasil disimpan.');
        $this->loadKategoriSections();
    }

    public function selesaiVerifikasi(): void
    {
        foreach ($this->validasiJawaban as $data) {
            if ($data['is_valid'] === null) {
                session()->flash('error', 'Semua pertanyaan harus divalidasi sebelum menyelesaikan verifikasi.');
                return;
            }
        }

        foreach ($this->validasiJawaban as $jawabanId => $data) {
            Jawaban::where('id', $jawabanId)->update([
                'is_valid' => $data['is_valid'],
                'catatan' => $data['catatan'] !== '' ? $data['catatan'] : null,
            ]);
        }

        $this->syncNilaiKategori();

        $service = app(PenilaianService::class);
        $service->syncHasilPenilaian($this->user->id, $this->jadwal->id);

        $hasil = HasilPenilaian::query()
            ->where('user_id', $this->user->id)
            ->where('jadwal_id', $this->jadwal->id)
            ->first();

        $hasil->update([
            'status_verifikasi' => 'Terverifikasi',
            'verified_at' => now(),
        ]);

        session()->flash('success', 'Verifikasi nilai dinas berhasil diselesaikan.');
        $this->redirectRoute('admin.penilaian', navigate: true);
    }

    public function with(): array
    {
        $hasil = HasilPenilaian::query()
            ->with('klasifikasiPenilaian')
            ->where('user_id', $this->user->id)
            ->where('jadwal_id', $this->jadwal->id)
            ->first();

        $kategoriScores = [];
        $totalScore = 0;

        foreach ($this->kategoriSections as $section) {
            $score = 0;
            foreach ($section['jawabans'] as $jawaban) {
                $validasi = $this->validasiJawaban[$jawaban['id']] ?? null;
                if ($validasi && $validasi['is_valid'] === true && $jawaban['jawaban'] === 'Ya') {
                    $score += $jawaban['skor_maks'];
                }
            }
            $kategoriScores[$section['kategori_id']] = $score;
            $totalScore += $score;
        }

        $allValidated = true;
        $totalQuestions = 0;
        $validatedQuestions = 0;
        foreach ($this->validasiJawaban as $data) {
            $totalQuestions++;
            if ($data['is_valid'] !== null) {
                $validatedQuestions++;
            }
            if ($data['is_valid'] === null) {
                $allValidated = false;
            }
        }

        $klasifikasi = app(PenilaianService::class)->resolveKlasifikasi($totalScore);

        return [
            'hasil' => $hasil,
            'klasifikasi' => $klasifikasi,
            'kategoriScores' => $kategoriScores,
            'totalScore' => $totalScore,
            'allValidated' => $allValidated,
            'totalQuestions' => $totalQuestions,
            'validatedQuestions' => $validatedQuestions,
        ];
    }
}; ?>

<div>
    <x-slot name="header">
        <div class="flex items-center space-x-4">
            <a href="{{ route('admin.penilaian') }}" wire:navigate class="p-2 rounded-full hover:bg-gray-200 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
            </a>
            <h1 class="text-3xl font-bold text-gray-900">Verifikasi Nilai Dinas</h1>
        </div>
    </x-slot>

    <main class="p-8 space-y-6">
        @if (session('success'))
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4" role="alert">
                <p>{{ session('success') }}</p>
            </div>
        @endif
        @if (session('error'))
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4" role="alert">
                <p>{{ session('error') }}</p>
            </div>
        @endif

        {{-- Info Card --}}
        <div class="bg-white p-6 rounded-lg shadow-md">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div>
                    <h3 class="text-sm font-medium text-gray-500">Nama Dinas / Badan Publik</h3>
                    <p class="mt-1 text-lg font-semibold text-gray-900">{{ $user->badanPublik->nama_badan_publik ?? $user->name }}</p>
                </div>
                <div>
                    <h3 class="text-sm font-medium text-gray-500">Jadwal Penilaian</h3>
                    <p class="mt-1 text-lg font-semibold text-gray-900">{{ $jadwal->nama }} ({{ $jadwal->tahun }})</p>
                </div>
                <div>
                    <h3 class="text-sm font-medium text-gray-500">Nilai Akhir</h3>
                    <p class="mt-1 text-lg font-semibold text-gray-900">{{ number_format($totalScore, 2) }}</p>
                </div>
                <div>
                    <h3 class="text-sm font-medium text-gray-500">Klasifikasi</h3>
                    <p class="mt-1 text-lg font-semibold text-gray-900">{{ $klasifikasi?->nama ?? 'Belum terklasifikasi' }}</p>
                </div>
            </div>

            {{-- Progress Bar --}}
            <div class="mt-4 pt-4 border-t border-gray-100">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium text-gray-700">Progress Validasi</span>
                    <span class="text-sm text-gray-500">{{ $validatedQuestions }} dari {{ $totalQuestions }} pertanyaan divalidasi</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2.5">
                    <div class="bg-blue-600 h-2.5 rounded-full transition-all duration-300" style="width: {{ $totalQuestions > 0 ? round(($validatedQuestions / $totalQuestions) * 100) : 0 }}%"></div>
                </div>
            </div>
        </div>

        @forelse ($kategoriSections as $section)
            @php
                $kategoriScore = $kategoriScores[$section['kategori_id']] ?? 0;
                $kategoriMaxScore = 0;
                foreach ($section['jawabans'] as $j) {
                    $kategoriMaxScore += $j['skor_maks'];
                }
            @endphp

            <div class="bg-white rounded-lg shadow-md">
                {{-- Category Header --}}
                <div class="p-6 border-b border-gray-100">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div class="flex-1 min-w-0">
                            <h2 class="text-xl font-semibold text-gray-900">{{ $section['kategori_nama'] }}</h2>
                            <p class="text-sm text-gray-500">
                                Tanggal submit: {{ $section['tanggal_submit'] ? \Carbon\Carbon::parse($section['tanggal_submit'])->isoFormat('D MMMM YYYY') : 'Belum ada submission' }}
                            </p>
                        </div>
                        <div class="flex-shrink-0 text-right">
                            <p class="text-sm font-medium text-gray-500">Skor Kategori</p>
                            <p class="text-2xl font-bold {{ $kategoriScore > 0 ? 'text-green-600' : 'text-gray-400' }}">{{ number_format($kategoriScore, 1) }}<span class="text-sm font-normal text-gray-400"> / {{ number_format($kategoriMaxScore, 1) }}</span></p>
                        </div>
                    </div>
                </div>

                {{-- Questions --}}
                <div class="divide-y divide-gray-100">
                    @forelse ($section['jawabans'] as $index => $jawaban)
                        @php
                            $isValid = $validasiJawaban[$jawaban['id']]['is_valid'] ?? null;
                            $catatan = $validasiJawaban[$jawaban['id']]['catatan'] ?? '';

                            // Score contribution logic
                            $contributesScore = ($isValid === true && $jawaban['jawaban'] === 'Ya');
                            $scoreContribution = $contributesScore ? $jawaban['skor_maks'] : 0;
                        @endphp

                        <div class="p-6 hover:bg-gray-50 transition-colors">
                            <div class="flex flex-col lg:flex-row lg:items-start gap-4">
                                {{-- Question Number & Text --}}
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-start gap-3">
                                        <span class="flex-shrink-0 inline-flex items-center justify-center w-7 h-7 rounded-full bg-gray-100 text-sm font-medium text-gray-600">{{ $index + 1 }}</span>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-medium text-gray-900">{{ $jawaban['teks_pertanyaan'] }}</p>
                                            @if ($jawaban['definisi_operasional'])
                                                <p class="mt-1 text-xs text-gray-500 italic">{{ $jawaban['definisi_operasional'] }}</p>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- Answer & Documents Row --}}
                                    <div class="mt-3 flex flex-wrap items-center gap-3 ml-10">
                                        {{-- Answer Badge --}}
                                        @if ($jawaban['jawaban'] === 'Ya')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                Ya
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                Tidak
                                            </span>
                                        @endif

                                        {{-- Skor Maks Badge --}}
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            Skor Maks: {{ $jawaban['skor_maks'] }}
                                        </span>

                                        {{-- Document Links --}}
                                        @if ($jawaban['link_dokumen'])
                                            <a href="{{ $jawaban['link_dokumen'] }}" target="_blank" class="inline-flex items-center gap-1 text-xs text-blue-600 hover:underline">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.301a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" /></svg>
                                                Link Dokumen
                                            </a>
                                        @endif
                                        @if ($jawaban['upload_dokumen'])
                                            <a href="{{ asset('storage/' . $jawaban['upload_dokumen']) }}" target="_blank" class="inline-flex items-center gap-1 text-xs text-blue-600 hover:underline">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                                File Dokumen
                                            </a>
                                        @endif
                                    </div>
                                </div>

                                {{-- Validation Controls --}}
                                <div class="flex-shrink-0 lg:w-72">
                                    {{-- Valid/Tidak Valid Toggle --}}
                                    <div class="flex items-center gap-2">
                                        <button
                                            wire:click="toggleValidasi({{ $jawaban['id'] }}, 'valid')"
                                            type="button"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium rounded-md transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 {{ $isValid === true ? 'bg-green-600 text-white hover:bg-green-700' : 'bg-white text-gray-600 border border-gray-300 hover:bg-gray-50' }}"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                            Valid
                                        </button>
                                        <button
                                            wire:click="toggleValidasi({{ $jawaban['id'] }}, 'tidak_valid')"
                                            type="button"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium rounded-md transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 {{ $isValid === false ? 'bg-red-600 text-white hover:bg-red-700' : 'bg-white text-gray-600 border border-gray-300 hover:bg-gray-50' }}"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                            Tidak Valid
                                        </button>
                                    </div>

                                    {{-- Validation Status --}}
                                    @if ($isValid === null)
                                        <p class="mt-1 text-xs text-amber-600 font-medium">Belum direview</p>
                                    @elseif ($isValid === true)
                                        <p class="mt-1 text-xs text-green-600 font-medium">Tervalidasi</p>
                                    @else
                                        <p class="mt-1 text-xs text-red-600 font-medium">Dinyatakan tidak valid</p>
                                    @endif

                                    {{-- Score Contribution --}}
                                    <div class="mt-2">
                                        @if ($contributesScore)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-green-100 text-green-800">
                                                +{{ number_format($jawaban['skor_maks'], 1) }}
                                            </span>
                                        @elseif ($isValid === false)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-red-100 text-red-800">
                                                0
                                            </span>
                                        @elseif ($isValid === null)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-amber-100 text-amber-800">
                                                &mdash;
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-gray-100 text-gray-600">
                                                0
                                            </span>
                                        @endif
                                    </div>

                                    {{-- Catatan Textarea --}}
                                    <textarea
                                        wire:model="validasiJawaban.{{ $jawaban['id'] }}.catatan"
                                        rows="2"
                                        placeholder="Catatan validasi..."
                                        class="mt-2 w-full text-xs border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                    ></textarea>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="p-6 text-center text-sm text-gray-500">
                            Tidak ada jawaban pada kategori ini.
                        </div>
                    @endforelse
                </div>
            </div>
        @empty
            <div class="bg-white p-6 rounded-lg shadow-md text-sm text-gray-500">
                Tidak ada kategori aktif pada jadwal ini.
            </div>
        @endforelse

        {{-- Action Buttons --}}
        <div class="bg-white p-6 rounded-lg shadow-md flex flex-col sm:flex-row justify-between gap-3">
            <a href="{{ route('admin.penilaian') }}" wire:navigate class="px-6 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 text-center">
                Kembali
            </a>
            <div class="flex gap-3">
                <button wire:click="simpan" wire:loading.attr="disabled" class="px-6 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50">
                    Simpan Progress
                </button>
                <button
                    wire:click="selesaiVerifikasi"
                    wire:loading.attr="disabled"
                    @if (!$allValidated) disabled @endif
                    class="px-6 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 disabled:bg-gray-300 disabled:cursor-not-allowed"
                    @if (!$allValidated) title="Validasi semua pertanyaan terlebih dahulu" @endif
                >
                    Selesai Verifikasi
                </button>
            </div>
        </div>
    </main>
</div>