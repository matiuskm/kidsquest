<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Child;
use App\Http\Controllers\ChildLoginController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Parents;
use App\Http\Controllers\PhotoController;
use App\Http\Controllers\PushSubscriptionController;
use App\Http\Middleware\AwardBirthdayBonus;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::bind('child', fn ($id) => User::children()->findOrFail($id));

Route::get('/', HomeController::class)->name('home');
Route::get('/masuk-anak', [ChildLoginController::class, 'pick'])->name('child.pick');
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
    Route::resource('anak', Parents\ChildController::class)->except('show')
        ->parameters(['anak' => 'child'])->names('parent.children');
    Route::resource('hadiah', Parents\RewardController::class)->except('show')
        ->parameters(['hadiah' => 'reward'])->names('parent.rewards');
    Route::resource('tugas', Parents\TaskController::class)->except('show')
        ->parameters(['tugas' => 'task'])->names('parent.tasks');
});

Route::middleware(['role:child', AwardBirthdayBonus::class])->group(function () {
    Route::get('/misi', [Child\MissionController::class, 'index'])->name('child.missions');
    Route::get('/saya', [Child\ProfileController::class, 'edit'])->name('child.profile');
    Route::put('/saya', [Child\ProfileController::class, 'update'])->name('child.profile.update');
    Route::get('/hadiah', [Child\RewardController::class, 'index'])->name('child.rewards');
    Route::post('/hadiah/{reward}', [Child\RewardController::class, 'claim'])->name('child.claim');
    Route::post('/misi/{task}', [Child\MissionController::class, 'store'])->name('child.submit');
});

Route::middleware('auth')->group(function () {
    Route::post('/push/subscribe', [PushSubscriptionController::class, 'store'])->name('push.subscribe');
    Route::delete('/push/subscribe', [PushSubscriptionController::class, 'destroy'])->name('push.unsubscribe');
});

Route::get('/foto/{submission}', PhotoController::class)->middleware('auth')->name('photos.show');
