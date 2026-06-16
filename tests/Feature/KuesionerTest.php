<?php

use App\Models\BadanPublik;
use App\Models\Jadwal;
use App\Models\JadwalPertanyaan;
use App\Models\Jawaban;
use App\Models\Kategori;
use App\Models\PertanyaanTemplate;
use App\Models\Submission;
use App\Models\User;
use Livewire\Volt\Volt;

// ── Kuesioner page access ─────────────────────────────────────────────────

test('authenticated user can access kuesioner page', function () {
    $user = User::factory()->create();
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

    $this->actingAs($user);

    Volt::test('pages.kuesioner')
        ->assertSet('namaResponden', $user->name)
        ->assertSet('isJadwalAktif', false);
});

test('guest cannot access kuesioner page', function () {
    $response = $this->get('/kuesioner');

    $response->assertRedirect('/login');
});

test('kuesioner page shows badan publik info', function () {
    $user = User::factory()->create(['name' => 'Test Respondent']);
    BadanPublik::create([
        'user_id' => $user->id,
        'nama_badan_publik' => 'Dinas Test',
        'website' => 'https://test.com',
        'telepon_badan_publik' => '0286-000000',
        'email_badan_publik' => 'test@example.com',
        'alamat' => 'Test Address',
        'telepon_responden' => '081000000000',
        'jabatan' => 'Staff',
    ]);

    $this->actingAs($user);

    $response = $this->get('/kuesioner');

    $response->assertSee('Test Respondent');
    $response->assertSee('Dinas Test');
    $response->assertSee('Data Kuesioner');
});

// ── Kuesioner jawab page access ───────────────────────────────────────────

test('authenticated user can access kuesioner jawab page', function () {
    $user = User::factory()->create();
    Jadwal::create([
        'nama' => 'Monev TA 2026',
        'tahun' => 2026,
        'tanggal_mulai' => now()->subDay(),
        'tanggal_selesai' => now()->addYear(),
        'is_active' => true,
    ]);

    $this->actingAs($user);

    $response = $this->get('/kuesioner/jawab');

    $response->assertOk();
});

test('guest cannot access kuesioner jawab page', function () {
    $response = $this->get('/kuesioner/jawab');

    $response->assertRedirect('/login');
});

// ── Questions display ─────────────────────────────────────────────────────

test('questions from active jadwal are displayed', function () {
    $user = User::factory()->create();
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

    $kategori = Kategori::create(['nama' => 'Test Kategori', 'judul' => 'Test', 'deskripsi' => 'D']);
    $template = PertanyaanTemplate::create([
        'kategori_id' => $kategori->id,
        'teks_pertanyaan' => 'Test Question?',
        'definisi_operasional' => 'Test definition',
        'tipe_jawaban' => 'Ya/Tidak',
        'butuh_link' => true,
    ]);
    JadwalPertanyaan::create([
        'jadwal_id' => $jadwal->id,
        'pertanyaan_template_id' => $template->id,
        'teks_pertanyaan' => 'Test Question?',
        'definisi_operasional' => 'Test definition',
        'urutan' => 1,
        'tipe_jawaban' => 'Ya/Tidak',
        'butuh_link' => true,
        'skor_maks' => 5,
    ]);

    $this->actingAs($user);

    $response = $this->get('/kuesioner/jawab');

    $response->assertSee('Test Question?');
    $response->assertSee('Monev TA 2026');
});

test('shows error when no active jadwal exists', function () {
    $user = User::factory()->create();
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

    $this->actingAs($user);

    $response = $this->get('/kuesioner/jawab');

    $response->assertSee('Tidak Ada Jadwal Aktif');
});

// ── Submit answers ────────────────────────────────────────────────────────

test('can submit answers with Ya/Tidak and links', function () {
    $user = User::factory()->create();
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

    $kategori = Kategori::create(['nama' => 'Test Kategori', 'judul' => 'Test', 'deskripsi' => 'D']);
    $template = PertanyaanTemplate::create([
        'kategori_id' => $kategori->id,
        'teks_pertanyaan' => 'Test Question?',
        'tipe_jawaban' => 'Ya/Tidak',
        'butuh_link' => true,
    ]);
    $jadwalPertanyaan = JadwalPertanyaan::create([
        'jadwal_id' => $jadwal->id,
        'pertanyaan_template_id' => $template->id,
        'teks_pertanyaan' => 'Test Question?',
        'urutan' => 1,
        'tipe_jawaban' => 'Ya/Tidak',
        'butuh_link' => true,
        'skor_maks' => 5,
    ]);

    $this->actingAs($user);

    $component = Volt::test('pages.jawab-kuesioner');

    // Set the active kategori (normally done in mount)
    $component->set('activeKategoriId', $kategori->id);
    $component->set('jawaban.' . $jadwalPertanyaan->id, 'Ya');
    $component->set('link_dokumen.' . $jadwalPertanyaan->id, 'https://example.com/doc');

    $component->call('simpanJawaban');

    $this->assertDatabaseHas('submissions', [
        'user_id' => $user->id,
        'kategori_id' => $kategori->id,
        'jadwal_id' => $jadwal->id,
    ]);

    $this->assertDatabaseHas('jawabans', [
        'jadwal_pertanyaan_id' => $jadwalPertanyaan->id,
        'jawaban' => 'Ya',
        'link_dokumen' => 'https://example.com/doc',
    ]);
});

