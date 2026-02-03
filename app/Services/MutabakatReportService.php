<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\ReconciliationBank;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
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

    /**
     * Firma–banka mail raporu için sayfalı ve filtrelenebilir sorgu.
     *
     * @param array{customer_id?: int|null, year?: int|null, mail_status?: string|null, reply_status?: string|null} $filters
     */
    public function getMailReportBanksPaginated(array $filters, int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        $query = ReconciliationBank::query()
            ->with(['customer', 'request'])
            ->orderBy('mail_sent_at', 'desc')
            ->orderBy('id', 'desc');

        if (!empty($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }
        if (isset($filters['year']) && $filters['year'] !== null && $filters['year'] !== '') {
            $query->whereHas('request', fn ($q) => $q->where('year', $filters['year']));
        }
        if (!empty($filters['mail_status'])) {
            $query->where('mail_status', $filters['mail_status']);
        }
        if (!empty($filters['reply_status'])) {
            $query->where('reply_status', $filters['reply_status']);
        }

        return $query->paginate($perPage, ['*'], 'page', $page);
    }
}
