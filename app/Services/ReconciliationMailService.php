<?php

namespace App\Services;

use App\Models\ReconciliationBank;
use App\Models\ReconciliationEmail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class ReconciliationMailService
{
    protected $mutabakatService;

    public function __construct(MutabakatService $mutabakatService)
    {
        $this->mutabakatService = $mutabakatService;
    }

    public function sendBankMail(ReconciliationBank $bank): void
    {
        $request  = $bank->request;
        $customer = $bank->customer;

        if (!$bank->officer_email) {
            throw new \Exception("Banka yetkilisinin e-postası yok.");
        }

        // İlişkileri yükle
        if (!$request) {
            $bank->load('request');
            $request = $bank->request;
        }

        if (!$customer) {
            $bank->load('customer');
            $customer = $bank->customer;
        }

        if (!$request || !$customer) {
            throw new \Exception("Talep veya müşteri bilgisi bulunamadı.");
        }

        $subject = "{$customer->name} - {$request->year} Yılı Banka Mutabakatı";

        $bodyViewData = [
            'bank'     => $bank,
            'customer' => $customer,
            'request'  => $request,
        ];

        try {
            // DOCX'i doldur (sadece temel bilgiler) ve PDF'e çevir
            // Hesap bilgileri boş bırakılacak - banka dolduracak
            Log::info('Banka mutabakat PDF\'i oluşturuluyor', [
                'bank_id' => $bank->id,
                'customer_id' => $customer->id,
                'request_id' => $request->id
            ]);

            $pdfPath = $this->mutabakatService->generatePdf(
                $request,
                $customer,
                $bank
            );

            // Mail gönder - PDF ekle (DOCX yerine)
            // CC adresleri: mutabakat@ideadenetim.com.tr + müşteri email'i (varsa)
            $ccAddresses = ['mutabakat@ideadenetim.com.tr'];
            
            if ($customer->email) {
                $ccAddresses[] = $customer->email;
            }
            
            \Mail::send('emails.bank-reconciliation', $bodyViewData, function ($message) use ($bank, $customer, $subject, $pdfPath, $ccAddresses, $request) {
                $message->to($bank->officer_email)
                    ->cc($ccAddresses)
                    ->subject($subject)
                    ->attach($pdfPath, [
                        'as'   => 'Banka-Mutabakat-Mektubu.pdf',
                        'mime' => 'application/pdf',
                    ]);
                
                // Mutabakat isteğindeki ek dosyaları ekle
                if ($request->attachments && is_array($request->attachments)) {
                    foreach ($request->attachments as $attachmentPath) {
                        $fullPath = storage_path('app/' . $attachmentPath);
                        if (file_exists($fullPath)) {
                            $fileName = basename($attachmentPath);
                            $mimeType = mime_content_type($fullPath) ?: 'application/octet-stream';
                            
                            $message->attach($fullPath, [
                                'as'   => $fileName,
                                'mime' => $mimeType,
                            ]);
                        }
                    }
                }
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

            Log::info('Banka mutabakat maili başarıyla gönderildi', [
                'bank_id' => $bank->id,
                'email' => $bank->officer_email,
                'pdf_path' => $pdfPath
            ]);

        } catch (\Exception $e) {
            Log::error('Banka mutabakat maili gönderilemedi', [
                'bank_id' => $bank->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}
