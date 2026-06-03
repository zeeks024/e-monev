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

        $pdf = Pdf::loadView('admin.laporan-pdf', [
            'laporans' => $laporans,
            'namaKlasifikasi' => $namaKlasifikasi,
            'tanggal' => now()->isoFormat('D MMMM YYYY')
        ]);

        return $pdf->download('laporan-e-monev-kip-' . now()->format('Y-m-d') . '.pdf');
    }

    public function unduhPdfPerBadanPublik(Request $request, int $userId, int $jadwalId)
    {
        $badanPublik = BadanPublik::where('user_id', $userId)->firstOrFail();
        $jadwal = Jadwal::findOrFail($jadwalId);

        $hasilPenilaian = HasilPenilaian::query()
            ->where('user_id', $userId)
            ->where('jadwal_id', $jadwalId)
            ->where('status_verifikasi', 'Terverifikasi')
            ->firstOrFail();

        // Get scores per category using PenilaianService
        $service = app(PenilaianService::class);
        $nilaiPerKategori = $service->getNilaiKategoriMap($userId, $jadwalId);

        // Get per-statement detail grouped by kategori
        $detailPerKategori = $this->getDetailPernyataan($userId, $jadwalId);

        $pdf = Pdf::loadView('admin.laporan-per-badan-publik-pdf', [
            'badanPublik' => $badanPublik,
            'jadwal' => $jadwal,
            'hasilPenilaian' => $hasilPenilaian,
            'nilaiPerKategori' => $nilaiPerKategori,
            'detailPerKategori' => $detailPerKategori,
            'tanggal' => now()->isoFormat('D MMMM YYYY')
        ]);

        $namaFile = 'laporan-' . Str::slug($badanPublik->nama_badan_publik) . '-' . $jadwal->nama . '-' . now()->format('Y-m-d') . '.pdf';

        return $pdf->download($namaFile);
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
}
