<?php

use Illuminate\Support\Facades\Route;

// Mailgun webhook (API route'ları zaten CSRF'den muaf)
Route::post('/webhook/mailgun/incoming', [\App\Http\Controllers\MailgunWebhookController::class, 'handleIncomingMail'])
    ->middleware('throttle:60,1') // Rate limiting
    ->name('webhook.mailgun.incoming');
