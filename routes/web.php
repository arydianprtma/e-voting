<?php

use App\Http\Controllers\Admin\AdminElectionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\VotingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - Portal Pemilih (Public)
|--------------------------------------------------------------------------
*/
Route::get('/', [VotingController::class, 'showLanding'])->name('home');
Route::get('/register-pemilih', [VotingController::class, 'showRegister'])->name('voter.register');
Route::post('/register-pemilih/check-nim', [VotingController::class, 'checkNim'])->middleware('throttle:30,1')->name('voter.check_nim');
Route::post('/register-pemilih', [VotingController::class, 'submitRegister'])->middleware('throttle:10,1')->name('voter.register.submit');
Route::post('/login-pemilih', [VotingController::class, 'loginVoter'])->middleware('throttle:10,1')->name('voter.login');
Route::get('/bilik-suara', [VotingController::class, 'showBooth'])->name('booth.show');
Route::post('/kirim-suara', [VotingController::class, 'submitVote'])->middleware('throttle:10,1')->name('booth.submit');
Route::get('/bukti-suara', [VotingController::class, 'showReceipt'])->name('receipt.show');
Route::get('/quick-count', [VotingController::class, 'showQuickCount'])->name('quick_count.show');

// Redirect rute lama token ke alur akun mandiri
Route::get('/klaim-token', fn () => redirect()->route('voter.register'))->name('token.claim');
Route::post('/masuk-bilik', [VotingController::class, 'loginVoter'])->name('booth.verify');

/*
|--------------------------------------------------------------------------
| Admin & Panitia Pemilihan Routes (Protected)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [AdminElectionController::class, 'dashboard'])->name('dashboard');

    // Manajemen Kandidat
    Route::get('/admin/kandidat', [AdminElectionController::class, 'candidates'])->name('admin.candidates');
    Route::post('/admin/kandidat', [AdminElectionController::class, 'storeCandidate'])->name('admin.candidates.store');
    Route::match(['put', 'post'], '/admin/kandidat/{candidate}', [AdminElectionController::class, 'updateCandidate'])->name('admin.candidates.update');
    Route::delete('/admin/kandidat/{candidate}', [AdminElectionController::class, 'destroyCandidate'])->name('admin.candidates.destroy');

    // Manajemen Jurusan & Program Studi
    Route::get('/admin/jurusan', [AdminElectionController::class, 'departments'])->name('admin.departments');
    Route::post('/admin/jurusan', [AdminElectionController::class, 'storeDepartment'])->name('admin.departments.store');
    Route::put('/admin/jurusan/{department}', [AdminElectionController::class, 'updateDepartment'])->name('admin.departments.update');
    Route::post('/admin/jurusan/{department}/toggle', [AdminElectionController::class, 'toggleDepartment'])->name('admin.departments.toggle');
    Route::delete('/admin/jurusan/{department}', [AdminElectionController::class, 'destroyDepartment'])->name('admin.departments.destroy');

    // Manajemen Sesi Pemilihan
    Route::get('/admin/sesi', [AdminElectionController::class, 'sessions'])->name('admin.sessions');
    Route::post('/admin/sesi', [AdminElectionController::class, 'storeSession'])->name('admin.sessions.store');
    Route::put('/admin/sesi/{session}', [AdminElectionController::class, 'updateSession'])->name('admin.sessions.update');
    Route::post('/admin/sesi/{session}/toggle', [AdminElectionController::class, 'toggleSession'])->name('admin.sessions.toggle');
    Route::delete('/admin/sesi/{session}', [AdminElectionController::class, 'destroySession'])->name('admin.sessions.destroy');

    // Manajemen Pemilih
    Route::get('/admin/pemilih', [AdminElectionController::class, 'voters'])->name('admin.voters');
    Route::post('/admin/pemilih', [AdminElectionController::class, 'storeVoter'])->name('admin.voters.store');
    Route::post('/admin/pemilih/import', [AdminElectionController::class, 'importVoters'])->name('admin.voters.import');
    Route::put('/admin/pemilih/{voter}', [AdminElectionController::class, 'updateVoter'])->name('admin.voters.update');
    Route::delete('/admin/pemilih/{voter}', [AdminElectionController::class, 'destroyVoter'])->name('admin.voters.destroy');
    Route::post('/admin/pemilih/bulk-delete', [AdminElectionController::class, 'bulkDestroyVoters'])->name('admin.voters.bulk_destroy');
    Route::post('/admin/pemilih/destroy-all', [AdminElectionController::class, 'destroyAllVoters'])->name('admin.voters.destroy_all');
    Route::post('/admin/pemilih/{voter}/reset-token', [AdminElectionController::class, 'regenerateToken'])->name('admin.voters.regenerate_token');

    // Pengaturan Pemilihan
    Route::get('/admin/pengaturan', [AdminElectionController::class, 'settings'])->name('admin.settings');
    Route::match(['put', 'post'], '/admin/pengaturan', [AdminElectionController::class, 'updateSettings'])->name('admin.settings.update');

    // Profile Panitia
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
