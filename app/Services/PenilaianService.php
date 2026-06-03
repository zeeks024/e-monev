<?php

namespace App\Services;

use App\Models\HasilPenilaian;
use App\Models\JadwalPertanyaan;
use App\Models\Jawaban;
use App\Models\KlasifikasiPenilaian;
use App\Models\Penilaian;
use App\Models\Submission;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PenilaianService
{
    /**
     * Get all active categories linked to a jadwal via its questions.
     */
    public function getKategoriAktifByJadwal(int $jadwalId): Collection
    {
        return JadwalPertanyaan::query()
            ->where('jadwal_pertanyaans.jadwal_id', $jadwalId)
            ->join('pertanyaan_templates', 'jadwal_pertanyaans.pertanyaan_template_id', '=', 'pertanyaan_templates.id')
            ->join('kategoris', 'pertanyaan_templates.kategori_id', '=', 'kategoris.id')
            ->select('kategoris.id', 'kategoris.nama')
            ->distinct()
            ->orderBy('kategoris.id')
            ->get();
    }

    /**
     * Get the latest submission for a user+jadwal+kategori combination.
     */
    public function getLatestSubmissionForCategory(int $userId, int $jadwalId, int $kategoriId): ?Submission
    {
        return Submission::query()
            ->where('user_id', $userId)
            ->where('jadwal_id', $jadwalId)
            ->where('kategori_id', $kategoriId)
            ->latest('tanggal_submit')
            ->latest('id')
            ->first();
    }

    /**
     * Calculate the score for a single submission based on validated Ya answers.
     * Score = sum of skor_maks for all jawabans where is_valid === true AND jawaban === 'Ya'.
     * Returns 0.0 if no validated Ya answers exist.
     */
    private function hitungNilaiSubmission(Submission $submission): float
    {
        $jawabans = Jawaban::query()
            ->where('submission_id', $submission->id)
            ->where('is_valid', true)
            ->where('jawaban', 'Ya')
            ->with('jadwalPertanyaan')
            ->get();

        $score = 0.0;
        foreach ($jawabans as $jawaban) {
            $score += (float) ($jawaban->jadwalPertanyaan?->skor_maks ?? 0);
        }

        return round($score, 2);
    }

    /**
     * Calculate per-category scores for a user+jadwal.
     * Each category score = sum of validated Ya questions' skor_maks within that category.
     * Categories without submissions return null nilai.
     */
    public function hitungNilaiPerKategori(int $userId, int $jadwalId): Collection
    {
        $kategoris = $this->getKategoriAktifByJadwal($jadwalId);

        return $kategoris->map(function ($kategori) use ($userId, $jadwalId) {
            $submission = $this->getLatestSubmissionForCategory($userId, $jadwalId, (int) $kategori->id);

            if (!$submission) {
                return [
                    'kategori_id' => (int) $kategori->id,
                    'kategori_nama' => $kategori->nama,
                    'submission_id' => null,
                    'nilai' => null,
                ];
            }

            $score = $this->hitungNilaiSubmission($submission);

            return [
                'kategori_id' => (int) $kategori->id,
                'kategori_nama' => $kategori->nama,
                'submission_id' => $submission->id,
                'nilai' => $score,
            ];
        });
    }

    /**
     * Calculate the final score as the direct sum of all validated Ya answers' skor_maks
     * across all categories for a user+jadwal.
     *
     * Scoring formula:
     * - For each Jawaban where is_valid === true AND jawaban === 'Ya': add skor_maks
     * - is_valid === null (not reviewed) or is_valid === false (invalid): contributes 0
     * - Edge cases: no submissions → 0, no validated questions → 0, all invalid → 0
     */
    public function hitungNilaiAkhir(int $userId, int $jadwalId): float
    {
        $submissions = Submission::query()
            ->where('user_id', $userId)
            ->where('jadwal_id', $jadwalId)
            ->get();

        if ($submissions->isEmpty()) {
            return 0.0;
        }

        $submissionIds = $submissions->pluck('id');

        $jawabans = Jawaban::query()
            ->whereIn('submission_id', $submissionIds)
            ->where('is_valid', true)
            ->where('jawaban', 'Ya')
            ->with('jadwalPertanyaan')
            ->get();

        if ($jawabans->isEmpty()) {
            return 0.0;
        }

        $totalScore = 0.0;
        foreach ($jawabans as $jawaban) {
            $totalScore += (float) ($jawaban->jadwalPertanyaan?->skor_maks ?? 0);
        }

        return round($totalScore, 2);
    }

    /**
     * Get per-category score map from Penilaian records.
     * Returns null nilai for categories without a Penilaian record.
     */
    public function getNilaiKategoriMap(int $userId, int $jadwalId): Collection
    {
        $kategoris = $this->getKategoriAktifByJadwal($jadwalId);

        return $kategoris->map(function ($kategori) use ($userId, $jadwalId) {
            $submission = $this->getLatestSubmissionForCategory($userId, $jadwalId, (int) $kategori->id);
            $nilai = $submission?->penilaian?->nilai;

            return [
                'kategori_id' => (int) $kategori->id,
                'kategori_nama' => $kategori->nama,
                'submission_id' => $submission?->id,
                'nilai' => $nilai !== null ? (float) $nilai : null,
            ];
        });
    }

    /**
     * Check whether all categories have been scored (have a Penilaian record with non-null nilai).
     */
    public function semuaKategoriSudahDinilai(int $userId, int $jadwalId): bool
    {
        $nilaiMap = $this->getNilaiKategoriMap($userId, $jadwalId);

        if ($nilaiMap->isEmpty()) {
            return false;
        }

        return $nilaiMap->every(fn ($item) => $item['nilai'] !== null);
    }

    /**
     * Resolve the classification tier for a given score.
     * Maps score to KlasifikasiPenilaian based on min/max thresholds.
     */
    public function resolveKlasifikasi(float $nilaiAkhir): ?KlasifikasiPenilaian
    {
        return KlasifikasiPenilaian::query()
            ->active()
            ->where('min_nilai', '<=', $nilaiAkhir)
            ->where('max_nilai', '>=', $nilaiAkhir)
            ->orderBy('urutan')
            ->first();
    }

    /**
     * Sync per-category scores into Penilaian records and overall score into HasilPenilaian.
     * Sets is_auto_calculated = true on all Penilaian records.
     * Uses DB transaction to prevent race conditions.
     */
    public function syncHasilPenilaian(int $userId, int $jadwalId): HasilPenilaian
    {
        return DB::transaction(function () use ($userId, $jadwalId) {
            // Calculate and store per-category scores
            $nilaiPerKategori = $this->hitungNilaiPerKategori($userId, $jadwalId);

            foreach ($nilaiPerKategori as $item) {
                if ($item['submission_id']) {
                    Penilaian::updateOrCreate(
                        ['submission_id' => $item['submission_id']],
                        [
                            'nilai' => $item['nilai'] ?? 0,
                            'is_auto_calculated' => true,
                        ]
                    );
                }
            }

            // Calculate overall score (direct sum across all categories)
            $nilaiAkhir = $this->hitungNilaiAkhir($userId, $jadwalId);
            $klasifikasi = $this->resolveKlasifikasi($nilaiAkhir);

            return HasilPenilaian::updateOrCreate(
                [
                    'user_id' => $userId,
                    'jadwal_id' => $jadwalId,
                ],
                [
                    'nilai_akhir' => $nilaiAkhir,
                    'klasifikasi_penilaian_id' => $klasifikasi?->id,
                ]
            );
        });
    }

    /**
     * Manually save a category score (override). Sets is_auto_calculated = false.
     */
    public function simpanNilaiKategori(int $submissionId, float $nilai): Penilaian
    {
        return Penilaian::updateOrCreate(
            ['submission_id' => $submissionId],
            [
                'nilai' => $nilai,
                'is_auto_calculated' => false,
                'status_informatif' => null,
            ]
        );
    }
}