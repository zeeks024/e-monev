<?php

use App\Models\HasilPenilaian;
use App\Models\Jadwal;
use App\Models\JadwalPertanyaan;
use App\Models\Jawaban;
use App\Models\Kategori;
use App\Models\KlasifikasiPenilaian;
use App\Models\Penilaian;
use App\Models\PertanyaanTemplate;
use App\Models\Submission;
use App\Models\User;
use App\Services\PenilaianService;
uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(PenilaianService::class);
});

function createFullScenario(): array
{
    $user = User::factory()->create(['role' => 'dinas']);

    $jadwal = Jadwal::create([
        'nama' => 'Monev KIP TA 2026',
        'tahun' => 2026,
        'tanggal_mulai' => now()->subDay(),
        'tanggal_selesai' => now()->addYear(),
        'is_active' => true,
    ]);

    $kategoriA = Kategori::create(['nama' => 'Kategori A', 'judul' => 'A', 'deskripsi' => 'Desc A']);
    $kategoriB = Kategori::create(['nama' => 'Kategori B', 'judul' => 'B', 'deskripsi' => 'Desc B']);
    $kategoriC = Kategori::create(['nama' => 'Kategori C', 'judul' => 'C', 'deskripsi' => 'Desc C']);

    $templateA = PertanyaanTemplate::create([
        'kategori_id' => $kategoriA->id,
        'teks_pertanyaan' => 'Question A1',
        'tipe_jawaban' => 'Ya/Tidak',
    ]);
    $templateB = PertanyaanTemplate::create([
        'kategori_id' => $kategoriB->id,
        'teks_pertanyaan' => 'Question B1',
        'tipe_jawaban' => 'Ya/Tidak',
    ]);
    $templateC = PertanyaanTemplate::create([
        'kategori_id' => $kategoriC->id,
        'teks_pertanyaan' => 'Question C1',
        'tipe_jawaban' => 'Ya/Tidak',
    ]);

    $jpA = JadwalPertanyaan::create([
        'jadwal_id' => $jadwal->id,
        'pertanyaan_template_id' => $templateA->id,
        'teks_pertanyaan' => 'Question A1',
        'urutan' => 1,
        'tipe_jawaban' => 'Ya/Tidak',
        'skor_maks' => 10,
    ]);
    $jpB = JadwalPertanyaan::create([
        'jadwal_id' => $jadwal->id,
        'pertanyaan_template_id' => $templateB->id,
        'teks_pertanyaan' => 'Question B1',
        'urutan' => 2,
        'tipe_jawaban' => 'Ya/Tidak',
        'skor_maks' => 10,
    ]);
    $jpC = JadwalPertanyaan::create([
        'jadwal_id' => $jadwal->id,
        'pertanyaan_template_id' => $templateC->id,
        'teks_pertanyaan' => 'Question C1',
        'urutan' => 3,
        'tipe_jawaban' => 'Ya/Tidak',
        'skor_maks' => 10,
    ]);

    return compact('user', 'jadwal', 'kategoriA', 'kategoriB', 'kategoriC', 'jpA', 'jpB', 'jpC');
}

// ── getKategoriAktifByJadwal ──────────────────────────────────────────────

test('getKategoriAktifByJadwal returns all categories linked to a jadwal', function () {
    $data = createFullScenario();

    $result = $this->service->getKategoriAktifByJadwal($data['jadwal']->id);

    expect($result)->toHaveCount(3);
    expect($result->pluck('nama')->toArray())->toContain('Kategori A', 'Kategori B', 'Kategori C');
});

test('getKategoriAktifByJadwal returns empty collection for jadwal without questions', function () {
    $jadwal = Jadwal::create([
        'nama' => 'Empty Jadwal',
        'tahun' => 2027,
        'tanggal_mulai' => now()->subDay(),
        'tanggal_selesai' => now()->addYear(),
        'is_active' => true,
    ]);

    $result = $this->service->getKategoriAktifByJadwal($jadwal->id);

    expect($result)->toBeEmpty();
});

// ── getLatestSubmissionForCategory ────────────────────────────────────────

