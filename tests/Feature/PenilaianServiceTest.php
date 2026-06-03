<?php

use App\Models\BadanPublik;
use App\Models\Jadwal;
use App\Models\JadwalPertanyaan;
use App\Models\Jawaban;
use App\Models\Kategori;
use App\Models\KlasifikasiPenilaian;
use App\Models\PertanyaanTemplate;
use App\Models\Submission;
use App\Models\User;
use App\Services\PenilaianService;

// ── Helpers ────────────────────────────────────────────────────────────────

function createTestSetup(bool $withKlasifikasi = false): array
{
    $user = User::factory()->create(['role' => 'dinas']);
    BadanPublik::create([
        'user_id' => $user->id,
        'nama_badan_publik' => 'Test BP',
        'website' => 'https://test.com',
        'telepon_badan_publik' => '0286-000000',
        'email_badan_publik' => 'test@example.com',
        'alamat' => 'Test Address',
        'telepon_responden' => '081000000000',
        'jabatan' => 'Staff',
    ]);

    $jadwal = Jadwal::create([
        'nama' => 'Monev TA 2026',
        'tahun' => 2026,
        'tanggal_mulai' => now()->subDay(),
        'tanggal_selesai' => now()->addYear(),
        'is_active' => true,
    ]);

    $kategori = Kategori::create(['nama' => 'Kategori A', 'judul' => 'A', 'deskripsi' => 'D']);
    $template = PertanyaanTemplate::create([
        'kategori_id' => $kategori->id,
        'teks_pertanyaan' => 'Test Q?',
        'definisi_operasional' => 'Test definition',
        'tipe_jawaban' => 'Ya/Tidak',
    ]);

    $jp1 = JadwalPertanyaan::create([
        'jadwal_id' => $jadwal->id,
        'pertanyaan_template_id' => $template->id,
        'teks_pertanyaan' => 'Q1?',
        'urutan' => 1,
        'tipe_jawaban' => 'Ya/Tidak',
        'skor_maks' => 10,
    ]);

    $jp2 = JadwalPertanyaan::create([
        'jadwal_id' => $jadwal->id,
        'pertanyaan_template_id' => $template->id,
        'teks_pertanyaan' => 'Q2?',
        'urutan' => 2,
        'tipe_jawaban' => 'Ya/Tidak',
        'skor_maks' => 20,
    ]);

    $jp3 = JadwalPertanyaan::create([
        'jadwal_id' => $jadwal->id,
        'pertanyaan_template_id' => $template->id,
        'teks_pertanyaan' => 'Q3?',
        'urutan' => 3,
        'tipe_jawaban' => 'Ya/Tidak',
        'skor_maks' => 30,
    ]);

    $submission = Submission::create([
        'user_id' => $user->id,
        'kategori_id' => $kategori->id,
        'jadwal_id' => $jadwal->id,
        'tanggal_submit' => now(),
    ]);

    if ($withKlasifikasi) {
        KlasifikasiPenilaian::create([
            'nama' => 'Baik',
            'min_nilai' => 50.00,
            'max_nilai' => 100.00,
            'urutan' => 1,
            'is_active' => true,
        ]);
    }

    return [
        'user' => $user,
        'jadwal' => $jadwal,
        'kategori' => $kategori,
        'submission' => $submission,
        'jp1' => $jp1,
        'jp2' => $jp2,
        'jp3' => $jp3,
    ];
}

// ── hitungNilaiAkhir: validated Ya answers ─────────────────────────────────

test('hitung_nilai_akhir with validated Ya answers sums skor_maks', function () {
    $s = createTestSetup();

    Jawaban::create([
        'submission_id' => $s['submission']->id,
        'jadwal_pertanyaan_id' => $s['jp1']->id,
        'jawaban' => 'Ya',
        'is_valid' => true,
    ]);

    Jawaban::create([
        'submission_id' => $s['submission']->id,
        'jadwal_pertanyaan_id' => $s['jp2']->id,
        'jawaban' => 'Ya',
        'is_valid' => true,
    ]);

    Jawaban::create([
        'submission_id' => $s['submission']->id,
        'jadwal_pertanyaan_id' => $s['jp3']->id,
        'jawaban' => 'Ya',
        'is_valid' => true,
    ]);

    $score = app(PenilaianService::class)->hitungNilaiAkhir($s['user']->id, $s['jadwal']->id);

    expect($score)->toBe(60.0); // 10 + 20 + 30
});

