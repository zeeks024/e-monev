<?php

namespace App\Services;

use App\Models\BadanPublik;
use App\Models\HasilPenilaian;
use App\Models\Jadwal;
use App\Models\JadwalPertanyaan;
use App\Models\Jawaban;
use App\Models\KlasifikasiPenilaian;
use App\Models\Penilaian;
use App\Models\Submission;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StatistikService
{
    /**
     * 1. Average scores per category across all verified submissions for a jadwal.
     * Returns: Collection of [kategori_id, kategori_nama, average_score, max_score]
     */
    public function getPerCategoryScores(int $jadwalId): Collection
    {
        // Get submission IDs for this jadwal
        $submissionIds = Submission::query()
            ->where('jadwal_id', $jadwalId)
            ->pluck('id');

        if ($submissionIds->isEmpty()) {
            return collect();
        }

        // Get per-category scores from Penilaian joined with Submission
        $categoryScores = Penilaian::query()
            ->join('submissions', 'penilaians.submission_id', '=', 'submissions.id')
            ->join('kategoris', 'submissions.kategori_id', '=', 'kategoris.id')
            ->whereIn('penilaians.submission_id', $submissionIds)
            ->where('submissions.jadwal_id', $jadwalId)
            ->select('kategoris.id as kategori_id', 'kategoris.nama as kategori_nama', DB::raw('AVG(penilaians.nilai) as average_score'))
            ->groupBy('kategoris.id', 'kategoris.nama')
            ->get();

        // Get max possible score per category from JadwalPertanyaan
        $maxScores = JadwalPertanyaan::query()
            ->join('pertanyaan_templates', 'jadwal_pertanyaans.pertanyaan_template_id', '=', 'pertanyaan_templates.id')
            ->where('jadwal_pertanyaans.jadwal_id', $jadwalId)
            ->select('pertanyaan_templates.kategori_id', DB::raw('SUM(jadwal_pertanyaans.skor_maks) as max_score'))
            ->groupBy('pertanyaan_templates.kategori_id')
            ->pluck('max_score', 'kategori_id');

        return $categoryScores->map(function ($item) use ($maxScores) {
            return [
                'kategori_id' => $item->kategori_id,
                'kategori_nama' => $item->kategori_nama,
                'average_score' => round((float) $item->average_score, 2),
                'max_score' => (int) ($maxScores[$item->kategori_id] ?? 0),
            ];
        });
    }

    /**
     * 2. Distribution of scores across klasifikasi tiers for a jadwal.
     * Returns: Collection of [klasifikasi_id, nama, min_nilai, max_nilai, count]
     */
    public function getOverallDistribution(int $jadwalId): Collection
    {
        $klasifikasis = KlasifikasiPenilaian::query()
            ->active()
            ->orderBy('urutan')
            ->get();

        $hasilCounts = HasilPenilaian::query()
            ->where('jadwal_id', $jadwalId)
            ->whereNotNull('klasifikasi_penilaian_id')
            ->select('klasifikasi_penilaian_id', DB::raw('COUNT(*) as count'))
            ->groupBy('klasifikasi_penilaian_id')
            ->pluck('count', 'klasifikasi_penilaian_id');

        return $klasifikasis->map(function ($klasifikasi) use ($hasilCounts) {
            return [
                'klasifikasi_id' => $klasifikasi->id,
                'nama' => $klasifikasi->nama,
                'min_nilai' => (float) $klasifikasi->min_nilai,
                'max_nilai' => (float) $klasifikasi->max_nilai,
                'count' => (int) ($hasilCounts[$klasifikasi->id] ?? 0),
            ];
        });
    }

    /**
     * 3. Top-performing badan publik by nilai_akhir (descending).
     * Returns: Collection of [rank, badan_publik_id, nama_badan_publik, nilai_akhir, klasifikasi]
     */
    public function getTopBadanPublik(int $jadwalId, int $limit = 10): Collection
    {
        return HasilPenilaian::query()
            ->where('jadwal_id', $jadwalId)
            ->whereNotNull('nilai_akhir')
            ->with(['user.badanPublik', 'klasifikasiPenilaian'])
            ->orderByDesc('nilai_akhir')
            ->take($limit)
            ->get()
            ->values()
            ->map(function ($hasil, $index) {
                return [
                    'rank' => $index + 1,
                    'badan_publik_id' => $hasil->user?->badanPublik?->id,
                    'nama_badan_publik' => $hasil->user?->badanPublik?->nama_badan_publik ?? $hasil->user?->name ?? 'N/A',
                    'nilai_akhir' => (float) $hasil->nilai_akhir,
                    'klasifikasi' => $hasil->klasifikasiPenilaian?->nama,
                ];
            });
    }

    /**
     * 4. Lowest-performing badan publik by nilai_akhir (ascending).
     * Returns: Collection of [rank, badan_publik_id, nama_badan_publik, nilai_akhir, klasifikasi]
     */
    public function getBottomBadanPublik(int $jadwalId, int $limit = 10): Collection
    {
        return HasilPenilaian::query()
            ->where('jadwal_id', $jadwalId)
            ->whereNotNull('nilai_akhir')
            ->with(['user.badanPublik', 'klasifikasiPenilaian'])
            ->orderBy('nilai_akhir')
            ->take($limit)
            ->get()
            ->values()
            ->map(function ($hasil, $index) {
                return [
                    'rank' => $index + 1,
                    'badan_publik_id' => $hasil->user?->badanPublik?->id,
                    'nama_badan_publik' => $hasil->user?->badanPublik?->nama_badan_publik ?? $hasil->user?->name ?? 'N/A',
                    'nilai_akhir' => (float) $hasil->nilai_akhir,
                    'klasifikasi' => $hasil->klasifikasiPenilaian?->nama,
                ];
            });
    }

    /**
     * 5. Per-question statistics: % Ya answers, average skor_maks, pass rate.
     * Returns: Collection of [pertanyaan_id, teks_pertanyaan, skor_maks, ya_count, total_answers, ya_percentage, pass_rate]
     */
    public function getPerQuestionStatistics(int $jadwalId): Collection
    {
        $questions = JadwalPertanyaan::query()
            ->where('jadwal_id', $jadwalId)
            ->orderBy('urutan')
            ->get();

        if ($questions->isEmpty()) {
            return collect();
        }

        $questionIds = $questions->pluck('id');

        // Get all answers for these questions across all submissions
        $submissionIds = Submission::query()
            ->where('jadwal_id', $jadwalId)
            ->pluck('id');

        $answerStats = Jawaban::query()
            ->whereIn('jadwal_pertanyaan_id', $questionIds)
            ->whereIn('submission_id', $submissionIds)
            ->select('jadwal_pertanyaan_id', DB::raw('COUNT(*) as total'), DB::raw("SUM(CASE WHEN jawaban = 'Ya' THEN 1 ELSE 0 END) as ya_count"), DB::raw('SUM(CASE WHEN is_valid = 1 THEN 1 ELSE 0 END) as valid_count'))
            ->groupBy('jadwal_pertanyaan_id')
            ->get()
            ->keyBy('jadwal_pertanyaan_id');

        return $questions->map(function ($question) use ($answerStats) {
            $stats = $answerStats->get($question->id);
            $total = $stats ? (int) $stats->total : 0;
            $yaCount = $stats ? (int) $stats->ya_count : 0;
            $validCount = $stats ? (int) $stats->valid_count : 0;

            return [
                'pertanyaan_id' => $question->id,
                'teks_pertanyaan' => $question->teks_pertanyaan,
                'skor_maks' => (int) $question->skor_maks,
                'ya_count' => $yaCount,
                'total_answers' => $total,
                'ya_percentage' => $total > 0 ? round(($yaCount / $total) * 100, 1) : 0.0,
                'pass_rate' => $total > 0 ? round(($validCount / $total) * 100, 1) : 0.0,
            ];
        });
    }

    /**
     * 6. Year-over-year trends: compare jadwal periods over time (average scores per period).
     * Returns: Collection of [jadwal_id, nama, tahun, average_score, total_participants]
     */
    public function getYearOverYearTrends(): Collection
    {
        return Jadwal::query()
            ->withCount(['hasilPenilaians as total_participants' => function ($query) {
                $query->whereNotNull('nilai_akhir');
            }])
            ->withAvg(['hasilPenilaians as average_score' => function ($query) {
                $query->whereNotNull('nilai_akhir');
            }], 'nilai_akhir')
            ->orderBy('tahun')
            ->orderBy('tanggal_mulai')
            ->get()
            ->map(function ($jadwal) {
                return [
                    'jadwal_id' => $jadwal->id,
                    'nama' => $jadwal->nama,
                    'tahun' => $jadwal->tahun,
                    'average_score' => $jadwal->average_score ? round((float) $jadwal->average_score, 2) : 0.0,
                    'total_participants' => $jadwal->total_participants,
                ];
            });
    }

    /**
     * 7. Verification progress: how many submissions verified vs total for a jadwal.
     * Returns: [total_submissions, verified_submissions, unverified_submissions, verification_percentage]
     */
    public function getVerificationProgress(int $jadwalId): array
    {
        $total = Submission::query()
            ->where('jadwal_id', $jadwalId)
            ->count();

        $verified = Submission::query()
            ->where('jadwal_id', $jadwalId)
            ->where('status_verifikasi', 'Terverifikasi')
            ->count();

        return [
            'total_submissions' => $total,
            'verified_submissions' => $verified,
            'unverified_submissions' => $total - $verified,
            'verification_percentage' => $total > 0 ? round(($verified / $total) * 100, 1) : 0.0,
        ];
    }

    /**
     * 8. Individual badan publik's per-category scores for a jadwal.
     * Returns: Collection of [kategori_id, kategori_nama, nilai, max_score]
     */
    public function getCategoryBreakdownByBadanPublik(int $jadwalId, int $badanPublikId): Collection
    {
        $badanPublik = BadanPublik::find($badanPublikId);

        if (! $badanPublik) {
            return collect();
        }

        $userId = $badanPublik->user_id;

        // Get the user's verified submissions for this jadwal
        $submissionIds = Submission::query()
            ->where('user_id', $userId)
            ->where('jadwal_id', $jadwalId)
            ->pluck('id');

        if ($submissionIds->isEmpty()) {
            return collect();
        }

        // Get per-category scores
        $categoryScores = Penilaian::query()
            ->join('submissions', 'penilaians.submission_id', '=', 'submissions.id')
            ->join('kategoris', 'submissions.kategori_id', '=', 'kategoris.id')
            ->whereIn('penilaians.submission_id', $submissionIds)
            ->select('kategoris.id as kategori_id', 'kategoris.nama as kategori_nama', 'penilaians.nilai')
            ->get();

        // Get max possible score per category
        $maxScores = JadwalPertanyaan::query()
            ->join('pertanyaan_templates', 'jadwal_pertanyaans.pertanyaan_template_id', '=', 'pertanyaan_templates.id')
            ->where('jadwal_pertanyaans.jadwal_id', $jadwalId)
            ->select('pertanyaan_templates.kategori_id', DB::raw('SUM(jadwal_pertanyaans.skor_maks) as max_score'))
            ->groupBy('pertanyaan_templates.kategori_id')
            ->pluck('max_score', 'kategori_id');

        return $categoryScores->map(function ($item) use ($maxScores) {
            return [
                'kategori_id' => $item->kategori_id,
                'kategori_nama' => $item->kategori_nama,
                'nilai' => $item->nilai !== null ? (float) $item->nilai : null,
                'max_score' => (int) ($maxScores[$item->kategori_id] ?? 0),
            ];
        });
    }
}
