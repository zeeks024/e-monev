<?php

use App\Models\BadanPublik;
use App\Models\Jadwal;
use App\Models\JadwalPertanyaan;
use App\Models\Kategori;
use App\Models\KlasifikasiPenilaian;
use App\Models\PertanyaanTemplate;
use App\Models\Submission;
use App\Models\User;
uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

// ── Jadwal scopeActive ────────────────────────────────────────────────────

test('Jadwal scopeActive returns only active schedules within date range', function () {
    $active = Jadwal::create([
        'nama' => 'Active Schedule',
        'tahun' => 2026,
        'tanggal_mulai' => now()->subDay(),
        'tanggal_selesai' => now()->addYear(),
        'is_active' => true,
    ]);

    Jadwal::create([
        'nama' => 'Inactive Flag',
        'tahun' => 2025,
        'tanggal_mulai' => now()->subDay(),
        'tanggal_selesai' => now()->addYear(),
        'is_active' => false,
    ]);

    Jadwal::create([
        'nama' => 'Future Schedule',
        'tahun' => 2027,
        'tanggal_mulai' => now()->addMonth(),
        'tanggal_selesai' => now()->addYears(2),
        'is_active' => true,
    ]);

    Jadwal::create([
        'nama' => 'Past Schedule',
        'tahun' => 2024,
        'tanggal_mulai' => now()->subYears(2),
        'tanggal_selesai' => now()->subMonth(),
        'is_active' => true,
    ]);

    $activeSchedules = Jadwal::active()->get();

    expect($activeSchedules)->toHaveCount(1);
    expect($activeSchedules->first()->id)->toBe($active->id);
});

// ── KlasifikasiPenilaian scopeActive ──────────────────────────────────────

test('KlasifikasiPenilaian scopeActive returns only active tiers', function () {
    KlasifikasiPenilaian::query()->delete();

    $active = KlasifikasiPenilaian::create([
        'nama' => 'Baik',
        'min_nilai' => 70.00,
        'max_nilai' => 100.00,
        'urutan' => 1,
        'is_active' => true,
    ]);

    KlasifikasiPenilaian::create([
        'nama' => 'Inactive Tier',
        'min_nilai' => 0.00,
        'max_nilai' => 50.00,
        'urutan' => 2,
        'is_active' => false,
    ]);

    $activeTiers = KlasifikasiPenilaian::active()->get();

    expect($activeTiers)->toHaveCount(1);
    expect($activeTiers->first()->id)->toBe($active->id);
});

// ── Kategori hasMany PertanyaanTemplate ───────────────────────────────────

test('Kategori has many PertanyaanTemplate', function () {
    $kategori = Kategori::create([
        'nama' => 'Test Kategori',
        'judul' => 'Test',
        'deskripsi' => 'Description',
    ]);

    PertanyaanTemplate::create([
        'kategori_id' => $kategori->id,
        'teks_pertanyaan' => 'Question 1',
        'tipe_jawaban' => 'Ya/Tidak',
    ]);
    PertanyaanTemplate::create([
        'kategori_id' => $kategori->id,
        'teks_pertanyaan' => 'Question 2',
        'tipe_jawaban' => 'Ya/Tidak',
    ]);

    expect($kategori->pertanyaanTemplates)->toHaveCount(2);
    expect($kategori->pertanyaanTemplates->pluck('teks_pertanyaan')->toArray())
        ->toContain('Question 1', 'Question 2');
});

// ── Jadwal hasMany JadwalPertanyaan ordered by urutan ─────────────────────

