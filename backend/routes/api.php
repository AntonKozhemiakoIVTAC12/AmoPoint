<?php

use App\Http\Controllers\JokeController;
use App\Http\Controllers\VisitController;
use Illuminate\Support\Facades\Route;

// === Sprint 1: Jokes (jsonPaginate, формат JSON:API) ===
Route::get('/jokes', [JokeController::class, 'index'])->name('api.jokes.index');

// === Sprint 3: Visit counter ===
Route::post('/track', [VisitController::class, 'store'])
    ->middleware('throttle:60,1')
    ->name('api.visits.store');

Route::get('/visits', [VisitController::class, 'index'])
    ->middleware('throttle:30,1')
    ->name('api.visits.index');
