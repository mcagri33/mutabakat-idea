<?php

namespace App\Services;

use App\Models\ReconciliationBank;
use App\Models\ReconciliationEmail;
use Illuminate\Support\Facades\Mail;

class ReconciliationMailService
{
   public function sendBankMail(ReconciliationBank $bank): void
{
    $request  = $bank->request;
    $customer = $bank->customer;

    if (! $bank->officer_email) {
        throw new \Exception("Banka yetkilisinin e-postası yok.");
    }

    $subject = "{$customer->name} - {$request->year} Yılı Banka Mutabakatı";

    $bodyViewData = [
        'bank'     => $bank,
        'customer' => $customer,
        'request'  => $request,
    ];

    // Word dosyasının yolu
    $wordFile = storage_path('app/mutabakat_templates/mutabakat_banka_sablon.docx');

    if (! file_exists($wordFile)) {
        throw new \Exception("Word şablonu bulunamadı.");
    }

    // Mail gönder
    \Mail::send('emails.bank-reconciliation', $bodyViewData, function ($message) use ($bank, $customer, $subject, $wordFile) {

        $message->to($bank->officer_email)
            ->cc($customer->email) // ⭐ Firma CC
            ->subject($subject)
            ->attach($wordFile, [
                'as'   => 'Mutabakat-Mektubu.docx',
                'mime' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ]);
    });

    // Mail log
    ReconciliationEmail::create([
        'request_id'  => $request->id,
        'bank_id'     => $bank->id,
        'sent_to'     => $bank->officer_email,
        'subject'     => $subject,
        'body'        => view('emails.bank-reconciliation', $bodyViewData)->render(),
        'status'      => 'sent',
        'sent_at'     => now(),
    ]);
}

}