test('Jadwal has many JadwalPertanyaan ordered by urutan', function () {
    $jadwal = Jadwal::create([
        'nama' => 'Test Jadwal',
        'tahun' => 2026,
        'tanggal_mulai' => now()->subDay(),
        'tanggal_selesai' => now()->addYear(),
        'is_active' => true,
    ]);

    $kategori = Kategori::create(['nama' => 'K', 'judul' => 'K', 'deskripsi' => 'D']);
    $template1 = PertanyaanTemplate::create(['kategori_id' => $kategori->id, 'teks_pertanyaan' => 'Q1', 'tipe_jawaban' => 'Ya/Tidak']);
    $template2 = PertanyaanTemplate::create(['kategori_id' => $kategori->id, 'teks_pertanyaan' => 'Q2', 'tipe_jawaban' => 'Ya/Tidak']);
    $template3 = PertanyaanTemplate::create(['kategori_id' => $kategori->id, 'teks_pertanyaan' => 'Q3', 'tipe_jawaban' => 'Ya/Tidak']);

    // Create in non-sequential order
    JadwalPertanyaan::create([
        'jadwal_id' => $jadwal->id,
        'pertanyaan_template_id' => $template3->id,
        'teks_pertanyaan' => 'Q3',
        'urutan' => 3,
        'tipe_jawaban' => 'Ya/Tidak',
    ]);
    JadwalPertanyaan::create([
        'jadwal_id' => $jadwal->id,
        'pertanyaan_template_id' => $template1->id,
        'teks_pertanyaan' => 'Q1',
        'urutan' => 1,
        'tipe_jawaban' => 'Ya/Tidak',
    ]);
    JadwalPertanyaan::create([
        'jadwal_id' => $jadwal->id,
        'pertanyaan_template_id' => $template2->id,
        'teks_pertanyaan' => 'Q2',
        'urutan' => 2,
        'tipe_jawaban' => 'Ya/Tidak',
    ]);

    $pertanyaans = $jadwal->jadwalPertanyaans;

    expect($pertanyaans)->toHaveCount(3);
    expect($pertanyaans->pluck('urutan')->toArray())->toBe([1, 2, 3]);
    expect($pertanyaans->pluck('teks_pertanyaan')->toArray())->toBe(['Q1', 'Q2', 'Q3']);
});

// ── User hasOne BadanPublik ───────────────────────────────────────────────

test('User has one BadanPublik', function () {
    $user = User::factory()->create();

    $badanPublik = BadanPublik::create([
        'user_id' => $user->id,
        'nama_badan_publik' => 'Test Badan Publik',
        'website' => 'https://example.com',
        'telepon_badan_publik' => '0286-123456',
        'email_badan_publik' => 'info@example.com',
        'alamat' => 'Test Address',
        'telepon_responden' => '0286-654321',
        'jabatan' => 'Kepala',
    ]);

    expect($user->badanPublik->id)->toBe($badanPublik->id);
    expect($user->badanPublik->nama_badan_publik)->toBe('Test Badan Publik');
});

test('User without BadanPublik returns null relation', function () {
    $user = User::factory()->create();

    expect($user->badanPublik)->toBeNull();
});

// ── BadanPublik belongsTo User ────────────────────────────────────────────

test('BadanPublik belongs to User', function () {
    $user = User::factory()->create(['name' => 'Test User']);

    $badanPublik = BadanPublik::create([
        'user_id' => $user->id,
        'nama_badan_publik' => 'Test Badan',
        'website' => 'https://example.com',
        'telepon_badan_publik' => '0286-123456',
        'email_badan_publik' => 'info@example.com',
        'alamat' => 'Test Address',
        'telepon_responden' => '0286-654321',
        'jabatan' => 'Kepala',
    ]);

    expect($badanPublik->user->id)->toBe($user->id);
    expect($badanPublik->user->name)->toBe('Test User');
});

// ── Submission belongsTo User, Kategori, Jadwal ───────────────────────────

test('Submission belongs to User, Kategori, and Jadwal', function () {
    $user = User::factory()->create();
    $kategori = Kategori::create(['nama' => 'Test Kategori', 'judul' => 'Test', 'deskripsi' => 'D']);
    $jadwal = Jadwal::create([
        'nama' => 'Test Jadwal',
        'tahun' => 2026,
        'tanggal_mulai' => now()->subDay(),
        'tanggal_selesai' => now()->addYear(),
        'is_active' => true,
    ]);

    $submission = Submission::create([
        'user_id' => $user->id,
        'kategori_id' => $kategori->id,
        'jadwal_id' => $jadwal->id,
        'tanggal_submit' => now(),
    ]);

    expect($submission->user->id)->toBe($user->id);
    expect($submission->kategori->id)->toBe($kategori->id);
    expect($submission->jadwal->id)->toBe($jadwal->id);
});

// ── User hasMany Submission ───────────────────────────────────────────────

test('User has many submissions', function () {
    $user = User::factory()->create();
    $kategori = Kategori::create(['nama' => 'K', 'judul' => 'K', 'deskripsi' => 'D']);
    $jadwal = Jadwal::create([
        'nama' => 'J',
        'tahun' => 2026,
        'tanggal_mulai' => now()->subDay(),
        'tanggal_selesai' => now()->addYear(),
        'is_active' => true,
    ]);

    Submission::create([
        'user_id' => $user->id,
        'kategori_id' => $kategori->id,
        'jadwal_id' => $jadwal->id,
        'tanggal_submit' => now(),
    ]);
    Submission::create([
        'user_id' => $user->id,
        'kategori_id' => $kategori->id,
        'jadwal_id' => $jadwal->id,
        'tanggal_submit' => now(),
    ]);

    expect($user->submissions)->toHaveCount(2);
});
