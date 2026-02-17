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

class SendCariMutabakatMailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 120;

    public function __construct(
        public CariMutabakatItem $item
    ) {
        $this->onQueue('emails');
    }

    public function handle(CariMutabakatMailService $mailService): void
    {
        try {
            $mailService->sendItemMail($this->item);

            $this->item->update(['mail_status' => 'sent']);

            $request = $this->item->request->fresh();
            $itemsWithEmail = $request->items()->whereNotNull('email')->where('email', '!=', '')->count();
            $itemsSent = $request->items()->where('mail_status', 'sent')->count();

            if ($itemsWithEmail > 0 && $itemsSent >= $itemsWithEmail) {
                $request->update([
                    'status' => 'sent',
                    'sent_at' => now(),
                ]);
            }

            Log::info('Cari mutabakat mail job tamamlandı', ['item_id' => $this->item->id]);
        } catch (\Exception $e) {
            Log::error('Cari mutabakat mail job başarısız', [
                'item_id' => $this->item->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
