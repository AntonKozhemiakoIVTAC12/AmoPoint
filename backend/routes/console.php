<?php

use App\Console\Commands\FetchJokeCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// === Sprint 1: каждые 5 минут забираем свежий анекдот ===
Schedule::command(FetchJokeCommand::class)
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->runInBackground()
    ->onFailure(function () {
        logger()->warning('jokes:fetch scheduled run failed');
    });
