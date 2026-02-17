<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerBank;
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

        $yearForMissing = isset($filters['year']) && $filters['year'] !== null && $filters['year'] !== ''
            ? (int) $filters['year']
            : now()->year;

        $includeMissingBanks = empty($filters['mail_status']) && empty($filters['reply_status']);

        $missingRows = collect();
        if ($includeMissingBanks) {
            $customerBankQuery = CustomerBank::query()
                ->with('customer')
                ->whereHas('customer', fn ($q) => $q->where('is_active', true));

            if (! empty($filters['customer_id'])) {
                $customerBankQuery->where('customer_id', $filters['customer_id']);
            }

            $systemBankKeys = ReconciliationBank::query()
                ->whereHas('request', fn ($q) => $q->where('year', $yearForMissing))
                ->when(! empty($filters['customer_id']), fn ($q) => $q->where('customer_id', $filters['customer_id']))
                ->get()
                ->map(function ($rb) {
                    if ($rb->customer_bank_id) {
                        return 'id:' . $rb->customer_bank_id;
                    }

                    return 'key:' . $rb->customer_id . '|' . trim($rb->bank_name ?? '');
                })
                ->unique()
                ->values()
                ->all();

            $manualBankKeys = ManualReconciliationEntry::query()
                ->where('year', $yearForMissing)
                ->when(! empty($filters['customer_id']), fn ($q) => $q->where('customer_id', $filters['customer_id']))
                ->get()
                ->map(fn ($e) => $e->customer_id . '|' . trim($e->bank_name))
                ->unique()
                ->values()
                ->all();

            $customerBanks = $customerBankQuery->get();

            foreach ($customerBanks as $cb) {
                $inSystem = in_array('id:' . $cb->id, $systemBankKeys)
                    || in_array('key:' . $cb->customer_id . '|' . trim($cb->bank_name), $systemBankKeys);
                $manualKey = $cb->customer_id . '|' . trim($cb->bank_name);
                $inManual = in_array($manualKey, $manualBankKeys);

                if (! $inSystem && ! $inManual) {
                    $missingRows->push([
                        'customer_name'      => $cb->customer?->name ?? '-',
                        'bank_name'          => $cb->bank_name . ($cb->branch_name ? ' - ' . $cb->branch_name : ''),
                        'year'               => (string) $yearForMissing,
                        'mail_sent_at'       => '-',
                        'mail_status'        => 'pending',
                        'reply_status'       => 'pending',
                        'reply_received_at'  => '-',
                        'source'             => 'banka_maili_gelmemis',
                        'sort_at'            => '',
                    ]);
                }
            }
        }

        $merged = $systemRows->concat($manualRows)->concat($missingRows)->sortByDesc('sort_at')->values();
        $total = $merged->count();
        $slice = $merged->slice(($page - 1) * $perPage, $perPage)->values();

        return new LengthAwarePaginatorConcrete($slice->all(), $total, $perPage, $page, [
            'path' => request()->url(),
            'pageName' => 'page',
        ]);
    }

    /**
     * Firma gönderim durumu raporu (yıl bazlı, manuel dahil).
     * Her firma için: banka sayısı, sistemden gönderilen, manuel giriş sayısı, durum.
     *
     * @return LengthAwarePaginator<array<string, mixed>>
     */
    public function getFirmSendingStatusPaginated(int $year, int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        $customers = Customer::query()
            ->where('is_active', true)
            ->withCount('banks')
            ->orderBy('name')
            ->get();

        $sentCounts = ReconciliationBank::query()
            ->whereHas('request', fn ($q) => $q->where('year', $year))
            ->where('mail_status', 'sent')
            ->selectRaw('customer_id, count(*) as c')
            ->groupBy('customer_id')
            ->pluck('c', 'customer_id');

        $manualCounts = ManualReconciliationEntry::query()
            ->where('year', $year)
            ->selectRaw('customer_id, count(*) as c')
            ->groupBy('customer_id')
            ->pluck('c', 'customer_id');

        $rows = $customers->map(function ($customer) use ($year, $sentCounts, $manualCounts) {
            $bankCount = (int) $customer->banks_count;
            $sentCount = (int) ($sentCounts[$customer->id] ?? 0);
            $manualCount = (int) ($manualCounts[$customer->id] ?? 0);

            if ($manualCount > 0) {
                $status = 'manuel_ile';
            } elseif ($bankCount === 0) {
                $status = 'banka_eklenmemis';
            } elseif ($sentCount >= $bankCount) {
                $status = 'hepsi_gonderildi';
            } elseif ($sentCount > 0) {
                $status = 'kismen';
            } else {
                $status = 'gonderilmedi';
            }

            return [
                'customer_id'   => $customer->id,
                'customer_name' => $customer->name,
                'year'          => $year,
                'bank_count'    => $bankCount,
                'sent_count'    => $sentCount,
                'manual_count'  => $manualCount,
                'status'        => $status,
            ];
        })->values();

        $total = $rows->count();
        $slice = $rows->slice(($page - 1) * $perPage, $perPage)->values();

        return new LengthAwarePaginatorConcrete($slice->all(), $total, $perPage, $page, [
            'path' => request()->url(),
            'pageName' => 'page',
        ]);
    }
}
