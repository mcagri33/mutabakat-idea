<?php

use Illuminate\Support\Facades\Route;

Route::get('/sync-customers', function () {
    try {
        $baseUrl = config('services.reconciliation_api.base_url');
        $apiKey = config('services.reconciliation_api.key');
        
        // API ayarlarını kontrol et
        if (!$baseUrl || !$apiKey) {
            return "API configuration missing!<br>MAIN_API_URL: " . ($baseUrl ?: 'NOT SET') . "<br>MAIN_API_KEY: " . ($apiKey ? 'SET' : 'NOT SET');
        }
        
        $service = app(\App\Services\CustomerSyncService::class);
        
        // API çağrısını test et
        $response = \Illuminate\Support\Facades\Http::withHeaders([
            'X-API-Key' => $apiKey,
            'Accept'    => 'application/json',
        ])->get($baseUrl . '/users', [
            'role'     => 'Customer',
            'per_page' => 2000,
        ]);
        
        $info = [
            'API URL' => $baseUrl . '/users',
            'Status' => $response->status(),
            'Has Key' => !empty($apiKey),
        ];
        
        if ($response->successful()) {
            $json = $response->json();
            $data = $json['data'] ?? [];
            
            $info['Response Keys'] = array_keys($json);
            $info['Data Count'] = count($data);
            $info['First Item Keys'] = !empty($data) ? array_keys($data[0] ?? []) : 'No data';
            
            $result = $service->sync();
            $count = \App\Models\Customer::count();
            
            $info['Sync Result'] = $result ? 'Success' : 'Failed';
            $info['Total Customers in DB'] = $count;
            
            return "<pre>" . print_r($info, true) . "</pre>";
        } else {
            $info['Error'] = $response->body();
            return "<pre>" . print_r($info, true) . "</pre>";
        }
    } catch (\Exception $e) {
        return "Error: " . $e->getMessage() . "<br>Trace: " . $e->getTraceAsString();
    }
});
