<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BadanPublik;
use App\Models\HasilPenilaian;
use App\Models\Jadwal;
use App\Models\Jawaban;
use App\Models\KlasifikasiPenilaian;
use App\Models\Submission;
use App\Services\PenilaianService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LaporanController extends Controller
{
    public function unduhPdf(Request $request)
    {
        $klasifikasiId = $request->query('klasifikasiId');

        $query = HasilPenilaian::with(['user.badanPublik', 'jadwal', 'klasifikasiPenilaian'])
            ->where('status_verifikasi', 'Terverifikasi');

        if ($klasifikasiId && $klasifikasiId !== 'semua') {
            $query->where('klasifikasi_penilaian_id', $klasifikasiId);
        }

        $laporans = $query->get();
        $namaKlasifikasi = $klasifikasiId && $klasifikasiId !== 'semua'
            ? KlasifikasiPenilaian::find($klasifikasiId)?->nama
            : 'Semua Klasifikasi';

        $ringkasan = [
            'total_laporan' => $laporans->count(),
            'rata_rata_nilai' => round((float) $laporans->avg('nilai_akhir'), 2),
            'nilai_tertinggi' => optional($laporans->sortByDesc('nilai_akhir')->first(), function ($laporan) {
                return [
                    'nama' => $laporan->user->badanPublik->nama_badan_publik ?? 'N/A',
                    'nilai' => (float) $laporan->nilai_akhir,
                ];
            }),
            'nilai_terendah' => optional($laporans->sortBy('nilai_akhir')->first(), function ($laporan) {
                return [
                    'nama' => $laporan->user->badanPublik->nama_badan_publik ?? 'N/A',
                    'nilai' => (float) $laporan->nilai_akhir,
                ];
            }),
        ];

        $rekapKlasifikasi = $laporans
            ->groupBy(fn ($laporan) => $laporan->klasifikasiPenilaian->nama ?? 'Belum terklasifikasi')
            ->map(fn (Collection $items, string $nama) => [
                'nama' => $nama,
                'jumlah' => $items->count(),
            ])
            ->values();

        $pdf = Pdf::loadView('admin.laporan-pdf', [
            'laporans' => $laporans,
            'namaKlasifikasi' => $namaKlasifikasi,
            'tanggal' => now()->isoFormat('D MMMM YYYY'),
            'ringkasan' => $ringkasan,
            'rekapKlasifikasi' => $rekapKlasifikasi,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('laporan-e-monev-kip-' . now()->format('Y-m-d') . '.pdf');
    }

    public function unduhPdfPerBadanPublik(Request $request, int $userId, int $jadwalId)
    {
        return $this->downloadPerBadanPublikPdf($userId, $jadwalId);
    }

    public function unduhPdfDinas(Request $request, int $jadwalId)
    {
        $userId = Auth::id();
        abort_unless($userId, 403);

        return $this->downloadPerBadanPublikPdf($userId, $jadwalId);
    }

    /**
     * Get per-statement detail (pertanyaan + jawaban + is_valid + catatan) grouped by kategori.
     */
    private function getDetailPernyataan(int $userId, int $jadwalId): array
    {
        $submissions = Submission::with('kategori')
            ->where('user_id', $userId)
            ->where('jadwal_id', $jadwalId)
            ->get();

        if ($submissions->isEmpty()) {
            return [];
        }

        $submissionIds = $submissions->pluck('id')->toArray();
        $submissionByKategori = $submissions->keyBy('kategori_id');

        $jawabans = Jawaban::with('jadwalPertanyaan')
            ->whereIn('submission_id', $submissionIds)
            ->get();

        // Group by kategori, then list each pertanyaan with answer & validation
        $detail = [];
        foreach ($jawabans as $jawaban) {
            $submission = $submissions->firstWhere('id', $jawaban->submission_id);
            if (!$submission) continue;

            $kategoriNama = $submission->kategori->nama ?? 'Lainnya';
            $kategoriKey = $submission->kategori_id ?? 0;

            if (!isset($detail[$kategoriKey])) {
                $detail[$kategoriKey] = [
                    'nama' => $kategoriNama,
                    'pernyataan' => [],
                ];
            }

            $detail[$kategoriKey]['pernyataan'][] = [
                'teks_pertanyaan' => $jawaban->jadwalPertanyaan->teks_pertanyaan ?? '-',
                'skor_maks' => $jawaban->jadwalPertanyaan->skor_maks ?? 0,
                'jawaban' => $jawaban->jawaban ?? '-',
                'is_valid' => $jawaban->is_valid,
                'catatan' => $jawaban->catatan,
            ];
        }

        // Sort pernyataan within each kategori by skor_maks descending
        foreach ($detail as &$kat) {
            usort($kat['pernyataan'], fn($a, $b) => $b['skor_maks'] <=> $a['skor_maks']);
        }
        unset($kat);

        return $detail;
    }

    private function downloadPerBadanPublikPdf(int $userId, int $jadwalId)
    {
        $badanPublik = BadanPublik::where('user_id', $userId)->firstOrFail();
        $jadwal = Jadwal::findOrFail($jadwalId);

        $hasilPenilaian = HasilPenilaian::query()
            ->where('user_id', $userId)
            ->where('jadwal_id', $jadwalId)
            ->where('status_verifikasi', 'Terverifikasi')
            ->firstOrFail();

        $service = app(PenilaianService::class);
        $nilaiPerKategori = $service->getNilaiKategoriMap($userId, $jadwalId);
        $detailPerKategori = $this->getDetailPernyataan($userId, $jadwalId);
        $rekapValidasi = $this->getRekapValidasi($detailPerKategori);
        $ringkasanKategori = $this->getRingkasanKategori($nilaiPerKategori);

        $pdf = Pdf::loadView('admin.laporan-per-badan-publik-pdf', [
            'badanPublik' => $badanPublik,
            'jadwal' => $jadwal,
            'hasilPenilaian' => $hasilPenilaian,
            'nilaiPerKategori' => $nilaiPerKategori,
            'detailPerKategori' => $detailPerKategori,
            'tanggal' => now()->isoFormat('D MMMM YYYY'),
            'rekapValidasi' => $rekapValidasi,
            'ringkasanKategori' => $ringkasanKategori,
        ])->setPaper('a4', 'portrait');

        $namaFile = 'laporan-' . Str::slug($badanPublik->nama_badan_publik) . '-' . Str::slug($jadwal->nama) . '-' . now()->format('Y-m-d') . '.pdf';

        return $pdf->download($namaFile);
    }

    private function getRekapValidasi(array $detailPerKategori): array
    {
        $items = collect($detailPerKategori)
            ->flatMap(fn (array $kategori) => $kategori['pernyataan'] ?? []);

        return [
            'total' => $items->count(),
            'valid' => $items->where('is_valid', true)->count(),
            'tidak_valid' => $items->where('is_valid', false)->count(),
            'belum_ditinjau' => $items->filter(fn (array $item) => $item['is_valid'] === null)->count(),
        ];
    }

    private function getRingkasanKategori(Collection|array $nilaiPerKategori): array
    {
        $items = collect($nilaiPerKategori);

        return [
            'total' => $items->count(),
            'sudah_dinilai' => $items->filter(fn (array $item) => $item['nilai'] !== null)->count(),
            'belum_dinilai' => $items->filter(fn (array $item) => $item['nilai'] === null)->count(),
            'rata_rata' => round((float) $items->pluck('nilai')->filter(fn ($nilai) => $nilai !== null)->avg(), 2),
        ];
    }
}
