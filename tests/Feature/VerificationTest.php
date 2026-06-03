<?php

use App\Models\BadanPublik;
use App\Models\HasilPenilaian;
use App\Models\Jadwal;
use App\Models\JadwalPertanyaan;
use App\Models\Jawaban;
use App\Models\Kategori;
use App\Models\PertanyaanTemplate;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Volt\Volt;

// ── Test setup ─────────────────────────────────────────────────────────────

function createVerificationSetup(): array
{
    $admin = User::create([
        'name' => 'Verifier Admin',
        'email' => 'verifier@test.com',
        'password' => Hash::make('password'),
        'role' => 'admin',
    ]);
    Auth::guard('admin')->login($admin);

    $user = User::factory()->create(['role' => 'dinas', 'name' => 'Test Dinas']);
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
        'tipe_jawaban' => 'Ya/Tidak',
    ]);

    $jp1 = JadwalPertanyaan::create([
        'jadwal_id' => $jadwal->id,
        'pertanyaan_template_id' => $template->id,
        'teks_pertanyaan' => 'Question 1?',
        'definisi_operasional' => 'Def 1',
        'urutan' => 1,
        'tipe_jawaban' => 'Ya/Tidak',
        'skor_maks' => 10,
    ]);
    $jp2 = JadwalPertanyaan::create([
        'jadwal_id' => $jadwal->id,
        'pertanyaan_template_id' => $template->id,
        'teks_pertanyaan' => 'Question 2?',
        'definisi_operasional' => 'Def 2',
        'urutan' => 2,
        'tipe_jawaban' => 'Ya/Tidak',
        'skor_maks' => 20,
    ]);

    $submission = Submission::create([
        'user_id' => $user->id,
        'kategori_id' => $kategori->id,
        'jadwal_id' => $jadwal->id,
        'tanggal_submit' => now(),
    ]);

    $jawaban1 = Jawaban::create([
        'submission_id' => $submission->id,
        'jadwal_pertanyaan_id' => $jp1->id,
        'jawaban' => 'Ya',
        'is_valid' => null,
    ]);
    $jawaban2 = Jawaban::create([
        'submission_id' => $submission->id,
        'jadwal_pertanyaan_id' => $jp2->id,
        'jawaban' => 'Tidak',
        'is_valid' => null,
    ]);

    HasilPenilaian::create([
        'user_id' => $user->id,
        'jadwal_id' => $jadwal->id,
        'nilai_akhir' => 0,
        'status_verifikasi' => 'Menunggu',
    ]);

    return [
        'admin' => $admin,
        'user' => $user,
        'jadwal' => $jadwal,
        'submission' => $submission,
        'jawaban1' => $jawaban1,
        'jawaban2' => $jawaban2,
    ];
}

// ── Verification page shows per-question validation ───────────────────────

test('verification page shows per question validation', function () {
    $s = createVerificationSetup();

    $component = Volt::test('pages.admin.verifikasi-nilai', [
        'user' => $s['user'],
        'jadwal' => $s['jadwal'],
    ]);

    $component->assertSee('Question 1?');
    $component->assertSee('Question 2?');
    $component->assertSee('Skor Maks: 10');
    $component->assertSee('Skor Maks: 20');
    $component->assertSee('Valid');
    $component->assertSee('Tidak Valid');
});

// ── Validated Ya contributes score ─────────────────────────────────────────

test('validated Ya contributes score', function () {
    $s = createVerificationSetup();

    $component = Volt::test('pages.admin.verifikasi-nilai', [
        'user' => $s['user'],
        'jadwal' => $s['jadwal'],
    ]);

    $component->call('toggleValidasi', $s['jawaban1']->id, 'valid');
    $component->call('simpan');

    $this->assertDatabaseHas('penilaians', [
        'submission_id' => $s['submission']->id,
        'nilai' => 10,
    ]);
});

// ── Invalidated answer contributes zero ────────────────────────────────────

test('invalidated answer contributes zero', function () {
    $s = createVerificationSetup();

    $component = Volt::test('pages.admin.verifikasi-nilai', [
        'user' => $s['user'],
        'jadwal' => $s['jadwal'],
    ]);

    $component->call('toggleValidasi', $s['jawaban1']->id, 'valid');
    $component->call('toggleValidasi', $s['jawaban1']->id, 'tidak_valid');
    $component->call('simpan');

    $this->assertDatabaseHas('penilaians', [
        'submission_id' => $s['submission']->id,
        'nilai' => 0,
    ]);
});

// ── Partial save preserves state ───────────────────────────────────────────

test('partial save preserves is_valid and catatan values', function () {
    $s = createVerificationSetup();

    $component = Volt::test('pages.admin.verifikasi-nilai', [
        'user' => $s['user'],
        'jadwal' => $s['jadwal'],
    ]);

    $component->call('toggleValidasi', $s['jawaban1']->id, 'valid');
    $component->set('validasiJawaban.' . $s['jawaban1']->id . '.catatan', 'Looks good');
    $component->call('simpan');

    $this->assertDatabaseHas('jawabans', [
        'id' => $s['jawaban1']->id,
        'is_valid' => 1,
        'catatan' => 'Looks good',
    ]);

    $this->assertDatabaseHas('jawabans', [
        'id' => $s['jawaban2']->id,
        'is_valid' => null,
    ]);
});

// ── Selesai verifikasi requires all reviewed ───────────────────────────────

test('selesai verifikasi requires all reviewed', function () {
    $s = createVerificationSetup();

    $component = Volt::test('pages.admin.verifikasi-nilai', [
        'user' => $s['user'],
        'jadwal' => $s['jadwal'],
    ]);

    $component->call('toggleValidasi', $s['jawaban1']->id, 'valid');
    // Q2 remains is_valid=null

    $component->call('selesaiVerifikasi');

    // Should flash error because Q2 is unreviewed
    $component->assertSee('Semua pertanyaan harus divalidasi');
});