test('submission is recorded in database', function () {
    $user = User::factory()->create();
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

    $kategori = Kategori::create(['nama' => 'Test Kategori', 'judul' => 'Test', 'deskripsi' => 'D']);
    $template = PertanyaanTemplate::create([
        'kategori_id' => $kategori->id,
        'teks_pertanyaan' => 'Q?',
        'tipe_jawaban' => 'Ya/Tidak',
    ]);
    $jp = JadwalPertanyaan::create([
        'jadwal_id' => $jadwal->id,
        'pertanyaan_template_id' => $template->id,
        'teks_pertanyaan' => 'Q?',
        'urutan' => 1,
        'tipe_jawaban' => 'Ya/Tidak',
        'skor_maks' => 5,
    ]);

    $this->actingAs($user);

    $component = Volt::test('pages.jawab-kuesioner');
    $component->set('activeKategoriId', $kategori->id);
    $component->set('jawaban.' . $jp->id, 'Tidak');
    $component->call('simpanJawaban');

    $submission = Submission::where('user_id', $user->id)->first();
    expect($submission)->not->toBeNull();
    expect($submission->kategori_id)->toBe($kategori->id);
    expect($submission->jadwal_id)->toBe($jadwal->id);
    expect($submission->tanggal_submit)->not->toBeNull();
});

// ── Validation on submission ──────────────────────────────────────────────

test('validates jawaban must be Ya or Tidak', function () {
    $user = User::factory()->create();
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
        'nama' => 'Monev',
        'tahun' => 2026,
        'tanggal_mulai' => now()->subDay(),
        'tanggal_selesai' => now()->addYear(),
        'is_active' => true,
    ]);

    $kategori = Kategori::create(['nama' => 'K', 'judul' => 'K', 'deskripsi' => 'D']);
    $template = PertanyaanTemplate::create([
        'kategori_id' => $kategori->id,
        'teks_pertanyaan' => 'Q?',
        'tipe_jawaban' => 'Ya/Tidak',
    ]);
    $jp = JadwalPertanyaan::create([
        'jadwal_id' => $jadwal->id,
        'pertanyaan_template_id' => $template->id,
        'teks_pertanyaan' => 'Q?',
        'urutan' => 1,
        'tipe_jawaban' => 'Ya/Tidak',
        'skor_maks' => 5,
    ]);

    $this->actingAs($user);

    $component = Volt::test('pages.jawab-kuesioner');
    $component->set('activeKategoriId', $kategori->id);
    $component->set('jawaban.' . $jp->id, 'Invalid');
    $component->call('simpanJawaban');

    $component->assertHasErrors(['jawaban.' . $jp->id]);
});

test('validates link must be valid URL', function () {
    $user = User::factory()->create();
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
        'nama' => 'Monev',
        'tahun' => 2026,
        'tanggal_mulai' => now()->subDay(),
        'tanggal_selesai' => now()->addYear(),
        'is_active' => true,
    ]);

    $kategori = Kategori::create(['nama' => 'K', 'judul' => 'K', 'deskripsi' => 'D']);
    $template = PertanyaanTemplate::create([
        'kategori_id' => $kategori->id,
        'teks_pertanyaan' => 'Q?',
        'tipe_jawaban' => 'Ya/Tidak',
        'butuh_link' => true,
    ]);
    $jp = JadwalPertanyaan::create([
        'jadwal_id' => $jadwal->id,
        'pertanyaan_template_id' => $template->id,
        'teks_pertanyaan' => 'Q?',
        'urutan' => 1,
        'tipe_jawaban' => 'Ya/Tidak',
        'butuh_link' => true,
        'skor_maks' => 5,
    ]);

    $this->actingAs($user);

    $component = Volt::test('pages.jawab-kuesioner');
    $component->set('activeKategoriId', $kategori->id);
    $component->set('jawaban.' . $jp->id, 'Ya');
    $component->set('link_dokumen.' . $jp->id, 'not-a-url');
    $component->call('simpanJawaban');

    $component->assertHasErrors(['link_dokumen.' . $jp->id]);
});
