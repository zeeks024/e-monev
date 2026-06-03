<?php

//use App\Http\Controllers\Admin\Auth\AuthenticatedSessionController as AdminAuth;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LandingPageController;
use Livewire\Volt\Volt;
use App\Models\HasilPenilaian;
use App\Models\Jadwal;
use App\Models\KlasifikasiPenilaian;
use App\Models\User;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Rute untuk landing page
Route::get('/', function () {
    $jadwalAcuan = Jadwal::active()->first() ?? Jadwal::latest('tanggal_mulai')->first();

    $klasifikasiAktif = KlasifikasiPenilaian::query()
        ->active()
        ->orderBy('urutan')
        ->get();

    $statistikKlasifikasi = $klasifikasiAktif->map(function ($item) use ($jadwalAcuan) {
        $query = HasilPenilaian::query()
            ->where('klasifikasi_penilaian_id', $item->id)
            ->where('status_verifikasi', 'Terverifikasi');

        if ($jadwalAcuan) {
            $query->where('jadwal_id', $jadwalAcuan->id);
        }

        return [
            'nama' => $item->nama,
            'jumlah' => $query->count(),
        ];
    })->values();

    $statistik = [
        'total_terdaftar' => User::where('role', 'dinas')->count(),
        'klasifikasi' => $statistikKlasifikasi,
    ];
    return view('welcome', ['statistik' => $statistik]); // 'welcome' adalah nama file blade Anda
});

// --- Rute untuk Komponen Livewire/Volt ---

// Rute untuk dashboard utama setelah login
Volt::route('/dashboard', 'pages.dashboard')
    ->middleware(['auth', 'verified'])
    ->name('user.dashboard');
    
// Rute untuk halaman ubah biodata
Volt::route('/biodata/edit', 'pages.edit-biodata')
    ->middleware(['auth']) 
    ->name('biodata.edit');

// Rute untuk halaman informasi kuesioner
Volt::route('/kuesioner', 'pages.kuesioner')
    ->middleware(['auth']) 
    ->name('kuesioner');

// Rute untuk halaman menjawab kuesioner
Volt::route('/kuesioner/jawab', 'pages.jawab-kuesioner')
    ->middleware(['auth'])
    ->name('kuesioner.jawab');

// Rute untuk halaman notifikasi
Volt::route('/notifikasi', 'pages.notifikasi')
    ->middleware(['auth'])
    ->name('notifikasi');

// Rute untuk halaman konfirmasi keluar
Volt::route('/keluar', 'pages.auth.logout-confirm')
    ->middleware(['auth'])
    ->name('logout.confirm');

// Rute untuk halaman verifikasi kode lupa password
Volt::route('/forgot-password/verify-code', 'pages.auth.verify-code')
    ->name('password.verify-code');

// Rute untuk halaman sukses setelah reset password
Volt::route('/reset-password/success', 'pages.auth.password-reset-success')
    ->name('password.update.success');

Volt::route('/notifikasi', 'pages.notifikasi')
    ->name('notifikasi');


// Rute untuk profil pengguna
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});


// Rute untuk otentikasi (login, register, dll.)
require __DIR__.'/auth.php';

// Panggil semua rute dari file admin.php
