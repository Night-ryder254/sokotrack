<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Daily summary of everything at or below its low-stock threshold
Schedule::command('sokotrack:low-stock-digest')->dailyAt('07:00');

// Release stock and points held by M-Pesa payments that never got an answer
Schedule::command('orders:release-stale-mpesa')->everyFiveMinutes();
