<?php

use App\Http\Controllers\Admin\Auth\AuthenticatedSessionController as AdminAuth;
use App\Http\Controllers\Admin\LaporanController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

/*
|--------------------------------------------------------------------------
| Rute Otentikasi Admin
|--------------------------------------------------------------------------
|
| Rute ini untuk menampilkan form login dan memproses login admin.
| Dilindungi oleh middleware 'guest:admin' agar hanya bisa diakses
| oleh admin yang belum login.
|
*/
Route::middleware('guest:admin')->group(function () {
    // URL: /admin/login
    // Nama: admin.login
    Route::get('/login', [AdminAuth::class, 'create'])->name('login');

    // URL: /admin/login (method POST)
    // Nama: admin.login.store
    Route::post('/login', [AdminAuth::class, 'store'])->name('login.store');
});


/*
|--------------------------------------------------------------------------
| Rute Panel Admin
|--------------------------------------------------------------------------
|
| Semua rute di bawah ini dilindungi oleh middleware 'admin'.
| Prefix URL '/admin' dan prefix nama 'admin.' sudah ditambahkan
| secara otomatis dari file bootstrap/app.php.
| Kita hanya perlu menulis URL relatifnya di sini.
|
*/
Route::middleware('admin')->group(function () {
    // --- Dashboard ---
    // URL: /admin/dashboard
    // Nama: admin.dashboard
    Volt::route('/dashboard', 'pages.admin.dashboard')->name('dashboard');

    // --- Kuesioner ---
    // URL: /admin/kuesioner
    // Nama: admin.kuesioner
    Volt::route('/kuesioner', 'pages.admin.kuesioner')->name('kuesioner');
    Volt::route('/kuesioner/kategori/create', 'pages.admin.kuesioner.create-kategori')->name('kuesioner.kategori.create');
    Volt::route('/kuesioner/kategori/{kategori}/edit', 'pages.admin.kuesioner.create-kategori')->name('kuesioner.kategori.edit');
    Volt::route('/kuesioner/jadwal', 'pages.admin.kuesioner.jadwal')->name('kuesioner.jadwal');
    Volt::route('/kuesioner/kategori/{kategori}/pertanyaan', 'pages.admin.kuesioner.detail-pertanyaan')->name('kuesioner.pertanyaan.index');
    Volt::route('/kuesioner/kategori/{kategori}/pertanyaan/create', 'pages.admin.kuesioner.create-pertanyaan')->name('kuesioner.pertanyaan.create');
    Volt::route('/kuesioner/kategori/{kategori}/pertanyaan/{jadwalPertanyaan}/edit', 'pages.admin.kuesioner.create-pertanyaan')->name('kuesioner.pertanyaan.edit');

    // --- Penilaian ---
    // URL: /admin/penilaian
    // Nama: admin.penilaian
    Volt::route('/penilaian', 'pages.admin.penilaian')->name('penilaian');
    Volt::route('/penilaian/{user}/jadwal/{jadwal}/verifikasi', 'pages.admin.verifikasi-nilai')->name('penilaian.verifikasi');

    // --- Statistik ---
    // URL: /admin/statistik
    // Nama: admin.statistik
    Volt::route('/statistik', 'pages.admin.statistik')->name('statistik');

    // --- Pengguna ---
    // URL: /admin/pengguna
    // Nama: admin.pengguna
    Volt::route('/pengguna', 'pages.admin.pengguna')->name('pengguna');

    // --- Badan Publik ---
    // URL: /admin/badan-publik
    // Nama: admin.badan-publik
    Volt::route('/badan-publik', 'pages.admin.badan-publik')->name('badan-publik');
    Volt::route('/badan-publik/{user}/detail', 'pages.admin.detail-badan-publik')->name('badan-publik.detail');
    Volt::route('/badan-publik/{user}/edit', 'pages.admin.edit-badan-publik')->name('badan-publik.edit');

    // --- Laporan ---
    // URL: /admin/laporan
    // Nama: admin.laporan
    Volt::route('/laporan', 'pages.admin.laporan')->name('laporan');
    Route::get('/laporan/unduh', [LaporanController::class, 'unduhPdf'])->name('laporan.unduh');
    Route::get('/laporan/unduh/{userId}/jadwal/{jadwalId}', [LaporanController::class, 'unduhPdfPerBadanPublik'])->name('laporan.unduh.per-badan-publik');

    // --- Lain-lain ---
    Volt::route('/pesan', 'pages.admin.pesan')->name('pesan');
    Volt::route('/klasifikasi-penilaian', 'pages.admin.klasifikasi-penilaian')->name('klasifikasi-penilaian');
    Volt::route('/pengaturan', 'pages.admin.pengaturan')->name('pengaturan');
    Volt::route('/keluar', 'pages.admin.keluar')->name('keluar');

    // --- Logout Admin ---
    // URL: /admin/logout (method POST)
    // Nama: admin.logout
    Route::post('/logout', [AdminAuth::class, 'destroy'])->name('logout');
});
