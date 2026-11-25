<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Child;
use App\Http\Controllers\ChildLoginController;
use App\Http\Controllers\Parents;
use App\Http\Controllers\PhotoController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ChildLoginController::class, 'pick'])->name('child.pick');
Route::post('/masuk-anak', [ChildLoginController::class, 'store'])->name('child.pin');

Route::get('/masuk', [AuthController::class, 'create'])->name('login');
Route::post('/masuk', [AuthController::class, 'store'])->middleware('throttle:10,1')->name('login.store');
Route::post('/keluar', [AuthController::class, 'destroy'])->name('logout');

Route::middleware('role:parent')->prefix('orangtua')->group(function () {
    Route::get('/', Parents\DashboardController::class)->name('parent.dashboard');
    Route::get('riwayat', Parents\HistoryController::class)->name('parent.history');
    Route::post('periksa/submission/{submission}/setuju', [Parents\ReviewController::class, 'approveSubmission'])->name('parent.submissions.approve');
    Route::post('periksa/submission/{submission}/tolak', [Parents\ReviewController::class, 'rejectSubmission'])->name('parent.submissions.reject');
    Route::post('periksa/klaim/{redemption}/setuju', [Parents\ReviewController::class, 'approveRedemption'])->name('parent.redemptions.approve');
    Route::post('periksa/klaim/{redemption}/tolak', [Parents\ReviewController::class, 'rejectRedemption'])->name('parent.redemptions.reject');
    Route::post('poin/{child}/sesuaikan', [Parents\PointsController::class, 'adjust'])->name('parent.points.adjust');
    Route::resource('hadiah', Parents\RewardController::class)->except('show')
        ->parameters(['hadiah' => 'reward'])->names('parent.rewards');
    Route::resource('tugas', Parents\TaskController::class)->except('show')
        ->parameters(['tugas' => 'task'])->names('parent.tasks');
});

Route::middleware('role:child')->group(function () {
    Route::get('/misi', [Child\MissionController::class, 'index'])->name('child.missions');
    Route::get('/hadiah', [Child\RewardController::class, 'index'])->name('child.rewards');
    Route::post('/hadiah/{reward}', [Child\RewardController::class, 'claim'])->name('child.claim');
    Route::post('/misi/{task}', [Child\MissionController::class, 'store'])->name('child.submit');
});

Route::get('/foto/{submission}', PhotoController::class)->middleware('auth')->name('photos.show');