// ── hitungNilaiAkhir: invalidated answers contribute 0 ─────────────────────

test('hitung_nilai_akhir with invalidated answers contributes zero', function () {
    $s = createTestSetup();

    Jawaban::create([
        'submission_id' => $s['submission']->id,
        'jadwal_pertanyaan_id' => $s['jp1']->id,
        'jawaban' => 'Ya',
        'is_valid' => true,
    ]);

    Jawaban::create([
        'submission_id' => $s['submission']->id,
        'jadwal_pertanyaan_id' => $s['jp2']->id,
        'jawaban' => 'Ya',
        'is_valid' => false,
    ]);

    Jawaban::create([
        'submission_id' => $s['submission']->id,
        'jadwal_pertanyaan_id' => $s['jp3']->id,
        'jawaban' => 'Ya',
        'is_valid' => true,
    ]);

    $score = app(PenilaianService::class)->hitungNilaiAkhir($s['user']->id, $s['jadwal']->id);

    expect($score)->toBe(40.0); // 10 + 0 + 30
});

// ── hitungNilaiAkhir: unreviewed answers contribute 0 ──────────────────────

test('hitung_nilai_akhir with unreviewed answers contributes zero', function () {
    $s = createTestSetup();

    Jawaban::create([
        'submission_id' => $s['submission']->id,
        'jadwal_pertanyaan_id' => $s['jp1']->id,
        'jawaban' => 'Ya',
        'is_valid' => true,
    ]);

    Jawaban::create([
        'submission_id' => $s['submission']->id,
        'jadwal_pertanyaan_id' => $s['jp2']->id,
        'jawaban' => 'Ya',
        'is_valid' => null,
    ]);

    Jawaban::create([
        'submission_id' => $s['submission']->id,
        'jadwal_pertanyaan_id' => $s['jp3']->id,
        'jawaban' => 'Ya',
        'is_valid' => true,
    ]);

    $score = app(PenilaianService::class)->hitungNilaiAkhir($s['user']->id, $s['jadwal']->id);

    expect($score)->toBe(40.0); // 10 + 0 + 30
});

// ── hitungNilaiAkhir: all validated Ya (max score) ─────────────────────────

test('hitung_nilai_akhir all validated Ya returns max score', function () {
    $s = createTestSetup();

    Jawaban::create([
        'submission_id' => $s['submission']->id,
        'jadwal_pertanyaan_id' => $s['jp1']->id,
        'jawaban' => 'Ya',
        'is_valid' => true,
    ]);
    Jawaban::create([
        'submission_id' => $s['submission']->id,
        'jadwal_pertanyaan_id' => $s['jp2']->id,
        'jawaban' => 'Ya',
        'is_valid' => true,
    ]);
    Jawaban::create([
        'submission_id' => $s['submission']->id,
        'jadwal_pertanyaan_id' => $s['jp3']->id,
        'jawaban' => 'Ya',
        'is_valid' => true,
    ]);

    $score = app(PenilaianService::class)->hitungNilaiAkhir($s['user']->id, $s['jadwal']->id);

    expect($score)->toBe(60.0);
});

// ── hitungNilaiAkhir: all Tidak = 0 ────────────────────────────────────────

test('hitung_nilai_akhir all Tidak answers returns zero', function () {
    $s = createTestSetup();

    Jawaban::create([
        'submission_id' => $s['submission']->id,
        'jadwal_pertanyaan_id' => $s['jp1']->id,
        'jawaban' => 'Tidak',
        'is_valid' => true,
    ]);
    Jawaban::create([
        'submission_id' => $s['submission']->id,
        'jadwal_pertanyaan_id' => $s['jp2']->id,
        'jawaban' => 'Tidak',
        'is_valid' => true,
    ]);
    Jawaban::create([
        'submission_id' => $s['submission']->id,
        'jadwal_pertanyaan_id' => $s['jp3']->id,
        'jawaban' => 'Tidak',
        'is_valid' => true,
    ]);

    $score = app(PenilaianService::class)->hitungNilaiAkhir($s['user']->id, $s['jadwal']->id);

    expect($score)->toBe(0.0);
});

// ── hitungNilaiAkhir: no submissions = 0 ───────────────────────────────────

