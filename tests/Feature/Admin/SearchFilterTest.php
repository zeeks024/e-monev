<?php

use App\Models\BadanPublik;
use App\Models\HasilPenilaian;
use App\Models\Jadwal;
use App\Models\Kategori;
use App\Models\KlasifikasiPenilaian;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Volt\Volt;

beforeEach(function () {
    $this->admin = User::create([
        'name' => 'Test Admin',
        'email' => 'admin-search@test.com',
        'password' => Hash::make('password123'),
        'role' => 'admin',
    ]);

    Auth::guard('admin')->login($this->admin);
});

test('penilaian search filters rows by badan publik name', function () {
    $jadwal = Jadwal::create([
        'nama' => 'E-Monev KIP 2026',
        'tahun' => 2026,
        'tanggal_mulai' => now()->subDay(),
        'tanggal_selesai' => now()->addDay(),
        'is_active' => true,
        'deskripsi' => 'Periode aktif',
    ]);

    $kategori = Kategori::create([
        'nama' => 'Kategori A',
        'judul' => 'Kategori A',
        'deskripsi' => 'Untuk pengujian search penilaian.',
    ]);

    $userTarget = User::factory()->create(['role' => 'dinas', 'name' => 'User Target']);
    $userOther = User::factory()->create(['role' => 'dinas', 'name' => 'User Other']);

    BadanPublik::create([
        'user_id' => $userTarget->id,
        'nama_badan_publik' => 'Dinas Pendidikan',
        'website' => 'https://pendidikan.test',
        'telepon_badan_publik' => '0286-111111',
        'email_badan_publik' => 'pendidikan@test.id',
        'alamat' => 'Jl. Pendidikan',
        'telepon_responden' => '081111111111',
        'jabatan' => 'Admin',
    ]);

    BadanPublik::create([
        'user_id' => $userOther->id,
        'nama_badan_publik' => 'Dinas Kesehatan',
        'website' => 'https://kesehatan.test',
        'telepon_badan_publik' => '0286-222222',
        'email_badan_publik' => 'kesehatan@test.id',
        'alamat' => 'Jl. Kesehatan',
        'telepon_responden' => '082222222222',
        'jabatan' => 'Admin',
    ]);

    Submission::create([
        'user_id' => $userTarget->id,
        'kategori_id' => $kategori->id,
        'jadwal_id' => $jadwal->id,
        'tanggal_submit' => now(),
        'status_verifikasi' => 'Menunggu',
    ]);

    Submission::create([
        'user_id' => $userOther->id,
        'kategori_id' => $kategori->id,
        'jadwal_id' => $jadwal->id,
        'tanggal_submit' => now(),
        'status_verifikasi' => 'Menunggu',
    ]);

    Volt::test('pages.admin.penilaian')
        ->assertSee('Dinas Pendidikan')
        ->assertSee('Dinas Kesehatan')
        ->set('search', 'Pendidikan')
        ->assertSee('Dinas Pendidikan')
        ->assertDontSee('Dinas Kesehatan');
});

test('laporan search filters verified report rows by badan publik name', function () {
    $jadwal = Jadwal::create([
        'nama' => 'E-Monev KIP 2026',
        'tahun' => 2026,
        'tanggal_mulai' => now()->subDay(),
        'tanggal_selesai' => now()->addDay(),
        'is_active' => true,
        'deskripsi' => 'Periode aktif',
    ]);

    $klasifikasi = KlasifikasiPenilaian::create([
        'nama' => 'Informatif',
        'min_nilai' => 80,
        'max_nilai' => 100,
        'urutan' => 1,
        'is_active' => true,
    ]);

    $userTarget = User::factory()->create(['role' => 'dinas', 'name' => 'User Target']);
    $userOther = User::factory()->create(['role' => 'dinas', 'name' => 'User Other']);

    BadanPublik::create([
        'user_id' => $userTarget->id,
        'nama_badan_publik' => 'Dinas Kominfo',
        'website' => 'https://kominfo.test',
        'telepon_badan_publik' => '0286-333333',
        'email_badan_publik' => 'kominfo@test.id',
        'alamat' => 'Jl. Kominfo',
        'telepon_responden' => '083333333333',
        'jabatan' => 'Admin',
    ]);

    BadanPublik::create([
        'user_id' => $userOther->id,
        'nama_badan_publik' => 'Dinas Pariwisata',
        'website' => 'https://pariwisata.test',
        'telepon_badan_publik' => '0286-444444',
        'email_badan_publik' => 'pariwisata@test.id',
        'alamat' => 'Jl. Pariwisata',
        'telepon_responden' => '084444444444',
        'jabatan' => 'Admin',
    ]);

    HasilPenilaian::create([
        'user_id' => $userTarget->id,
        'jadwal_id' => $jadwal->id,
        'nilai_akhir' => 90,
        'klasifikasi_penilaian_id' => $klasifikasi->id,
        'status_verifikasi' => 'Terverifikasi',
        'verified_at' => now(),
    ]);

    HasilPenilaian::create([
        'user_id' => $userOther->id,
        'jadwal_id' => $jadwal->id,
        'nilai_akhir' => 88,
        'klasifikasi_penilaian_id' => $klasifikasi->id,
        'status_verifikasi' => 'Terverifikasi',
        'verified_at' => now(),
    ]);

    Volt::test('pages.admin.laporan')
        ->assertSee('Dinas Kominfo')
        ->assertSee('Dinas Pariwisata')
        ->set('search', 'Kominfo')
        ->assertSee('Dinas Kominfo')
        ->assertDontSee('Dinas Pariwisata');
});