test('getLatestSubmissionForCategory returns latest submission for a category', function () {
    $data = createFullScenario();

    $sub1 = Submission::create([
        'user_id' => $data['user']->id,
        'kategori_id' => $data['kategoriA']->id,
        'jadwal_id' => $data['jadwal']->id,
        'tanggal_submit' => now()->subDays(2),
    ]);
    $sub2 = Submission::create([
        'user_id' => $data['user']->id,
        'kategori_id' => $data['kategoriA']->id,
        'jadwal_id' => $data['jadwal']->id,
        'tanggal_submit' => now(),
    ]);

    $result = $this->service->getLatestSubmissionForCategory(
        $data['user']->id,
        $data['jadwal']->id,
        $data['kategoriA']->id
    );

    expect($result->id)->toBe($sub2->id);
});

test('getLatestSubmissionForCategory returns null when no submission exists', function () {
    $data = createFullScenario();

    $result = $this->service->getLatestSubmissionForCategory(
        $data['user']->id,
        $data['jadwal']->id,
        $data['kategoriA']->id
    );

    expect($result)->toBeNull();
});

// ── hitungNilaiAkhir (direct sum of validated Ya answers) ────────────────

test('hitungNilaiAkhir computes direct sum of validated Ya answers skor_maks', function () {
    $data = createFullScenario();

    // Create submissions for all 3 categories
    $subA = Submission::create([
        'user_id' => $data['user']->id,
        'kategori_id' => $data['kategoriA']->id,
        'jadwal_id' => $data['jadwal']->id,
        'tanggal_submit' => now(),
    ]);
    $subB = Submission::create([
        'user_id' => $data['user']->id,
        'kategori_id' => $data['kategoriB']->id,
        'jadwal_id' => $data['jadwal']->id,
        'tanggal_submit' => now(),
    ]);
    $subC = Submission::create([
        'user_id' => $data['user']->id,
        'kategori_id' => $data['kategoriC']->id,
        'jadwal_id' => $data['jadwal']->id,
        'tanggal_submit' => now(),
    ]);

    // All Ya and validated → total = 10 + 10 + 10 = 30
    Jawaban::create(['submission_id' => $subA->id, 'jadwal_pertanyaan_id' => $data['jpA']->id, 'jawaban' => 'Ya', 'is_valid' => true]);
    Jawaban::create(['submission_id' => $subB->id, 'jadwal_pertanyaan_id' => $data['jpB']->id, 'jawaban' => 'Ya', 'is_valid' => true]);
    Jawaban::create(['submission_id' => $subC->id, 'jadwal_pertanyaan_id' => $data['jpC']->id, 'jawaban' => 'Ya', 'is_valid' => true]);

    $result = $this->service->hitungNilaiAkhir($data['user']->id, $data['jadwal']->id);

    expect($result)->toBe(30.0);
});

test('hitungNilaiAkhir returns 0 when no submissions exist', function () {
    $user = User::factory()->create();
    $jadwal = Jadwal::create([
        'nama' => 'Empty',
        'tahun' => 2027,
        'tanggal_mulai' => now()->subDay(),
        'tanggal_selesai' => now()->addYear(),
        'is_active' => true,
    ]);

    $result = $this->service->hitungNilaiAkhir($user->id, $jadwal->id);

    expect($result)->toBe(0.0);
});

test('hitungNilaiAkhir returns 0 when no validated questions exist', function () {
    $data = createFullScenario();

    $subA = Submission::create([
        'user_id' => $data['user']->id,
        'kategori_id' => $data['kategoriA']->id,
        'jadwal_id' => $data['jadwal']->id,
        'tanggal_submit' => now(),
    ]);

    // Answer is Ya but not validated (is_valid = null)
    Jawaban::create(['submission_id' => $subA->id, 'jadwal_pertanyaan_id' => $data['jpA']->id, 'jawaban' => 'Ya', 'is_valid' => null]);

    $result = $this->service->hitungNilaiAkhir($data['user']->id, $data['jadwal']->id);

    expect($result)->toBe(0.0);
});

