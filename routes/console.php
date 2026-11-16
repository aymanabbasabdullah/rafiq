<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled tasks (Module-12 §8, Data-Lifecycle §2)
|--------------------------------------------------------------------------
*/

Schedule::command('rafiq:prune')->dailyAt('03:00')->withoutOverlapping()->onOneServer();
Schedule::command('rafiq:content:review-reminders')->dailyAt('07:00')->withoutOverlapping()->onOneServer();
Schedule::command('rafiq:ai:budget-check')->everyFifteenMinutes()->withoutOverlapping();
Schedule::command('queue:prune-failed', ['--hours' => 168])->daily();
