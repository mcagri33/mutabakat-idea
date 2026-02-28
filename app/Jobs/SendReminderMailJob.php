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

class SendReminderMailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 300; // 5 dakika (PDF + LibreOffice dönüşümü)

    /**
     * @param ReconciliationBank $bank
     * @param array<string>|null $overrideRecipients Test modunda banka yerine bu adreslere gönderilir
     */
    public function __construct(
        public ReconciliationBank $bank,
        public ?array $overrideRecipients = null
    ) {
        $this->onQueue('emails');
    }

    public function handle(ReconciliationMailService $mailService): void
    {
        try {
            Log::info('Pazar hatırlatma mail job başlatıldı', [
                'bank_id' => $this->bank->id,
                'test' => $this->overrideRecipients !== null,
            ]);

            $mailService->sendReminderMail($this->bank, $this->overrideRecipients);

            Log::info('Pazar hatırlatma mail job tamamlandı', ['bank_id' => $this->bank->id]);
        } catch (\Exception $e) {
            Log::error('Pazar hatırlatma mail job başarısız', [
                'bank_id' => $this->bank->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Pazar hatırlatma mail job kalıcı olarak başarısız', [
            'bank_id' => $this->bank->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
