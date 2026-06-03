<?php

use App\Models\BadanPublik;
use App\Models\HasilPenilaian;
use App\Models\Jadwal;
use App\Models\JadwalPertanyaan;
use App\Models\Kategori;
use App\Models\KlasifikasiPenilaian;
use App\Models\Penilaian;
use App\Models\PertanyaanTemplate;
use App\Models\Submission;
use App\Models\User;
use App\Services\StatistikService;

// ── getPerCategoryScores ───────────────────────────────────────────────────

test('get_per_category_scores returns correct averages', function () {
    $jadwal = Jadwal::create([
        'nama' => 'Monev TA 2026',
        'tahun' => 2026,
        'tanggal_mulai' => now()->subDay(),
        'tanggal_selesai' => now()->addYear(),
        'is_active' => true,
    ]);

    $kategori = Kategori::create(['nama' => 'Kategori A', 'judul' => 'A', 'deskripsi' => 'D']);
    $template = PertanyaanTemplate::create(['kategori_id' => $kategori->id, 'teks_pertanyaan' => 'Q?', 'tipe_jawaban' => 'Ya/Tidak']);
    $jp = JadwalPertanyaan::create([
        'jadwal_id' => $jadwal->id,
        'pertanyaan_template_id' => $template->id,
        'teks_pertanyaan' => 'Q?',
        'urutan' => 1,
        'tipe_jawaban' => 'Ya/Tidak',
        'skor_maks' => 50,
    ]);

    $user1 = User::factory()->create(['role' => 'dinas']);
    $user2 = User::factory()->create(['role' => 'dinas']);

    $sub1 = Submission::create(['user_id' => $user1->id, 'kategori_id' => $kategori->id, 'jadwal_id' => $jadwal->id, 'tanggal_submit' => now(), 'status_verifikasi' => 'Terverifikasi']);
    $sub2 = Submission::create(['user_id' => $user2->id, 'kategori_id' => $kategori->id, 'jadwal_id' => $jadwal->id, 'tanggal_submit' => now(), 'status_verifikasi' => 'Terverifikasi']);

    Penilaian::create(['submission_id' => $sub1->id, 'nilai' => 40]);
    Penilaian::create(['submission_id' => $sub2->id, 'nilai' => 30]);

    $scores = app(StatistikService::class)->getPerCategoryScores($jadwal->id);

    expect($scores)->toHaveCount(1);
    expect($scores->first()['kategori_nama'])->toBe('Kategori A');
    expect($scores->first()['average_score'])->toBe(35.0);
    expect($scores->first()['max_score'])->toBe(50);
});

// ── getOverallDistribution ─────────────────────────────────────────────────

test('get_overall_distribution returns correct klasifikasi counts', function () {
    $jadwal = Jadwal::create([
        'nama' => 'Monev TA 2026',
        'tahun' => 2026,
        'tanggal_mulai' => now()->subDay(),
        'tanggal_selesai' => now()->addYear(),
        'is_active' => true,
    ]);

    $klas1 = KlasifikasiPenilaian::create(['nama' => 'Baik', 'min_nilai' => 60, 'max_nilai' => 100, 'urutan' => 1, 'is_active' => true]);
    $klas2 = KlasifikasiPenilaian::create(['nama' => 'Cukup', 'min_nilai' => 30, 'max_nilai' => 59, 'urutan' => 2, 'is_active' => true]);
    $klas3 = KlasifikasiPenilaian::create(['nama' => 'Kurang', 'min_nilai' => 0, 'max_nilai' => 29, 'urutan' => 3, 'is_active' => true]);

    $user1 = User::factory()->create(['role' => 'dinas']);
    $user2 = User::factory()->create(['role' => 'dinas']);

    HasilPenilaian::create(['user_id' => $user1->id, 'jadwal_id' => $jadwal->id, 'nilai_akhir' => 80, 'klasifikasi_penilaian_id' => $klas1->id]);
    HasilPenilaian::create(['user_id' => $user2->id, 'jadwal_id' => $jadwal->id, 'nilai_akhir' => 80, 'klasifikasi_penilaian_id' => $klas1->id]);
    HasilPenilaian::create(['user_id' => User::factory()->create(['role' => 'dinas'])->id, 'jadwal_id' => $jadwal->id, 'nilai_akhir' => 50, 'klasifikasi_penilaian_id' => $klas2->id]);

    $distribution = app(StatistikService::class)->getOverallDistribution($jadwal->id);

    // 3 seed records from migration + 3 created in this test = 6 total
    expect($distribution)->toHaveCount(6);

    $baik = $distribution->firstWhere('nama', 'Baik');
    expect($baik['count'])->toBe(2);

    $cukup = $distribution->firstWhere('nama', 'Cukup');
    expect($cukup['count'])->toBe(1);

    $kurang = $distribution->firstWhere('nama', 'Kurang');
    expect($kurang['count'])->toBe(0);
});

