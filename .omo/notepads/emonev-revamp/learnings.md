# Learnings — E-Monev Revamp

## PenilaianService Scoring Rewrite (Task 4)

### Key Changes
- hitungNilaiAkhir(): Changed from averaging manual category scores to computing direct sum of validated Ya answers' skor_maks. Formula: for each Jawaban where is_valid === true AND jawaban === 'Ya', add jadwalPertanyaan.skor_maks. Max possible = 100.
- hitungNilaiPerKategori() (new): Computes per-category scores by grouping validated Ya answers by kategori. Each category score = sum of validated Ya questions' skor_maks within that category.
- hitungNilaiSubmission() (new private): Calculates score for a single Submission based on its validated Ya answers.
- syncHasilPenilaian(): Now wraps in DB transaction, calls hitungNilaiPerKategori() to store per-category scores in Penilaian records with is_auto_calculated = true, then calls hitungNilaiAkhir() for the overall total stored in HasilPenilaian.
- simpanNilaiKategori(): Now sets is_auto_calculated = false (manual override).
- Edge cases: No submissions -> 0, no validated questions -> 0, all invalid -> 0, is_valid = null (not reviewed) -> contributes 0.

### ThreeStateBoolean Semantics
- is_valid = null -> not reviewed -> contributes 0 points
- is_valid = true -> validated -> Ya answers contribute skor_maks
- is_valid = false -> invalid -> contributes 0 points

### Preserved Methods
- getKategoriAktifByJadwal() — unchanged
- getLatestSubmissionForCategory() — unchanged
- getNilaiKategoriMap() — unchanged (reads from Penilaian records, which are now auto-populated)
- semuaKategoriSudahDinilai() — unchanged
- resolveKlasifikasi() — unchanged

### Test Coverage
- 24 tests, 45 assertions, all passing
- Tests cover: direct sum scoring, edge cases (no submissions, no validated, all invalid, Tidak answers), per-category breakdown, is_auto_calculated flag, syncHasilPenilaian with DB transaction, manual override via simpanNilaiKategori
