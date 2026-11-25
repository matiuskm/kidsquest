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
    Route::resource('tugas', Parents\TaskController::class)->except('show')
        ->parameters(['tugas' => 'task'])->names('parent.tasks');
});

Route::middleware('role:child')->group(function () {
    Route::get('/misi', [Child\MissionController::class, 'index'])->name('child.missions');
    Route::post('/misi/{task}', [Child\MissionController::class, 'store'])->name('child.submit');
});

Route::get('/foto/{submission}', PhotoController::class)->middleware('auth')->name('photos.show');
