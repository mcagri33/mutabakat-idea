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
            // CC adresleri başlangıçta boş
            $ccAddresses = [];
            
            // Otomatik CC email'lerini ekle (eğer isteniyorsa)
            if ($request->include_auto_cc ?? true) {
                $ccAddresses[] = 'mutabakat@ideadenetim.com.tr';
                
                if ($customer->email) {
                    $ccAddresses[] = $customer->email;
                }
            }
            
            // Kullanıcı tarafından girilen CC email'lerini parse et ve ekle
            if ($request->cc_emails) {
                // Virgül, noktalı virgül veya yeni satır ile ayrılmış email'leri parse et
                $customEmails = preg_split('/[,;\n\r]+/', $request->cc_emails);
                
                foreach ($customEmails as $email) {
                    $email = trim($email);
                    
                    // Email formatını kontrol et ve boş değilse ekle
                    if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        // Aynı email zaten listede yoksa ekle
                        if (!in_array($email, $ccAddresses)) {
                            $ccAddresses[] = $email;
                        }
                    }
                }
            }
            
            // Eğer hiç CC email yoksa, en azından mutabakat@ideadenetim.com.tr'yi ekle (güvenlik için)
            if (empty($ccAddresses)) {
                $ccAddresses = ['mutabakat@ideadenetim.com.tr'];
            }
            
            \Mail::send('emails.bank-reconciliation', $bodyViewData, function ($message) use ($bank, $customer, $subject, $pdfPath, $ccAddresses, $request) {
                // FROM adresini en başta, ayrı bir satır olarak set et
                $message->from('mutabakat@mg.ideadocs.com.tr', 'Mutabakat Yönetim Sistemi');
                
                $message->to($bank->officer_email)
                    ->cc($ccAddresses)
                    ->subject($subject)
                    ->attach($pdfPath, [
                        'as'   => 'Banka-Mutabakat-Mektubu.pdf',
                        'mime' => 'application/pdf',
                    ]);
                
                // Request'i fresh yükle (attachments dahil)
                $request->refresh();
                
                // Mutabakat isteğindeki ek dosyaları ekle
                if ($request->attachments && !empty($request->attachments)) {
                    Log::info('Ek dosyalar bulundu', [
                        'request_id' => $request->id,
                        'attachments' => $request->attachments,
                        'attachments_count' => is_array($request->attachments) ? count($request->attachments) : 1
                    ]);
                    
                    // Array kontrolü - Filament multiple FileUpload array döner
                    $attachments = is_array($request->attachments) ? $request->attachments : [$request->attachments];
                    
                    foreach ($attachments as $attachmentPath) {
                        if (empty($attachmentPath)) {
                            continue;
                        }
                        
                        // Path'i normalize et (backslash'ları düzelt, başlangıç slash'ını kontrol et)
                        $normalizedPath = str_replace('\\', '/', $attachmentPath);
                        
                        // Eğer path zaten 'reconciliation_request_attachments/' ile başlıyorsa direkt kullan
                        // Değilse ekle
                        if (!str_starts_with($normalizedPath, 'reconciliation_request_attachments/')) {
                            $normalizedPath = 'reconciliation_request_attachments/' . basename($normalizedPath);
                        }
                        
                        $fullPath = storage_path('app/' . $normalizedPath);
                        
                        Log::info('Dosya kontrolü yapılıyor', [
                            'attachment_path' => $attachmentPath,
                            'normalized_path' => $normalizedPath,
                            'full_path' => $fullPath,
                            'exists' => file_exists($fullPath)
                        ]);
                        
                        if (file_exists($fullPath)) {
                            $fileName = basename($normalizedPath);
                            $mimeType = mime_content_type($fullPath) ?: 'application/octet-stream';
                            
                            Log::info('Ek dosya eklendi', [
                                'file_path' => $fullPath,
                                'file_name' => $fileName,
                                'mime_type' => $mimeType
                            ]);
                            
                            $message->attach($fullPath, [
                                'as'   => $fileName,
                                'mime' => $mimeType,
                            ]);
                        } else {
                            Log::warning('Ek dosya bulunamadı', [
                                'attachment_path' => $attachmentPath,
                                'normalized_path' => $normalizedPath,
                                'full_path' => $fullPath,
                                'storage_app_path' => storage_path('app')
                            ]);
                        }
                    }
                } else {
                    Log::info('Ek dosya bulunamadı (attachments boş)', [
                        'request_id' => $request->id,
                        'attachments_value' => $request->attachments,
                        'attachments_type' => gettype($request->attachments)
                    ]);
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
                'pdf_path' => $pdfPath,
                'attachments_count' => $request->attachments && is_array($request->attachments) ? count($request->attachments) : 0
            ]);

        } catch (\Exception $e) {
            // Hata durumunda email log kaydı oluştur
            ReconciliationEmail::create([
                'request_id'  => $request->id,
                'bank_id'     => $bank->id,
                'sent_to'     => $bank->officer_email,
                'subject'     => $subject,
                'body'        => null,
                'status'      => 'failed',
                'sent_at'     => now(),
                'error_message' => $e->getMessage(),
            ]);

            Log::error('Banka mutabakat maili gönderilemedi', [
                'bank_id' => $bank->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Hatırlatma maili gönder (Pazar otomatik gönderimleri için).
     * Kaşe bekleyen ve cevap gelen bankalara GÖNDERİLMEZ.
     */
    public function sendReminderMail(ReconciliationBank $bank): void
    {
        $request  = $bank->request;
        $customer = $bank->customer;

        if (!$bank->officer_email) {
            throw new \Exception("Banka yetkilisinin e-postası yok.");
        }

        $bank->load(['request', 'customer']);
        $request  = $bank->request;
        $customer = $bank->customer;

        if (!$request || !$customer) {
            throw new \Exception("Talep veya müşteri bilgisi bulunamadı.");
        }

        $subject = "Hatırlatma: {$customer->name} - {$request->year} Yılı Banka Mutabakatı";

        $bodyViewData = [
            'bank'     => $bank,
            'customer' => $customer,
            'request'  => $request,
        ];

        try {
            $pdfPath = $this->mutabakatService->generatePdf($request, $customer, $bank);

            $ccAddresses = ['mutabakat@ideadenetim.com.tr'];
            if ($request->include_auto_cc ?? true) {
                if ($customer->email && !in_array($customer->email, $ccAddresses)) {
                    $ccAddresses[] = $customer->email;
                }
            }
            if ($request->cc_emails) {
                foreach (preg_split('/[,;\n\r]+/', $request->cc_emails) as $email) {
                    $email = trim($email);
                    if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL) && !in_array($email, $ccAddresses)) {
                        $ccAddresses[] = $email;
                    }
                }
            }

            Mail::send('emails.bank-reconciliation-reminder', $bodyViewData, function ($message) use ($bank, $request, $subject, $pdfPath, $ccAddresses) {
                $message->from('mutabakat@mg.ideadocs.com.tr', 'Mutabakat Yönetim Sistemi');
                $message->to($bank->officer_email)
                    ->cc($ccAddresses)
                    ->subject($subject)
                    ->attach($pdfPath, ['as' => 'Banka-Mutabakat-Mektubu.pdf', 'mime' => 'application/pdf']);

                // Talep ek dosyalarını da ekle (ilk maildeki gibi)
                $request->refresh();
                if ($request->attachments && !empty($request->attachments)) {
                    $attachments = is_array($request->attachments) ? $request->attachments : [$request->attachments];
                    foreach ($attachments as $attachmentPath) {
                        if (empty($attachmentPath)) continue;
                        $normalizedPath = str_replace('\\', '/', $attachmentPath);
                        if (!str_starts_with($normalizedPath, 'reconciliation_request_attachments/')) {
                            $normalizedPath = 'reconciliation_request_attachments/' . basename($attachmentPath);
                        }
                        $fullPath = storage_path('app/' . $normalizedPath);
                        if (file_exists($fullPath)) {
                            $message->attach($fullPath, [
                                'as'   => basename($normalizedPath),
                                'mime' => mime_content_type($fullPath) ?: 'application/octet-stream',
                            ]);
                        }
                    }
                }
            });

            ReconciliationEmail::create([
                'request_id'  => $request->id,
                'bank_id'     => $bank->id,
                'sent_to'     => $bank->officer_email,
                'subject'     => $subject,
                'body'        => view('emails.bank-reconciliation-reminder', $bodyViewData)->render(),
                'status'      => 'sent',
                'sent_at'     => now(),
            ]);

            Log::info('Banka mutabakat hatırlatma maili gönderildi', ['bank_id' => $bank->id]);
        } catch (\Exception $e) {
            ReconciliationEmail::create([
                'request_id'     => $request->id,
                'bank_id'        => $bank->id,
                'sent_to'        => $bank->officer_email,
                'subject'        => $subject,
                'body'           => null,
                'status'         => 'failed',
                'sent_at'        => now(),
                'error_message'  => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
