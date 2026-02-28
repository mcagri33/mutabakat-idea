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

// Haftalık mutabakat raporunu adminlere e-posta ile gönder (her Pazartesi 09:00)
Schedule::command('mutabakat:send-weekly-report')
    ->weeklyOn(1, '09:00')
    ->timezone('Europe/Istanbul');

// Her Pazar 10:00 - cevap gelmeyen bankalara hatırlatma (kaşe bekleyen ve cevap gelenler hariç)
Schedule::command('mutabakat:send-sunday-reminders')
    ->weeklyOn(0, '10:00')
    ->timezone('Europe/Istanbul');