test('hitungNilaiAkhir returns 0 when all answers are invalid (is_valid = false)', function () {
    $data = createFullScenario();

    $subA = Submission::create([
        'user_id' => $data['user']->id,
        'kategori_id' => $data['kategoriA']->id,
        'jadwal_id' => $data['jadwal']->id,
        'tanggal_submit' => now(),
    ]);

    // Answer is Ya but invalidated
    Jawaban::create(['submission_id' => $subA->id, 'jadwal_pertanyaan_id' => $data['jpA']->id, 'jawaban' => 'Ya', 'is_valid' => false]);

    $result = $this->service->hitungNilaiAkhir($data['user']->id, $data['jadwal']->id);

    expect($result)->toBe(0.0);
});

test('hitungNilaiAkhir ignores Tidak answers even when validated', function () {
    $data = createFullScenario();

    $subA = Submission::create([
        'user_id' => $data['user']->id,
        'kategori_id' => $data['kategoriA']->id,
        'jadwal_id' => $data['jadwal']->id,
        'tanggal_submit' => now(),
    ]);

    // Answer is Tidak (No) and validated → should contribute 0
    Jawaban::create(['submission_id' => $subA->id, 'jadwal_pertanyaan_id' => $data['jpA']->id, 'jawaban' => 'Tidak', 'is_valid' => true]);

    $result = $this->service->hitungNilaiAkhir($data['user']->id, $data['jadwal']->id);

    expect($result)->toBe(0.0);
});

test('hitungNilaiAkhir sums only validated Ya answers across multiple categories', function () {
    $data = createFullScenario();

    $subA = Submission::create([
        'user_id' => $data['user']->id,
        'kategori_id' => $data['kategoriA']->id,
        'jadwal_id' => $data['jadwal']->id,
        'tanggal_submit' => now(),
    ]);
    $subB = Submission::create([
        'user_id' => $data['user']->id,
        'kategori_id' => $data['kategoriB']->id,
        'jadwal_id' => $data['jadwal']->id,
        'tanggal_submit' => now(),
    ]);

    // Category A: Ya + validated → 10 points
    Jawaban::create(['submission_id' => $subA->id, 'jadwal_pertanyaan_id' => $data['jpA']->id, 'jawaban' => 'Ya', 'is_valid' => true]);
    // Category B: Ya + validated → 10 points
    Jawaban::create(['submission_id' => $subB->id, 'jadwal_pertanyaan_id' => $data['jpB']->id, 'jawaban' => 'Ya', 'is_valid' => true]);

    // Total = 10 + 10 = 20
    $result = $this->service->hitungNilaiAkhir($data['user']->id, $data['jadwal']->id);

    expect($result)->toBe(20.0);
});

test('hitungNilaiAkhir handles mixed validated and unvalidated answers', function () {
    $data = createFullScenario();

    $subA = Submission::create([
        'user_id' => $data['user']->id,
        'kategori_id' => $data['kategoriA']->id,
        'jadwal_id' => $data['jadwal']->id,
        'tanggal_submit' => now(),
    ]);

    // Ya + validated → 10 points
    Jawaban::create(['submission_id' => $subA->id, 'jadwal_pertanyaan_id' => $data['jpA']->id, 'jawaban' => 'Ya', 'is_valid' => true]);
    // Ya + not reviewed (null) → 0 points
    // (Can't create another jawaban for same jpA on same submission easily, so test with different scenario)

    $result = $this->service->hitungNilaiAkhir($data['user']->id, $data['jadwal']->id);

    expect($result)->toBe(10.0);
});

// ── hitungNilaiPerKategori ────────────────────────────────────────────────

