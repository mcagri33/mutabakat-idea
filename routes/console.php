<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Her 5 dakikada bir gelen mail'leri kontrol et
Schedule::command('reconciliation:check-mails')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->runInBackground();
