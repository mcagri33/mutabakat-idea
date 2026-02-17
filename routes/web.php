<?php

use App\Http\Controllers\CariMutabakatReplyController;
use Illuminate\Support\Facades\Route;

Route::get('/cari-mutabakat/cevapla/{token}', [CariMutabakatReplyController::class, 'show'])
    ->name('cari-mutabakat.reply');
Route::post('/cari-mutabakat/cevapla/{token}', [CariMutabakatReplyController::class, 'store'])
    ->name('cari-mutabakat.reply.store');

Route::get('/sync-customers', function () {
    try {
        $baseUrl = config('services.reconciliation_api.base_url');
        $apiKey = config('services.reconciliation_api.key');
        
        if (!$baseUrl || !$apiKey) {
            return "API configuration missing!<br>MAIN_API_URL: " . ($baseUrl ?: 'NOT SET') . "<br>MAIN_API_KEY: " . ($apiKey ? 'SET' : 'NOT SET');
        }
        
        // Farklı URL kombinasyonlarını test et
        $testUrls = [
            $baseUrl . '/users',  // https://ideadocs.com.tr/api/reconciliation/users
            'https://ideadocs.com.tr/api/reconciliation/users',  // Tam URL
            'https://ideadocs.com.tr/reconciliation/users',  // /api olmadan
        ];
        
        $results = [];
        foreach ($testUrls as $testUrl) {
            try {
                $response = \Illuminate\Support\Facades\Http::withHeaders([
                    'X-API-Key' => $apiKey,
                    'Accept'    => 'application/json',
                ])->timeout(10)->get($testUrl, [
                    'role'     => 'Customer',
                    'per_page' => 5,
                ]);
                
                $results[$testUrl] = [
                    'status' => $response->status(),
                    'success' => $response->successful(),
                    'has_data' => !empty($response->json()['data'] ?? []),
                    'response' => $response->successful() ? 'OK' : $response->body(),
                ];
            } catch (\Exception $e) {
                $results[$testUrl] = [
                    'error' => $e->getMessage(),
                ];
            }
        }
        
        // En iyi çalışan URL'i bul
        $workingUrl = null;
        foreach ($results as $url => $result) {
            if (isset($result['success']) && $result['success']) {
                $workingUrl = $url;
                break;
            }
        }
        
        $info = [
            'Base URL Config' => $baseUrl,
            'Test Results' => $results,
            'Working URL' => $workingUrl,
        ];
        
        if ($workingUrl) {
            // Çalışan URL ile sync yap
            $service = app(\App\Services\CustomerSyncService::class);
            $result = $service->sync();
            $count = \App\Models\Customer::count();
            
            $info['Sync Result'] = $result ? 'Success' : 'Failed';
            $info['Total Customers in DB'] = $count;
        } else {
            $info['Error'] = 'No working URL found. Please check the API route configuration.';
        }
        
        return "<pre>" . print_r($info, true) . "</pre>";
    } catch (\Exception $e) {
        return "Error: " . $e->getMessage() . "<br>Trace: " . $e->getTraceAsString();
    }
});
