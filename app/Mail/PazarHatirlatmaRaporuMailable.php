<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PazarHatirlatmaRaporuMailable extends Mailable
{
    use Queueable, SerializesModels;

    public array $sentItems;
    public array $failedItems;
    public array $excludedKase;
    public array $excludedReceived;
    public string $reportDate;

    public function __construct(array $sentItems, array $failedItems, array $excludedKase, array $excludedReceived)
    {
        $this->sentItems      = $sentItems;
        $this->failedItems    = $failedItems;
        $this->excludedKase   = $excludedKase;
        $this->excludedReceived = $excludedReceived;
        $this->reportDate     = now()->format('d.m.Y H:i');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: config('mail.from.address', 'mutabakat@mg.ideadocs.com.tr'),
            subject: 'Pazar Hatırlatma Raporu - ' . $this->reportDate,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.pazar-hatirlatma-raporu');
    }
}
