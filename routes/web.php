<?php

use Illuminate\Support\Facades\Route;

Route::get('/sync-customers', function () {
    try {
        $baseUrl = config('services.reconciliation_api.base_url');
        $apiKey = config('services.reconciliation_api.key');
        
        if (!$baseUrl || !$apiKey) {
            return "API configuration missing!<br>MAIN_API_URL: " . ($baseUrl ?: 'NOT SET') . "<br>MAIN_API_KEY: " . ($apiKey ? 'SET' : 'NOT SET');
        }
        
        $service = app(\App\Services\CustomerSyncService::class);
        
        // Test URL'i göster
        $testUrl = $baseUrl . '/users';
        
        $response = \Illuminate\Support\Facades\Http::withHeaders([
            'X-API-Key' => $apiKey,
            'Accept'    => 'application/json',
        ])->get($testUrl, [
            'role'     => 'Customer',
            'per_page' => 10, // Test için küçük sayı
        ]);
        
        $info = [
            'Base URL' => $baseUrl,
            'Full URL' => $testUrl,
            'Status' => $response->status(),
            'Has Key' => !empty($apiKey),
        ];
        
        if ($response->successful()) {
            $json = $response->json();
            $data = $json['data'] ?? [];
            
            $info['Response Keys'] = array_keys($json);
            $info['Data Count'] = count($data);
            $info['First Item'] = $data[0] ?? 'No data';
            
            // Şimdi gerçek sync'i çalıştır
            $result = $service->sync();
            $count = \App\Models\Customer::count();
            
            $info['Sync Result'] = $result ? 'Success' : 'Failed';
            $info['Total Customers in DB'] = $count;
            
            return "<pre>" . print_r($info, true) . "</pre>";
        } else {
            $info['Error'] = $response->body();
            $info['Error Status'] = $response->status();
            return "<pre>" . print_r($info, true) . "</pre>";
        }
    } catch (\Exception $e) {
        return "Error: " . $e->getMessage() . "<br>Trace: " . $e->getTraceAsString();
    }
});