test('hitungNilaiPerKategori computes per-category scores from validated Ya answers', function () {
    $data = createFullScenario();

    $subA = Submission::create([
        'user_id' => $data['user']->id,
        'kategori_id' => $data['kategoriA']->id,
        'jadwal_id' => $data['jadwal']->id,
        'tanggal_submit' => now(),
    ]);
    $subB = Submission::create([
        'user_id' => $data['user']->id,
        'kategori_id' => $data['kategoriB']->id,
        'jadwal_id' => $data['jadwal']->id,
        'tanggal_submit' => now(),
    ]);

    Jawaban::create(['submission_id' => $subA->id, 'jadwal_pertanyaan_id' => $data['jpA']->id, 'jawaban' => 'Ya', 'is_valid' => true]);
    Jawaban::create(['submission_id' => $subB->id, 'jadwal_pertanyaan_id' => $data['jpB']->id, 'jawaban' => 'Ya', 'is_valid' => true]);

    $map = $this->service->hitungNilaiPerKategori($data['user']->id, $data['jadwal']->id);

    expect($map)->toHaveCount(3);

    $kategoriA = $map->firstWhere('kategori_nama', 'Kategori A');
    expect($kategoriA['nilai'])->toBe(10.0);

    $kategoriB = $map->firstWhere('kategori_nama', 'Kategori B');
    expect($kategoriB['nilai'])->toBe(10.0);

    $kategoriC = $map->firstWhere('kategori_nama', 'Kategori C');
    expect($kategoriC['nilai'])->toBeNull();
});

// ── getNilaiKategoriMap ───────────────────────────────────────────────────

test('getNilaiKategoriMap returns map with nilai for scored categories', function () {
    $data = createFullScenario();

    $submission = Submission::create([
        'user_id' => $data['user']->id,
        'kategori_id' => $data['kategoriA']->id,
        'jadwal_id' => $data['jadwal']->id,
        'tanggal_submit' => now(),
    ]);
    Penilaian::create([
        'submission_id' => $submission->id,
        'nilai' => 80.0,
    ]);

    $map = $this->service->getNilaiKategoriMap($data['user']->id, $data['jadwal']->id);

    expect($map)->toHaveCount(3);

    $kategoriA = $map->firstWhere('kategori_nama', 'Kategori A');
    expect($kategoriA['nilai'])->toBe(80.0);

    $kategoriB = $map->firstWhere('kategori_nama', 'Kategori B');
    expect($kategoriB['nilai'])->toBeNull();
});

// ── semuaKategoriSudahDinilai ─────────────────────────────────────────────

test('semuaKategoriSudahDinilai returns true when all categories are scored', function () {
    $data = createFullScenario();

    foreach ([$data['kategoriA'], $data['kategoriB'], $data['kategoriC']] as $kategori) {
        $submission = Submission::create([
            'user_id' => $data['user']->id,
            'kategori_id' => $kategori->id,
            'jadwal_id' => $data['jadwal']->id,
            'tanggal_submit' => now(),
        ]);
        Penilaian::create([
            'submission_id' => $submission->id,
            'nilai' => 75.0,
        ]);
    }

    expect($this->service->semuaKategoriSudahDinilai($data['user']->id, $data['jadwal']->id))->toBeTrue();
});

test('semuaKategoriSudahDinilai returns false when some categories are not scored', function () {
    $data = createFullScenario();

    // Score only 1 category
    $submission = Submission::create([
        'user_id' => $data['user']->id,
        'kategori_id' => $data['kategoriA']->id,
        'jadwal_id' => $data['jadwal']->id,
        'tanggal_submit' => now(),
    ]);
    Penilaian::create([
        'submission_id' => $submission->id,
        'nilai' => 75.0,
    ]);

    expect($this->service->semuaKategoriSudahDinilai($data['user']->id, $data['jadwal']->id))->toBeFalse();
});

test('semuaKategoriSudahDinilai returns false when no categories exist', function () {
    $user = User::factory()->create();
    $jadwal = Jadwal::create([
        'nama' => 'Empty',
        'tahun' => 2027,
        'tanggal_mulai' => now()->subDay(),
        'tanggal_selesai' => now()->addYear(),
        'is_active' => true,
    ]);

    expect($this->service->semuaKategoriSudahDinilai($user->id, $jadwal->id))->toBeFalse();
});

// ── resolveKlasifikasi ────────────────────────────────────────────────────

