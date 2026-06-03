<?php

use App\Models\HasilPenilaian;
use App\Models\Jadwal;
use App\Models\JadwalPertanyaan;
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

    JadwalPertanyaan::create([
        'jadwal_id' => $jadwal->id,
        'pertanyaan_template_id' => $templateA->id,
        'teks_pertanyaan' => 'Question A1',
        'urutan' => 1,
        'tipe_jawaban' => 'Ya/Tidak',
        'skor_maks' => 10,
    ]);
    JadwalPertanyaan::create([
        'jadwal_id' => $jadwal->id,
        'pertanyaan_template_id' => $templateB->id,
        'teks_pertanyaan' => 'Question B1',
        'urutan' => 2,
        'tipe_jawaban' => 'Ya/Tidak',
        'skor_maks' => 10,
    ]);
    JadwalPertanyaan::create([
        'jadwal_id' => $jadwal->id,
        'pertanyaan_template_id' => $templateC->id,
        'teks_pertanyaan' => 'Question C1',
        'urutan' => 3,
        'tipe_jawaban' => 'Ya/Tidak',
        'skor_maks' => 10,
    ]);

    return compact('user', 'jadwal', 'kategoriA', 'kategoriB', 'kategoriC');
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

// ── hitungNilaiAkhir ──────────────────────────────────────────────────────

test('hitungNilaiAkhir calculates correct average of category scores', function () {
    $data = createFullScenario();

    // Score only 2 out of 3 categories
    foreach ([$data['kategoriA'], $data['kategoriB']] as $kategori) {
        $submission = Submission::create([
            'user_id' => $data['user']->id,
            'kategori_id' => $kategori->id,
            'jadwal_id' => $data['jadwal']->id,
            'tanggal_submit' => now(),
        ]);
        Penilaian::create([
            'submission_id' => $submission->id,
            'nilai' => 80.0,
        ]);
    }

    // Average: (80 + 80 + 0) / 3 = 53.33
    $result = $this->service->hitungNilaiAkhir($data['user']->id, $data['jadwal']->id);

    expect($result)->toBe(53.33);
});

test('hitungNilaiAkhir returns 0.0 when no categories exist', function () {
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

test('hitungNilaiAkhir treats unscored categories as zero', function () {
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
        'nilai' => 90.0,
    ]);

    // Average: (90 + 0 + 0) / 3 = 30.0
    $result = $this->service->hitungNilaiAkhir($data['user']->id, $data['jadwal']->id);

    expect($result)->toBe(30.0);
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

test('syncHasilPenilaian aggregates and stores final score', function () {
    $data = createFullScenario();

    KlasifikasiPenilaian::create([
        'nama' => 'Baik',
        'min_nilai' => 50.01,
        'max_nilai' => 100.00,
        'urutan' => 1,
        'is_active' => true,
    ]);

    foreach ([$data['kategoriA'], $data['kategoriB'], $data['kategoriC']] as $kategori) {
        $submission = Submission::create([
            'user_id' => $data['user']->id,
            'kategori_id' => $kategori->id,
            'jadwal_id' => $data['jadwal']->id,
            'tanggal_submit' => now(),
        ]);
        Penilaian::create([
            'submission_id' => $submission->id,
            'nilai' => 90.0,
        ]);
    }

    $hasil = $this->service->syncHasilPenilaian($data['user']->id, $data['jadwal']->id);

    expect($hasil->user_id)->toBe($data['user']->id);
    expect($hasil->jadwal_id)->toBe($data['jadwal']->id);
    expect((float) $hasil->nilai_akhir)->toBe(90.0);
    expect($hasil->klasifikasi_penilaian_id)->not->toBeNull();
});

test('syncHasilPenilaian updates existing record on second call', function () {
    $data = createFullScenario();

    // First sync with score 60
    $sub = Submission::create([
        'user_id' => $data['user']->id,
        'kategori_id' => $data['kategoriA']->id,
        'jadwal_id' => $data['jadwal']->id,
        'tanggal_submit' => now(),
    ]);
    Penilaian::create(['submission_id' => $sub->id, 'nilai' => 60.0]);

    $hasil1 = $this->service->syncHasilPenilaian($data['user']->id, $data['jadwal']->id);
    expect((float) $hasil1->nilai_akhir)->toBe(20.0); // 60/3 = 20

    // Add more scores and sync again
    foreach ([$data['kategoriB'], $data['kategoriC']] as $kategori) {
        $sub = Submission::create([
            'user_id' => $data['user']->id,
            'kategori_id' => $kategori->id,
            'jadwal_id' => $data['jadwal']->id,
            'tanggal_submit' => now(),
        ]);
        Penilaian::create(['submission_id' => $sub->id, 'nilai' => 60.0]);
    }

    $hasil2 = $this->service->syncHasilPenilaian($data['user']->id, $data['jadwal']->id);

    expect($hasil2->id)->toBe($hasil1->id); // same record
    expect((float) $hasil2->nilai_akhir)->toBe(60.0); // (60+60+60)/3 = 60
});

// ── simpanNilaiKategori ───────────────────────────────────────────────────

test('simpanNilaiKategori creates penilaian for a submission', function () {
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
