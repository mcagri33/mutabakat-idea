<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MutabakatRaporuMailable extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Bankası eklenmemiş firmalar listesi
     *
     * @var \Illuminate\Support\Collection<int, \App\Models\Customer>
     */
    public $customersWithoutBanks;

    /**
     * Firma bazlı mail raporu satırları
     * Her eleman: ['customer_name', 'year', 'sent_count', 'manual_count',
     *              'reply_received_count', 'reply_pending_count', 'summary']
     *
     * @var array<int, array<string, mixed>>
     */
    public $mailReportRows;

    /**
     * Rapor tarihi
     */
    public string $reportDate;

    public function __construct($customersWithoutBanks, $mailReportRows)
    {
        $this->customersWithoutBanks = $customersWithoutBanks;
        $this->mailReportRows = $mailReportRows;
        $this->reportDate = now()->format('d.m.Y H:i');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: config('mail.from.address', 'mutabakat@mg.ideadocs.com.tr'),
            subject: 'Mutabakat Raporu - ' . $this->reportDate,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.mutabakat-raporu',
        );
    }
}