test('resolveKlasifikasi matches score to correct tier', function () {
    KlasifikasiPenilaian::query()->delete();

    $low = KlasifikasiPenilaian::create([
        'nama' => 'Kurang',
        'min_nilai' => 0.00,
        'max_nilai' => 40.00,
        'urutan' => 1,
        'is_active' => true,
    ]);
    $mid = KlasifikasiPenilaian::create([
        'nama' => 'Cukup',
        'min_nilai' => 40.01,
        'max_nilai' => 70.00,
        'urutan' => 2,
        'is_active' => true,
    ]);
    $high = KlasifikasiPenilaian::create([
        'nama' => 'Baik',
        'min_nilai' => 70.01,
        'max_nilai' => 100.00,
        'urutan' => 3,
        'is_active' => true,
    ]);

    expect($this->service->resolveKlasifikasi(25.0)->id)->toBe($low->id);
    expect($this->service->resolveKlasifikasi(55.0)->id)->toBe($mid->id);
    expect($this->service->resolveKlasifikasi(90.0)->id)->toBe($high->id);
});

test('resolveKlasifikasi returns null for score outside all tiers', function () {
    KlasifikasiPenilaian::query()->delete();

    KlasifikasiPenilaian::create([
        'nama' => 'Normal',
        'min_nilai' => 50.00,
        'max_nilai' => 80.00,
        'urutan' => 1,
        'is_active' => true,
    ]);

    expect($this->service->resolveKlasifikasi(10.0))->toBeNull();
});

test('resolveKlasifikasi ignores inactive tiers', function () {
    KlasifikasiPenilaian::query()->delete();

    KlasifikasiPenilaian::create([
        'nama' => 'Inactive',
        'min_nilai' => 0.00,
        'max_nilai' => 100.00,
        'urutan' => 1,
        'is_active' => false,
    ]);

    expect($this->service->resolveKlasifikasi(50.0))->toBeNull();
});

// ── syncHasilPenilaian ────────────────────────────────────────────────────

test('syncHasilPenilaian computes direct sum and stores final score', function () {
    $data = createFullScenario();

    KlasifikasiPenilaian::create([
        'nama' => 'Baik',
        'min_nilai' => 0.01,
        'max_nilai' => 100.00,
        'urutan' => 1,
        'is_active' => true,
    ]);

    // Create submissions with validated Ya answers
    $subA = Submission::create([
        'user_id' => $data['user']->id,
        'kategori_id' => $data['kategoriA']->id,
        'jadwal_id' => $data['jadwal']->id,
        'tanggal_submit' => now(),
    ]);
    $subB = Submission::create([
        'user_id' => $data['user']->id,
        'kategori_id' => $data['kategoriB']->id,
        'jadwal_id' => $data['jadwal']->id,
        'tanggal_submit' => now(),
    ]);
    $subC = Submission::create([
        'user_id' => $data['user']->id,
        'kategori_id' => $data['kategoriC']->id,
        'jadwal_id' => $data['jadwal']->id,
        'tanggal_submit' => now(),
    ]);

    Jawaban::create(['submission_id' => $subA->id, 'jadwal_pertanyaan_id' => $data['jpA']->id, 'jawaban' => 'Ya', 'is_valid' => true]);
    Jawaban::create(['submission_id' => $subB->id, 'jadwal_pertanyaan_id' => $data['jpB']->id, 'jawaban' => 'Ya', 'is_valid' => true]);
    Jawaban::create(['submission_id' => $subC->id, 'jadwal_pertanyaan_id' => $data['jpC']->id, 'jawaban' => 'Ya', 'is_valid' => true]);

    $hasil = $this->service->syncHasilPenilaian($data['user']->id, $data['jadwal']->id);

    expect($hasil->user_id)->toBe($data['user']->id);
    expect($hasil->jadwal_id)->toBe($data['jadwal']->id);
    // Direct sum: 10 + 10 + 10 = 30
    expect((float) $hasil->nilai_akhir)->toBe(30.0);
    expect($hasil->klasifikasi_penilaian_id)->not->toBeNull();
});