// ── getTopBadanPublik ──────────────────────────────────────────────────────

test('get_top_badan_publik returns top 10 by nilai_akhir descending', function () {
    $jadwal = Jadwal::create([
        'nama' => 'Monev TA 2026',
        'tahun' => 2026,
        'tanggal_mulai' => now()->subDay(),
        'tanggal_selesai' => now()->addYear(),
        'is_active' => true,
    ]);

    $klas = KlasifikasiPenilaian::create(['nama' => 'Baik', 'min_nilai' => 0, 'max_nilai' => 100, 'urutan' => 1, 'is_active' => true]);

    $user1 = User::factory()->create(['role' => 'dinas']);
    BadanPublik::create(['user_id' => $user1->id, 'nama_badan_publik' => 'BP Top', 'website' => 'https://a.com', 'telepon_badan_publik' => '0286-000000', 'email_badan_publik' => 'a@test.com', 'alamat' => 'Addr', 'telepon_responden' => '081000000000', 'jabatan' => 'Staff']);
    $user2 = User::factory()->create(['role' => 'dinas']);
    BadanPublik::create(['user_id' => $user2->id, 'nama_badan_publik' => 'BP Mid', 'website' => 'https://b.com', 'telepon_badan_publik' => '0286-111111', 'email_badan_publik' => 'b@test.com', 'alamat' => 'Addr', 'telepon_responden' => '081111111111', 'jabatan' => 'Staff']);

    HasilPenilaian::create(['user_id' => $user1->id, 'jadwal_id' => $jadwal->id, 'nilai_akhir' => 95.5, 'klasifikasi_penilaian_id' => $klas->id]);
    HasilPenilaian::create(['user_id' => $user2->id, 'jadwal_id' => $jadwal->id, 'nilai_akhir' => 72.3, 'klasifikasi_penilaian_id' => $klas->id]);

    $top = app(StatistikService::class)->getTopBadanPublik($jadwal->id);

    expect($top)->toHaveCount(2);
    expect($top->first()['rank'])->toBe(1);
    expect($top->first()['nama_badan_publik'])->toBe('BP Top');
    expect($top->first()['nilai_akhir'])->toBe(95.5);
    expect($top->last()['nama_badan_publik'])->toBe('BP Mid');
    expect($top->last()['nilai_akhir'])->toBe(72.3);
});

// ── getVerificationProgress ────────────────────────────────────────────────

test('get_verification_progress returns correct verified total counts', function () {
    $jadwal = Jadwal::create([
        'nama' => 'Monev TA 2026',
        'tahun' => 2026,
        'tanggal_mulai' => now()->subDay(),
        'tanggal_selesai' => now()->addYear(),
        'is_active' => true,
    ]);

    $kategori = Kategori::create(['nama' => 'K', 'judul' => 'K', 'deskripsi' => 'D']);
    $user1 = User::factory()->create(['role' => 'dinas']);
    $user2 = User::factory()->create(['role' => 'dinas']);

    Submission::create(['user_id' => $user1->id, 'kategori_id' => $kategori->id, 'jadwal_id' => $jadwal->id, 'status_verifikasi' => 'Terverifikasi', 'tanggal_submit' => now()]);
    Submission::create(['user_id' => $user2->id, 'kategori_id' => $kategori->id, 'jadwal_id' => $jadwal->id, 'status_verifikasi' => 'Menunggu', 'tanggal_submit' => now()]);

    $progress = app(StatistikService::class)->getVerificationProgress($jadwal->id);

    expect($progress['total_submissions'])->toBe(2);
    expect($progress['verified_submissions'])->toBe(1);
    expect($progress['unverified_submissions'])->toBe(1);
    expect($progress['verification_percentage'])->toBe(50.0);
});
