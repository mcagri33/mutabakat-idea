<?php

namespace App\Console\Commands;

use App\Services\ReconciliationIncomingMailService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckReconciliationMails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reconciliation:check-mails';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Gelen mutabakat mail\'lerini kontrol et';

    /**
     * Execute the console command.
     */
    public function handle(ReconciliationIncomingMailService $service)
    {
        $this->info('Gelen mail\'ler kontrol ediliyor...');
        
        try {
            $service->checkIncomingMails();
            $this->info('Mail kontrolü tamamlandı.');
        } catch (\Exception $e) {
            $this->error('Hata: ' . $e->getMessage());
            Log::error('Mail kontrol hatası', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return Command::FAILURE;
        }
        
        return Command::SUCCESS;
    }
}
