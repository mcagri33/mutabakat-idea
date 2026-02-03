<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\ManualReconciliationEntry;
use App\Models\ReconciliationBank;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as LengthAwarePaginatorConcrete;
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

    /**
     * Sistem (ReconciliationBank) + manuel girişleri birleştirip sayfalı döndürür.
     * Her satırda 'source' => 'sistem' | 'manuel' alanı bulunur.
     *
     * @param array{customer_id?: int|null, year?: int|null, mail_status?: string|null, reply_status?: string|null} $filters
     * @return LengthAwarePaginator<array<string, mixed>>
     */
    public function getMergedMailReportPaginated(array $filters, int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        $systemBanks = ReconciliationBank::query()
            ->with(['customer', 'request'])
            ->orderBy('mail_sent_at', 'desc')
            ->orderBy('id', 'desc');

        if (!empty($filters['customer_id'])) {
            $systemBanks->where('customer_id', $filters['customer_id']);
        }
        if (isset($filters['year']) && $filters['year'] !== null && $filters['year'] !== '') {
            $systemBanks->whereHas('request', fn ($q) => $q->where('year', $filters['year']));
        }
        if (!empty($filters['mail_status'])) {
            $systemBanks->where('mail_status', $filters['mail_status']);
        }
        if (!empty($filters['reply_status'])) {
            $systemBanks->where('reply_status', $filters['reply_status']);
        }

        $systemRows = $systemBanks->get()->map(function ($bank) {
            $replyAt = $bank->reply_received_at?->format('Y-m-d H:i') ?? $bank->mail_sent_at?->format('Y-m-d H:i') ?? '';
            return [
                'customer_name'      => $bank->customer?->name ?? '-',
                'bank_name'          => $bank->bank_name ?? '-',
                'year'               => $bank->request?->year ?? '-',
                'mail_sent_at'       => $bank->mail_sent_at ? $bank->mail_sent_at->format('d.m.Y H:i') : '-',
                'mail_status'        => $bank->mail_status ?? 'pending',
                'reply_status'       => $bank->reply_status ?? 'pending',
                'reply_received_at'  => $bank->reply_received_at ? $bank->reply_received_at->format('d.m.Y H:i') : '-',
                'source'             => 'sistem',
                'sort_at'            => $replyAt,
            ];
        });

        $manualQuery = ManualReconciliationEntry::query()->with('customer');
        if (!empty($filters['customer_id'])) {
            $manualQuery->where('customer_id', $filters['customer_id']);
        }
        if (isset($filters['year']) && $filters['year'] !== null && $filters['year'] !== '') {
            $manualQuery->where('year', $filters['year']);
        }
        $manualRows = $manualQuery->orderBy('reply_received_at', 'desc')->orderBy('requested_at', 'desc')->get()->map(function ($entry) {
            $replyAt = $entry->reply_received_at?->format('Y-m-d H:i') ?? $entry->requested_at?->format('Y-m-d H:i') ?? '';
            return [
                'customer_name'      => $entry->customer?->name ?? '-',
                'bank_name'          => $entry->bank_name . ($entry->branch_name ? ' - ' . $entry->branch_name : ''),
                'year'               => (string) $entry->year,
                'mail_sent_at'       => $entry->requested_at ? $entry->requested_at->format('d.m.Y') : '-',
                'mail_status'        => 'sent',
                'reply_status'       => $entry->reply_received_at ? 'received' : 'pending',
                'reply_received_at'  => $entry->reply_received_at ? $entry->reply_received_at->format('d.m.Y') : '-',
                'source'             => 'manuel',
                'sort_at'            => $replyAt,
            ];
        });

        $merged = $systemRows->concat($manualRows)->sortByDesc('sort_at')->values();
        $total = $merged->count();
        $slice = $merged->slice(($page - 1) * $perPage, $perPage)->values();

        return new LengthAwarePaginatorConcrete($slice->all(), $total, $perPage, $page, [
            'path' => request()->url(),
            'pageName' => 'page',
        ]);
    }
}
