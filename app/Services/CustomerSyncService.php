<?php

namespace App\Services;

use App\Models\Customer;
use Illuminate\Support\Facades\Http;

class CustomerSyncService
{
    /**
     * API'den customer listesi alıp database ile senkronize eder.
     */
    public function sync(): bool
    {
        $baseUrl = config('services.reconciliation_api.base_url');
        $apiKey = config('services.reconciliation_api.key');
        
        if (!$baseUrl || !$apiKey) {
            logger()->error('Customer Sync Failed: API configuration missing');
            return false;
        }
        
        // API çağrısı
        $response = Http::withHeaders([
            'X-API-Key' => $apiKey,
            'Accept'    => 'application/json',
        ])->timeout(30)->get($baseUrl . '/users', [
            'role'     => 'Customer',
            'per_page' => 2000,
        ]);

        if (!$response->successful()) {
            logger()->error('Customer Sync Failed: API unreachable', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            return false;
        }

        $json = $response->json();
        $data = $json['data'] ?? [];
        
        // Log response structure
        logger()->info('Customer Sync: API Response', [
            'status' => $response->status(),
            'json_keys' => array_keys($json),
            'data_count' => count($data),
            'first_item' => $data[0] ?? null,
        ]);

        if (empty($data)) {
            logger()->warning('Customer Sync: No data received', [
                'response' => $json,
            ]);
            return false;
        }

        $synced = 0;
        foreach ($data as $item) {
            try {
                Customer::updateOrCreate(
                    ['external_id' => $item['id']],
                    [
                        'uuid'       => $item['uuid'] ?? null,
                        'name'       => $item['name'] ?? 'Unknown',
                        'email'      => $item['email'] ?? null,
                        'company'    => $item['company'] ?? null,
                        'phone'      => $item['phone'] ?? null,
                        'is_active'  => ($item['status'] ?? 1) == 1,
                        'synced_at'  => now(),
                    ]
                );
                $synced++;
            } catch (\Exception $e) {
                logger()->error('Customer Sync: Failed to save customer', [
                    'item' => $item,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        logger()->info('Customer Sync Completed', [
            'synced_count' => $synced,
        ]);

        return $synced > 0;
    }
}
