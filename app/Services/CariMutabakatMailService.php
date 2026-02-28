<?php

namespace App\Services;

use App\Models\CariMutabakatItem;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CariMutabakatMailService
{
    public function sendItemMail(CariMutabakatItem $item): void
    {
        if (empty($item->email) || !filter_var($item->email, FILTER_VALIDATE_EMAIL)) {
            throw new \Exception("Geçerli e-posta adresi bulunamadı.");
        }

        if (empty($item->token)) {
            throw new \Exception("Cari mutabakat öğesi için token oluşturulmamış.");
        }

        $item->load(['request.customer']);
        $request = $item->request;
        $customer = $request->customer;

        if (!$request || !$customer) {
            throw new \Exception("Talep veya müşteri bilgisi bulunamadı.");
        }

        $replyUrl = url("/cari-mutabakat/cevapla/{$item->token}");
        $subject = "{$customer->name} - {$request->year} Yılı Cari Hesap Mutabakatı - {$item->unvan}";

        $viewData = [
            'item' => $item,
            'request' => $request,
            'customer' => $customer,
            'replyUrl' => $replyUrl,
        ];

        $ccAddresses = ['mutabakat@ideadenetim.com.tr'];
        if ($item->cc_email && filter_var($item->cc_email, FILTER_VALIDATE_EMAIL)) {
            $ccAddresses[] = $item->cc_email;
        }

        Mail::send('emails.cari-mutabakat', $viewData, function ($message) use ($item, $subject, $ccAddresses) {
            $message->from('mutabakat@mg.ideadocs.com.tr', 'Mutabakat Yönetim Sistemi');
            $message->to($item->email)
                ->cc($ccAddresses)
                ->subject($subject);
        });

        Log::info('Cari mutabakat maili gönderildi', [
            'item_id' => $item->id,
            'email' => $item->email,
        ]);
    }

    /**
     * Hatırlatma maili gönder (haftalık otomatik gönderimler için).
     * Sadece cevap bekleyen (reply_status=pending) item'lara gönderilir.
     *
     * @param CariMutabakatItem $item
     * @param array<string>|null $overrideRecipients Test modunda item yerine bu e-postalara gönderilir (örn. admin listesi)
     */
    public function sendReminderMail(CariMutabakatItem $item, ?array $overrideRecipients = null): void
    {
        $item->load(['request.customer']);
        $request = $item->request;
        $customer = $item->request?->customer;

        if (!$request || !$customer) {
            throw new \Exception("Talep veya müşteri bilgisi bulunamadı.");
        }

        $recipients = $overrideRecipients ?? [$item->email];
        $recipients = array_filter($recipients);
        if (empty($recipients)) {
            throw new \Exception("Geçerli e-posta adresi bulunamadı.");
        }

        $isTest = $overrideRecipients !== null;

        if (!$isTest && (empty($item->token) || !filter_var($item->email, FILTER_VALIDATE_EMAIL))) {
            throw new \Exception("Item için token veya geçerli e-posta yok.");
        }

        $replyUrl = url("/cari-mutabakat/cevapla/{$item->token}");
        $subject = "Hatırlatma: {$customer->name} - {$request->year} Yılı Cari Hesap Mutabakatı - {$item->unvan}";
        if ($isTest) {
            $subject = '[TEST] ' . $subject;
        }

        $viewData = [
            'item' => $item,
            'request' => $request,
            'customer' => $customer,
            'replyUrl' => $replyUrl,
        ];

        $ccAddresses = ['mutabakat@ideadenetim.com.tr'];
        if (!$isTest && $item->cc_email && filter_var($item->cc_email, FILTER_VALIDATE_EMAIL)) {
            $ccAddresses[] = $item->cc_email;
        }

        Mail::send('emails.cari-mutabakat-reminder', $viewData, function ($message) use ($recipients, $subject, $ccAddresses, $isTest) {
            $message->from('mutabakat@mg.ideadocs.com.tr', 'Mutabakat Yönetim Sistemi');
            $message->to($recipients)->subject($subject);
            if (!$isTest) {
                $message->cc($ccAddresses);
            }
        });

        Log::info('Cari mutabakat hatırlatma maili gönderildi', ['item_id' => $item->id, 'test' => $isTest]);
    }
}
