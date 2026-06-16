<?php

use App\Models\Jadwal;
use App\Models\JadwalPertanyaan;
use App\Models\Kategori;
use App\Models\PertanyaanTemplate;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Volt\Volt;

beforeEach(function () {
    $this->admin = User::create([
        'name' => 'Test Admin',
        'email' => 'admin-jadwal@test.com',
        'password' => Hash::make('password123'),
        'role' => 'admin',
    ]);

    Auth::guard('admin')->login($this->admin);
});

test('creating a new jadwal copies questions from the previous jadwal and activates the new jadwal', function () {
    $kategori = Kategori::create([
        'nama' => 'Informasi Berkala',
        'judul' => 'Informasi Berkala',
        'deskripsi' => 'Kategori untuk pengujian penyalinan pertanyaan jadwal.',
    ]);

    $template = PertanyaanTemplate::create([
        'kategori_id' => $kategori->id,
        'teks_pertanyaan' => 'Apakah informasi publik ditampilkan pada website resmi?',
        'definisi_operasional' => 'Pastikan informasi mudah diakses masyarakat.',
        'tipe_jawaban' => 'Ya/Tidak',
        'butuh_link' => true,
        'butuh_upload' => false,
        'is_active' => true,
    ]);

    $jadwalLama = Jadwal::create([
        'nama' => 'E-Monev KIP 2025',
        'tahun' => 2025,
        'tanggal_mulai' => now()->subYear()->startOfMonth(),
        'tanggal_selesai' => now()->subYear()->endOfMonth(),
        'is_active' => true,
        'deskripsi' => 'Periode lama',
    ]);

    JadwalPertanyaan::create([
        'jadwal_id' => $jadwalLama->id,
        'pertanyaan_template_id' => $template->id,
        'teks_pertanyaan' => $template->teks_pertanyaan,
        'definisi_operasional' => $template->definisi_operasional,
        'urutan' => 1,
        'tipe_jawaban' => 'Ya/Tidak',
        'butuh_link' => true,
        'butuh_upload' => false,
        'skor_maks' => 10,
    ]);

    Volt::test('pages.admin.kuesioner.jadwal')
        ->set('nama', 'E-Monev KIP 2026')
        ->set('tahun', 2026)
        ->set('tanggal_mulai', now()->addMonth()->startOfMonth()->setHour(8)->setMinute(0)->format('Y-m-d\TH:i'))
        ->set('tanggal_selesai', now()->addMonth()->endOfMonth()->setHour(23)->setMinute(59)->format('Y-m-d\TH:i'))
        ->set('deskripsi', 'Periode baru')
        ->call('createJadwal')
        ->assertHasNoErrors();

    $jadwalBaru = Jadwal::where('nama', 'E-Monev KIP 2026')->first();

    expect($jadwalBaru)->not->toBeNull();
    expect($jadwalBaru->is_active)->toBeTrue();
    expect($jadwalLama->fresh()->is_active)->toBeFalse();

    $salinan = JadwalPertanyaan::where('jadwal_id', $jadwalBaru->id)->first();

    expect($salinan)->not->toBeNull();
    expect($salinan->pertanyaan_template_id)->toBe($template->id);
    expect($salinan->teks_pertanyaan)->toBe($template->teks_pertanyaan);
    expect($salinan->definisi_operasional)->toBe($template->definisi_operasional);
    expect((bool) $salinan->butuh_link)->toBeTrue();
    expect((bool) $salinan->butuh_upload)->toBeFalse();
    expect((float) $salinan->skor_maks)->toBe(10.0);
});

test('admin can set skor maksimum when creating a question for active jadwal', function () {
    $kategori = Kategori::create([
        'nama' => 'Informasi Serta Merta',
        'judul' => 'Informasi Serta Merta',
        'deskripsi' => 'Kategori untuk pengaturan skor.',
    ]);

    $jadwal = Jadwal::create([
        'nama' => 'E-Monev KIP 2026',
        'tahun' => 2026,
        'tanggal_mulai' => now()->subDay(),
        'tanggal_selesai' => now()->addDay(),
        'is_active' => true,
        'deskripsi' => 'Periode aktif',
    ]);

    Volt::test('pages.admin.kuesioner.create-pertanyaan', ['kategori' => $kategori])
        ->set('teks_pertanyaan', 'Apakah informasi penting dipublikasikan dengan cepat?')
        ->set('definisi_operasional', 'Informasi tersedia tepat waktu untuk publik.')
        ->set('butuh_link', true)
        ->set('butuh_upload', false)
        ->set('skor_maks', 25)
        ->call('save')
        ->assertHasNoErrors();

    $pertanyaan = JadwalPertanyaan::where('jadwal_id', $jadwal->id)->first();

    expect($pertanyaan)->not->toBeNull();
    expect((float) $pertanyaan->skor_maks)->toBe(25.0);
});

test('question changes are locked after submissions exist on the active jadwal', function () {
    $kategori = Kategori::create([
        'nama' => 'Informasi Berkala',
        'judul' => 'Informasi Berkala',
        'deskripsi' => 'Kategori untuk penguncian skor.',
    ]);

    $template = PertanyaanTemplate::create([
        'kategori_id' => $kategori->id,
        'teks_pertanyaan' => 'Apakah informasi publik ditampilkan pada website resmi?',
        'definisi_operasional' => 'Pastikan informasi mudah diakses masyarakat.',
        'tipe_jawaban' => 'Ya/Tidak',
        'butuh_link' => true,
        'butuh_upload' => false,
        'is_active' => true,
    ]);

    $jadwal = Jadwal::create([
        'nama' => 'E-Monev KIP 2026',
        'tahun' => 2026,
        'tanggal_mulai' => now()->subDay(),
        'tanggal_selesai' => now()->addDay(),
        'is_active' => true,
        'deskripsi' => 'Periode aktif',
    ]);

    $pertanyaan = JadwalPertanyaan::create([
        'jadwal_id' => $jadwal->id,
        'pertanyaan_template_id' => $template->id,
        'teks_pertanyaan' => $template->teks_pertanyaan,
        'definisi_operasional' => $template->definisi_operasional,
        'urutan' => 1,
        'tipe_jawaban' => 'Ya/Tidak',
        'butuh_link' => true,
        'butuh_upload' => false,
        'skor_maks' => 15,
    ]);

    Submission::create([
        'user_id' => User::factory()->create(['role' => 'dinas'])->id,
        'kategori_id' => $kategori->id,
        'jadwal_id' => $jadwal->id,
        'tanggal_submit' => now(),
        'status_verifikasi' => 'Menunggu',
    ]);

    Volt::test('pages.admin.kuesioner.create-pertanyaan', [
        'kategori' => $kategori,
        'jadwalPertanyaan' => $pertanyaan->id,
    ])
        ->set('teks_pertanyaan', 'Pertanyaan diubah setelah ada submission')
        ->set('skor_maks', 50)
        ->call('save');

    $pertanyaan->refresh();

    expect($pertanyaan->teks_pertanyaan)->toBe('Apakah informasi publik ditampilkan pada website resmi?');
    expect((float) $pertanyaan->skor_maks)->toBe(15.0);
});
