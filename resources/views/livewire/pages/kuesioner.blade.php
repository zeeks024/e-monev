<?php

use App\Models\BadanPublik;
use App\Models\Jawaban;
use App\Models\Kategori;
use App\Models\Jadwal;
use App\Models\JadwalPertanyaan;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Livewire\Volt\Component;

new #[Layout('components.layouts.app')] class extends Component
{
    public ?BadanPublik $badanPublik = null;
    public string $namaResponden = '';
    public string $teleponResponden = '';
    public string $jadwal = 'Belum diatur';
    public bool $isJadwalAktif = false;
    public bool $sudahSelesai = false;

    public function mount(): void {
        $user = Auth::user();
        if ($user) {
            $this->badanPublik       = $user->badanPublik;
            $this->namaResponden     = $user->name;
            $this->teleponResponden  = $this->badanPublik?->telepon_responden ?? 'Tidak ada';

            // Get active schedule
            $jadwalAktif = Jadwal::active()->first();

            if ($jadwalAktif) {
                // cek jawaban terakhir untuk active schedule
                $kategoriTerakhir = Kategori::latest('id')->first();
                if ($kategoriTerakhir) {
                    $jadwalPertanyaanTerakhir = JadwalPertanyaan::where('jadwal_id', $jadwalAktif->id)
                        ->whereHas('pertanyaanTemplate', function($query) use ($kategoriTerakhir) {
                            $query->where('kategori_id', $kategoriTerakhir->id);
                        })
                        ->latest('id')
                        ->first();

                    if ($jadwalPertanyaanTerakhir) {
                        $jawabanTerakhir = Jawaban::where('jadwal_pertanyaan_id', $jadwalPertanyaanTerakhir->id)
                            ->whereHas('submission', function ($query) use ($user) {
                                $query->where('user_id', $user->id);
                            })
                            ->exists();

                        if ($jawabanTerakhir) {
                            $this->sudahSelesai = true;
                        }
                    }
                }

                // Set jadwal info
                $tanggalMulai   = Carbon::parse($jadwalAktif->tanggal_mulai);
                $tanggalSelesai = Carbon::parse($jadwalAktif->tanggal_selesai);
                $this->jadwal = $jadwalAktif->nama . ' (' . $jadwalAktif->tahun . ') - ' .
                                $tanggalMulai->timezone('Asia/Jakarta')->isoFormat('D MMMM YYYY, HH:mm') . ' WIB s/d ' .
                                $tanggalSelesai->timezone('Asia/Jakarta')->isoFormat('D MMMM YYYY, HH:mm') . ' WIB';
                $this->isJadwalAktif = true;
            } else {
                // Check if any jadwal exists but not active
                $jadwalDb = Jadwal::first();
                if ($jadwalDb) {
                    $tanggalMulai   = Carbon::parse($jadwalDb->tanggal_mulai);
                    $tanggalSelesai = Carbon::parse($jadwalDb->tanggal_selesai);
                    $this->jadwal = $jadwalDb->nama . ' (' . $jadwalDb->tahun . ') - ' .
                                    $tanggalMulai->timezone('Asia/Jakarta')->isoFormat('D MMMM YYYY, HH:mm') . ' WIB s/d ' .
                                    $tanggalSelesai->timezone('Asia/Jakarta')->isoFormat('D MMMM YYYY, HH:mm') . ' WIB';
                }
            }
        }
    }

    public function logout(): void {
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();
        $this->redirect('/', navigate: true);
    }
}; ?>


    <div class="min-h-screen bg-gray-100">
        <main class="py-12">
            <div class="max-w-screen-xl mx-auto px-6 md:px-20">
                <h1 class="text-3xl font-bold text-gray-900 mb-6">Data Kuesioner</h1>

                @if($badanPublik)
                    <div class="bg-white p-8 rounded-lg shadow-md space-y-6">
                        <div class="py-3 px-4 border border-gray-200 rounded-md">
                            <p class="text-sm text-gray-500">Nama Responden</p>
                            <p class="font-medium text-gray-800">{{ $namaResponden }}</p>
                        </div>

                        <div class="py-3 px-4 border border-gray-200 rounded-md">
                            <p class="text-sm text-gray-500">Badan Publik</p>
                            <p class="font-medium text-gray-800">{{ $badanPublik->nama_badan_publik }}</p>
                        </div>

                        <div class="py-3 px-4 border border-gray-200 rounded-md">
                            <p class="text-sm text-gray-500">No. Telepon</p>
                            <p class="font-medium text-gray-800">{{ $teleponResponden }}</p>
                        </div>

                        <div class="py-3 px-4 border border-gray-200 rounded-md">
                            <p class="text-sm text-gray-500">Durasi Kuesioner</p>
                            <p class="font-medium text-gray-800">{{ $jadwal }}</p>
                        </div>

                        <div class="pt-6 text-center">
                            @if ($sudahSelesai)
                                <button disabled class="inline-block px-8 py-3 bg-gray-400 text-white text-lg font-semibold rounded-md cursor-not-allowed">
                                    Mulai Mengisi
                                </button>
                                <p class="text-sm text-green-600 font-semibold mt-2">Anda sudah mengerjakan kuesioner ini. Terima kasih.</p>
                            @elseif ($isJadwalAktif)
                                <a href="{{ route('kuesioner.jawab') }}" class="inline-block px-8 py-3 bg-green-500 text-white text-lg font-semibold rounded-md hover:bg-green-600 transition-colors">
                                    Mulai Mengisi
                                </a>
                            @else
                                <button disabled class="inline-block px-8 py-3 bg-gray-400 text-white text-lg font-semibold rounded-md cursor-not-allowed">
                                    Mulai Mengisi
                                </button>
                                @if ($jadwal !== 'Belum diatur')
                                    <p class="text-sm text-gray-500 mt-2">Kuesioner belum dapat diisi sesuai jadwal yang ditentukan.</p>
                                @else
                                    <p class="text-sm text-gray-500 mt-2">Belum ada jadwal yang tersedia.</p>
                                @endif
                            @endif
                        </div>
                    </div>
                @else
                    <div class="bg-white p-8 rounded-lg shadow-md text-center">
                        <p>Data tidak ditemukan.</p>
                    </div>
                @endif
            </div>
        </main>
    </div>
