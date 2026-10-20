<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Hosting condiviso: nessun worker della coda sempre attivo.
// La coda viene elaborata periodicamente dal cron tramite lo scheduler.
Schedule::command('queue:work --stop-when-empty')
    ->everyMinute()
    ->withoutOverlapping();

Schedule::command('certificates:send-reminders')
    ->dailyAt('08:00')
    ->timezone('Europe/Rome')
    ->withoutOverlapping();

Schedule::command('admins:send-daily-digest')
    ->dailyAt('08:05')
    ->timezone('Europe/Rome')
    ->withoutOverlapping();
