<?php

namespace App\Console\Commands;

use App\Mail\MutabakatRaporuMailable;
use App\Models\User;
use App\Services\MutabakatReportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendWeeklyMutabakatReport extends Command
{
    protected $signature = 'mutabakat:send-weekly-report';

    protected $description = 'Mutabakat raporunu (bankası olmayan firmalar + firma-banka mail özeti) adminlere haftalık e-posta ile gönderir';

    public function handle(MutabakatReportService $reportService): int
    {
        $this->info('Haftalık mutabakat raporu hazırlanıyor...');

        $admins = User::whereHas('roles', function ($q) {
            $q->where('name', 'admin')->orWhere('name', 'super-admin');
        })->get();

        if ($admins->isEmpty()) {
            $admins = User::all();
        }

        $emails = $admins->pluck('email')->filter()->unique()->values();
        if ($emails->isEmpty()) {
            $this->warn('Admin e-posta adresi bulunamadı. Rapor gönderilmedi.');
            Log::warning('Haftalık mutabakat raporu: Alıcı bulunamadı.');
            return self::FAILURE;
        }

        try {
            $customersWithoutBanks = $reportService->getCustomersWithoutBanks();
            $mailReportRows = $reportService->getMailReportRows();

            $mailable = new MutabakatRaporuMailable($customersWithoutBanks, $mailReportRows);
            Mail::to($emails->toArray())->send($mailable);

            $this->info('Rapor ' . $emails->count() . ' admin adresine gönderildi: ' . $emails->implode(', '));
            Log::info('Haftalık mutabakat raporu gönderildi', [
                'recipient_count' => $emails->count(),
                'recipients' => $emails->toArray(),
            ]);

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Rapor gönderilemedi: ' . $e->getMessage());
            Log::error('Haftalık mutabakat raporu gönderilemedi', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return self::FAILURE;
        }
    }
}
