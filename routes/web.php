<?php

use Illuminate\Support\Facades\Route;

Route::get('/sync-customers', function () {
    app(\App\Services\CustomerSyncService::class)->sync();
    return 'Customer sync completed!';
});