test('hitung_nilai_akhir with no submissions returns zero', function () {
    $user = User::factory()->create();
    $jadwal = Jadwal::create([
        'nama' => 'Test',
        'tahun' => 2026,
        'tanggal_mulai' => now()->subDay(),
        'tanggal_selesai' => now()->addYear(),
        'is_active' => true,
    ]);

    $score = app(PenilaianService::class)->hitungNilaiAkhir($user->id, $jadwal->id);

    expect($score)->toBe(0.0);
});

// ── syncHasilPenilaian: is_auto_calculated=true ────────────────────────────

test('sync_hasil_penilaian sets is_auto_calculated true', function () {
    $s = createTestSetup(true);

    Jawaban::create([
        'submission_id' => $s['submission']->id,
        'jadwal_pertanyaan_id' => $s['jp1']->id,
        'jawaban' => 'Ya',
        'is_valid' => true,
    ]);

    app(PenilaianService::class)->syncHasilPenilaian($s['user']->id, $s['jadwal']->id);

    $this->assertDatabaseHas('penilaians', [
        'submission_id' => $s['submission']->id,
        'is_auto_calculated' => 1,
    ]);

    $this->assertDatabaseHas('hasil_penilaians', [
        'user_id' => $s['user']->id,
        'jadwal_id' => $s['jadwal']->id,
        'nilai_akhir' => 10.00,
    ]);
});

// ── hitungNilaiPerKategori: per-category breakdown ─────────────────────────

test('per_category_breakdown returns correct category scores', function () {
    $user = User::factory()->create(['role' => 'dinas']);
    BadanPublik::create([
        'user_id' => $user->id,
        'nama_badan_publik' => 'Test BP',
        'website' => 'https://test.com',
        'telepon_badan_publik' => '0286-000000',
        'email_badan_publik' => 'test@example.com',
        'alamat' => 'Test Address',
        'telepon_responden' => '081000000000',
        'jabatan' => 'Staff',
    ]);

    $jadwal = Jadwal::create([
        'nama' => 'Monev TA 2026',
        'tahun' => 2026,
        'tanggal_mulai' => now()->subDay(),
        'tanggal_selesai' => now()->addYear(),
        'is_active' => true,
    ]);

    $kat1 = Kategori::create(['nama' => 'Kategori A', 'judul' => 'A', 'deskripsi' => 'D']);
    $kat2 = Kategori::create(['nama' => 'Kategori B', 'judul' => 'B', 'deskripsi' => 'D']);

    $tmpl1 = PertanyaanTemplate::create(['kategori_id' => $kat1->id, 'teks_pertanyaan' => 'Q?', 'tipe_jawaban' => 'Ya/Tidak']);
    $tmpl2 = PertanyaanTemplate::create(['kategori_id' => $kat2->id, 'teks_pertanyaan' => 'Q?', 'tipe_jawaban' => 'Ya/Tidak']);

    $jp1 = JadwalPertanyaan::create(['jadwal_id' => $jadwal->id, 'pertanyaan_template_id' => $tmpl1->id, 'teks_pertanyaan' => 'Q1?', 'urutan' => 1, 'tipe_jawaban' => 'Ya/Tidak', 'skor_maks' => 10]);
    $jp2 = JadwalPertanyaan::create(['jadwal_id' => $jadwal->id, 'pertanyaan_template_id' => $tmpl2->id, 'teks_pertanyaan' => 'Q2?', 'urutan' => 2, 'tipe_jawaban' => 'Ya/Tidak', 'skor_maks' => 20]);

    $sub1 = Submission::create(['user_id' => $user->id, 'kategori_id' => $kat1->id, 'jadwal_id' => $jadwal->id, 'tanggal_submit' => now()]);
    $sub2 = Submission::create(['user_id' => $user->id, 'kategori_id' => $kat2->id, 'jadwal_id' => $jadwal->id, 'tanggal_submit' => now()]);

    Jawaban::create(['submission_id' => $sub1->id, 'jadwal_pertanyaan_id' => $jp1->id, 'jawaban' => 'Ya', 'is_valid' => true]);
    Jawaban::create(['submission_id' => $sub2->id, 'jadwal_pertanyaan_id' => $jp2->id, 'jawaban' => 'Ya', 'is_valid' => false]);

    $breakdown = app(PenilaianService::class)->hitungNilaiPerKategori($user->id, $jadwal->id);

    $kategoriA = $breakdown->firstWhere('kategori_id', $kat1->id);
    $kategoriB = $breakdown->firstWhere('kategori_id', $kat2->id);

    expect($kategoriA['nilai'])->toBe(10.0);
    expect($kategoriB['nilai'])->toBe(0.0);
});
