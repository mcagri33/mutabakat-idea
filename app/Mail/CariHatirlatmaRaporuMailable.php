<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CariHatirlatmaRaporuMailable extends Mailable
{
    use Queueable, SerializesModels;

    public array $sentItems;
    public string $reportDate;

    public function __construct(array $sentItems)
    {
        $this->sentItems = $sentItems;
        $this->reportDate = now()->format('d.m.Y H:i');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: config('mail.from.address', 'mutabakat@mg.ideadocs.com.tr'),
            subject: 'Cari Hatırlatma Raporu - ' . $this->reportDate,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.cari-hatirlatma-raporu');
    }
}