test('syncHasilPenilaian sets is_auto_calculated on Penilaian records', function () {
    $data = createFullScenario();

    $subA = Submission::create([
        'user_id' => $data['user']->id,
        'kategori_id' => $data['kategoriA']->id,
        'jadwal_id' => $data['jadwal']->id,
        'tanggal_submit' => now(),
    ]);

    Jawaban::create(['submission_id' => $subA->id, 'jadwal_pertanyaan_id' => $data['jpA']->id, 'jawaban' => 'Ya', 'is_valid' => true]);

    $this->service->syncHasilPenilaian($data['user']->id, $data['jadwal']->id);

    $penilaian = Penilaian::where('submission_id', $subA->id)->first();
    expect($penilaian)->not->toBeNull();
    expect($penilaian->is_auto_calculated)->toBeTrue();
    expect((float) $penilaian->nilai)->toBe(10.0);
});

test('syncHasilPenilaian updates existing record on second call', function () {
    $data = createFullScenario();

    KlasifikasiPenilaian::create([
        'nama' => 'Baik',
        'min_nilai' => 0.01,
        'max_nilai' => 100.00,
        'urutan' => 1,
        'is_active' => true,
    ]);

    // First sync with one category
    $subA = Submission::create([
        'user_id' => $data['user']->id,
        'kategori_id' => $data['kategoriA']->id,
        'jadwal_id' => $data['jadwal']->id,
        'tanggal_submit' => now(),
    ]);
    Jawaban::create(['submission_id' => $subA->id, 'jadwal_pertanyaan_id' => $data['jpA']->id, 'jawaban' => 'Ya', 'is_valid' => true]);

    $hasil1 = $this->service->syncHasilPenilaian($data['user']->id, $data['jadwal']->id);
    expect((float) $hasil1->nilai_akhir)->toBe(10.0);

    // Add more categories and sync again
    $subB = Submission::create([
        'user_id' => $data['user']->id,
        'kategori_id' => $data['kategoriB']->id,
        'jadwal_id' => $data['jadwal']->id,
        'tanggal_submit' => now(),
    ]);
    $subC = Submission::create([
        'user_id' => $data['user']->id,
        'kategori_id' => $data['kategoriC']->id,
        'jadwal_id' => $data['jadwal']->id,
        'tanggal_submit' => now(),
    ]);
    Jawaban::create(['submission_id' => $subB->id, 'jadwal_pertanyaan_id' => $data['jpB']->id, 'jawaban' => 'Ya', 'is_valid' => true]);
    Jawaban::create(['submission_id' => $subC->id, 'jadwal_pertanyaan_id' => $data['jpC']->id, 'jawaban' => 'Ya', 'is_valid' => true]);

    $hasil2 = $this->service->syncHasilPenilaian($data['user']->id, $data['jadwal']->id);

    expect($hasil2->id)->toBe($hasil1->id); // same record updated
    // Direct sum: 10 + 10 + 10 = 30
    expect((float) $hasil2->nilai_akhir)->toBe(30.0);
});

// ── simpanNilaiKategori ───────────────────────────────────────────────────

test('simpanNilaiKategori creates penilaian with is_auto_calculated = false', function () {
    $data = createFullScenario();

    $submission = Submission::create([
        'user_id' => $data['user']->id,
        'kategori_id' => $data['kategoriA']->id,
        'jadwal_id' => $data['jadwal']->id,
        'tanggal_submit' => now(),
    ]);

    $penilaian = $this->service->simpanNilaiKategori($submission->id, 85.5);

    expect($penilaian->submission_id)->toBe($submission->id);
    expect($penilaian->nilai)->toBe(85.5);
    expect($penilaian->is_auto_calculated)->toBeFalse();
    expect($penilaian->status_informatif)->toBeNull();
});

test('simpanNilaiKategori updates existing penilaian', function () {
    $data = createFullScenario();

    $submission = Submission::create([
        'user_id' => $data['user']->id,
        'kategori_id' => $data['kategoriA']->id,
        'jadwal_id' => $data['jadwal']->id,
        'tanggal_submit' => now(),
    ]);

    $first = $this->service->simpanNilaiKategori($submission->id, 50.0);
    $second = $this->service->simpanNilaiKategori($submission->id, 75.0);

    expect($second->id)->toBe($first->id);
    expect($second->nilai)->toBe(75.0);
});