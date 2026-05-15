<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ProfileController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Monitor\MonitorController;
use App\Http\Controllers\Monitor\MonitorHistoryController;

Route::prefix('auth')->as('auth.')->group(function () {
    Route::post('/login', [LoginController::class, 'store'])
        ->name('login');

    Route::post('/register', [RegisterController::class, 'store'])
        ->name('register');

    Route::middleware(['auth:sanctum'])->group(function () {
        Route::get('/profile', [ProfileController::class, 'show'])
            ->name('profile');

        Route::post('/logout', [LoginController::class, 'destroy'])
            ->name('logout');
    });
});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/monitors', [MonitorController::class, 'index'])
        ->name('monitors.index');

    Route::post('/monitors', [MonitorController::class, 'store'])
        ->name('monitors.store');

    Route::get('/monitors/{monitor}/history', [MonitorHistoryController::class, 'index'])
        ->name('monitors.history');
});
