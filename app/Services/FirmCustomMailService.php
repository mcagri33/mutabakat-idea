<?php

namespace App\Services;

use App\Models\Customer;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class FirmCustomMailService
{
    protected array $defaultCc = [
        'mutabakat@ideadenetim.com.tr',
        'okanyurdbulan@hotmail.com',
        'okany@ideadenetim.com.tr',
    ];

    public function sendToCustomer(Customer $customer, string $subject, string $body, ?int $year = null, array $attachments = []): void
    {
        if (empty($customer->email) || !filter_var($customer->email, FILTER_VALIDATE_EMAIL)) {
            throw new \Exception("Firma e-posta adresi geçersiz veya boş: {$customer->name}");
        }

        $body = $this->replacePlaceholders($body, $customer, $year);
        $subject = $this->replacePlaceholders($subject, $customer, $year);

        Mail::send('emails.firma-custom-mail', [
            'body' => $body,
            'customer' => $customer,
        ], function ($message) use ($customer, $subject, $attachments) {
            $message->from('mutabakat@mg.ideadocs.com.tr', 'Mutabakat Yönetim Sistemi');
            $message->to($customer->email);
            $message->cc($this->defaultCc);
            $message->subject($subject);

            foreach ($attachments as $path) {
                $fullPath = Storage::disk('local')->path($path);
                if (file_exists($fullPath)) {
                    $message->attach($fullPath, ['as' => basename($path)]);
                }
            }
        });
    }

    protected function replacePlaceholders(string $text, Customer $customer, ?int $year = null): string
    {
        $year = $year ?? (now()->year - 1);
        return str_replace(
            ['{firma_adi}', '{firma_adı}', '{yil}', '{yıl}'],
            [$customer->name ?? '', $customer->name ?? '', (string) $year, (string) $year],
            $text
        );
    }
}
