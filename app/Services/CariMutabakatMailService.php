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
        if ($customer->email && filter_var($customer->email, FILTER_VALIDATE_EMAIL) && !in_array($customer->email, $ccAddresses)) {
            $ccAddresses[] = $customer->email;
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
}
