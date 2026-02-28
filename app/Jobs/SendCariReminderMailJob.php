<?php

namespace App\Jobs;

use App\Models\CariMutabakatItem;
use App\Services\CariMutabakatMailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendCariReminderMailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 120;

    /**
     * @param CariMutabakatItem $item
     * @param array<string>|null $overrideRecipients Test modunda item yerine bu adreslere gönderilir
     */
    public function __construct(
        public CariMutabakatItem $item,
        public ?array $overrideRecipients = null
    ) {
        $this->onQueue('emails');
    }

    public function handle(CariMutabakatMailService $mailService): void
    {
        try {
            Log::info('Cari hatırlatma mail job başlatıldı', [
                'item_id' => $this->item->id,
                'test' => $this->overrideRecipients !== null,
            ]);

            $mailService->sendReminderMail($this->item, $this->overrideRecipients);

            Log::info('Cari hatırlatma mail job tamamlandı', ['item_id' => $this->item->id]);
        } catch (\Exception $e) {
            Log::error('Cari hatırlatma mail job başarısız', [
                'item_id' => $this->item->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Cari hatırlatma mail job kalıcı olarak başarısız', [
            'item_id' => $this->item->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
