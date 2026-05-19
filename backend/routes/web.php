<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

// === Auth (manual, без Breeze) ===
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    // === Sprint 3: дашборд статистики посещений ===
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/stats.json', [\App\Http\Controllers\VisitController::class, 'stats'])
        ->name('dashboard.stats');
});

// Демо-страница для проверки трекера локально (никакой авторизации)
Route::view('/tracker-demo', 'tracker-demo')->name('tracker.demo');
