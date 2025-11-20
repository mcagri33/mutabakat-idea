<?php

namespace App\Services;

use App\Models\Customer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
            Log::error('Customer Sync Failed: API configuration missing');
            return false;
        }
        
        // API çağrısı
        $url = $baseUrl . '/users';
        Log::info('Customer Sync: Starting', ['url' => $url]);
        
        $response = Http::withHeaders([
            'X-API-Key' => $apiKey,
            'Accept'    => 'application/json',
        ])->timeout(30)->get($url, [
            'role'     => 'Customer',
            'per_page' => 2000,
        ]);

        if (!$response->successful()) {
            Log::error('Customer Sync Failed: API unreachable', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            return false;
        }

        $json = $response->json();
        $data = $json['data'] ?? [];
        
        // Log response structure
        Log::info('Customer Sync: API Response', [
            'status' => $response->status(),
            'json_keys' => array_keys($json),
            'data_count' => count($data),
            'first_item_keys' => !empty($data) ? array_keys($data[0] ?? []) : null,
            'first_item' => $data[0] ?? null,
        ]);

        if (empty($data)) {
            Log::warning('Customer Sync: No data received', [
                'response' => $json,
            ]);
            return false;
        }

        $synced = 0;
        $errors = 0;
        
        foreach ($data as $index => $item) {
            try {
                // ID field'ını kontrol et
                if (!isset($item['id'])) {
                    Log::warning('Customer Sync: Missing ID field', [
                        'index' => $index,
                        'item_keys' => array_keys($item),
                        'item' => $item,
                    ]);
                    $errors++;
                    continue;
                }
                
                $result = Customer::updateOrCreate(
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
                
                // İlk 3 kayıt için detaylı log
                if ($synced <= 3) {
                    Log::info('Customer Sync: Saved customer', [
                        'external_id' => $item['id'],
                        'name' => $item['name'] ?? 'Unknown',
                        'wasRecentlyCreated' => $result->wasRecentlyCreated,
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Customer Sync: Failed to save customer', [
                    'index' => $index,
                    'item' => $item,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                $errors++;
            }
        }
        
        Log::info('Customer Sync Completed', [
            'synced_count' => $synced,
            'error_count' => $errors,
            'total_received' => count($data),
        ]);

        return $synced > 0;
    }
}
