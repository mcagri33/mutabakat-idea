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
        // API çağrısı
        $response = Http::withHeaders([
            'X-API-Key' => config('services.reconciliation_api.key'),
            'Accept'    => 'application/json',
        ])->get(config('services.reconciliation_api.base_url') . '/users', [
            'role'     => 'Customer',
            'per_page' => 2000, // geniş limit çünkü firmalar çok olabilir
        ]);

        if (!$response->successful()) {
            logger()->error('Customer Sync Failed: API unreachable', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            return false;
        }

        $data = $response->json()['data'] ?? [];

        foreach ($data as $item) {
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
        }

        return true;
    }
}
