<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('feeds:refresh')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('digests:send daily')->dailyAt('06:00')->timezone('America/New_York');
Schedule::command('digests:send weekly')->weeklyOn(1, '06:00')->timezone('America/New_York');
