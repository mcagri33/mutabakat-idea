<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Services\FirmCustomMailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendFirmCustomMailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 120;

    public function __construct(
        public Customer $customer,
        public string $subject,
        public string $content,
        public ?int $year,
        public array $attachments = []
    ) {
        $this->onQueue('emails');
    }

    public function handle(FirmCustomMailService $mailService): void
    {
        try {
            $mailService->sendToCustomer(
                $this->customer,
                $this->subject,
                $this->content,
                $this->year,
                $this->attachments
            );
            Log::info('Firma mail job tamamlandı', ['customer_id' => $this->customer->id]);
        } catch (\Exception $e) {
            Log::error('Firma mail job başarısız', [
                'customer_id' => $this->customer->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
