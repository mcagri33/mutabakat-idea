<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\ReconciliationBank;
use Illuminate\Support\Collection;

class MutabakatReportService
{
    /**
     * Banka tanımı olmayan (aktif) firmaları getirir.
     */
    public function getCustomersWithoutBanks(): Collection
    {
        return Customer::query()
            ->where('is_active', true)
            ->whereDoesntHave('banks')
            ->orderBy('name')
            ->get();
    }

    /**
     * Firma–banka bazlı mail raporu için satırları döndürür.
     * Her satır: customer_name, bank_name, year, mail_sent_at, mail_status, reply_status, reply_received_at
     *
     * @return array<int, array<string, mixed>>
     */
    public function getMailReportRows(): array
    {
        $banks = ReconciliationBank::query()
            ->with(['customer', 'request'])
            ->orderBy('mail_sent_at', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        $rows = [];
        foreach ($banks as $bank) {
            $rows[] = [
                'customer_name' => $bank->customer?->name ?? '-',
                'bank_name'     => $bank->bank_name,
                'year'          => $bank->request?->year ?? '-',
                'mail_sent_at'  => $bank->mail_sent_at?->format('d.m.Y H:i') ?? '-',
                'mail_status'   => $bank->mail_status,
                'reply_status'  => $bank->reply_status,
                'reply_received_at' => $bank->reply_received_at?->format('d.m.Y H:i') ?? '-',
            ];
        }
        return $rows;
    }
}
