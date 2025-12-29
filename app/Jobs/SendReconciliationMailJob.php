<?php

namespace App\Jobs;

use App\Models\ReconciliationBank;
use App\Services\ReconciliationMailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendReconciliationMailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Maksimum deneme sayısı
     */
    public $tries = 3;

    /**
     * Timeout süresi (saniye)
     */
    public $timeout = 300; // 5 dakika

    /**
     * Create a new job instance.
     */
    public function __construct(
        public ReconciliationBank $bank
    ) {
        // Job'un hangi queue'da çalışacağını belirle
        $this->onQueue('emails');
    }

    /**
     * Execute the job.
     */
    public function handle(ReconciliationMailService $mailService): void
    {
        try {
            Log::info('Reconciliation mail job başlatıldı', [
                'bank_id' => $this->bank->id,
                'email' => $this->bank->officer_email,
            ]);

            // Mail gönder
            $mailService->sendBankMail($this->bank);
            
            // Başarılı → Güncelle
            $this->bank->update([
                'mail_status' => 'sent',
                'mail_sent_at' => now(),
            ]);

            // Request status'ünü güncelle
            $this->bank->updateRequestStatus();

            Log::info('Reconciliation mail job başarıyla tamamlandı', [
                'bank_id' => $this->bank->id,
            ]);

        } catch (\Exception $e) {
            // Hatalı → Logla ve güncelle
            $this->bank->update([
                'mail_status' => 'failed',
            ]);

            Log::error('Reconciliation mail job başarısız', [
                'bank_id' => $this->bank->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Retry için exception'ı tekrar fırlat
            throw $e;
        }
    }

    /**
     * Job başarısız olduğunda çağrılır
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Reconciliation mail job kalıcı olarak başarısız', [
            'bank_id' => $this->bank->id,
            'error' => $exception->getMessage(),
        ]);

        // Son denemede de başarısız olduysa durumu güncelle
        $this->bank->update([
            'mail_status' => 'failed',
        ]);
    }
}

