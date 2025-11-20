<?php

namespace App\Services;

use App\Models\ReconciliationBank;
use App\Models\ReconciliationIncomingEmail;
use Illuminate\Support\Facades\Log;
use Webklex\IMAP\Facades\Client;

class ReconciliationIncomingMailService
{
    /**
     * IMAP/POP3'ten gelen mail'leri kontrol et
     */
    public function checkIncomingMails(): void
    {
        try {
            // IMAP bağlantısı
            $config = config('imap.accounts.default');
            if (!$config || !$config['username'] || !$config['password']) {
                Log::warning('IMAP ayarları yapılandırılmamış');
                return;
            }
            
            $client = Client::account('default');
            $client->connect();
            
            // Gelen kutusunu aç
            $folder = $client->getFolder('INBOX');
            
            // Okunmamış mail'leri al
            $messages = $folder->messages()->unseen()->get();
            
            foreach ($messages as $message) {
                $this->processIncomingMail($message);
            }
            
            $client->disconnect();
        } catch (\Exception $e) {
            Log::error('IMAP bağlantı hatası', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
    
    /**
     * Gelen mail'i işle ve banka ile eşleştir
     */
    protected function processIncomingMail($message): void
    {
        try {
            $from = $message->getFrom();
            $fromEmail = $from[0]->mail ?? null;
            $fromName = $from[0]->personal ?? null;
            $subject = $message->getSubject();
            $body = $message->getTextBody();
            $htmlBody = $message->getHTMLBody();
            $messageId = $message->getMessageId();
            $receivedAt = $message->getDate();
            
            if (!$fromEmail) {
                Log::warning('Gelen mail\'de gönderen e-posta bulunamadı', [
                    'message_id' => $messageId,
                ]);
                return;
            }
            
            // Banka ile eşleştir
            $bank = ReconciliationBank::where('officer_email', $fromEmail)
                ->where('reply_status', 'pending')
                ->first();
            
            if (!$bank) {
                // Eşleşme bulunamadı - logla
                ReconciliationIncomingEmail::create([
                    'request_id' => null,
                    'bank_id' => null,
                    'from_email' => $fromEmail,
                    'from_name' => $fromName,
                    'subject' => $subject,
                    'body' => $body,
                    'html_body' => $htmlBody,
                    'message_id' => $messageId,
                    'received_at' => $receivedAt,
                    'match_status' => 'unmatched',
                    'match_notes' => 'Banka eşleştirmesi bulunamadı',
                    'status' => 'new',
                ]);
                
                Log::info('Eşleşmeyen mail kaydedildi', [
                    'from_email' => $fromEmail,
                    'subject' => $subject,
                ]);
                
                return;
            }
            
            // Ekleri kaydet
            $attachments = [];
            foreach ($message->getAttachments() as $attachment) {
                $attachments[] = [
                    'name' => $attachment->getName(),
                    'size' => $attachment->getSize(),
                    'type' => $attachment->getMimeType(),
                ];
            }
            
            // Gelen mail'i kaydet
            $incomingEmail = ReconciliationIncomingEmail::create([
                'request_id' => $bank->request_id,
                'bank_id' => $bank->id,
                'from_email' => $fromEmail,
                'from_name' => $fromName,
                'subject' => $subject,
                'body' => $body,
                'html_body' => $htmlBody,
                'message_id' => $messageId,
                'received_at' => $receivedAt,
                'match_status' => 'matched',
                'status' => 'new',
                'attachments' => $attachments,
            ]);
            
            // Banka durumunu güncelle
            $bank->update([
                'reply_status' => 'received',
                'reply_received_at' => now(),
            ]);
            
            // Admin'e bildirim gönder
            $this->notifyAdmin($bank, $incomingEmail);
            
            // Mail'i okundu olarak işaretle
            $message->setFlag('Seen');
            
            Log::info('Banka cevabı işlendi', [
                'bank_id' => $bank->id,
                'bank_name' => $bank->bank_name,
                'email_id' => $incomingEmail->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Mail işleme hatası', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
    
    /**
     * Admin'e bildirim gönder
     */
    protected function notifyAdmin($bank, $incomingEmail): void
    {
        try {
            $request = $bank->request;
            $customer = $bank->customer;
            
            // Tüm admin kullanıcılarına bildirim gönder
            // FilamentAccessManagement kullanıyorsak roles relationship'i var
            $admins = \App\Models\User::whereHas('roles', function($query) {
                $query->where('name', 'admin')
                      ->orWhere('name', 'super-admin');
            })->get();
            
            if ($admins->isEmpty()) {
                // Admin yoksa, tüm kullanıcılara gönder
                $admins = \App\Models\User::all();
            }
            
            $attachmentCount = count($incomingEmail->attachments ?? []);
            $attachmentText = $attachmentCount > 0 
                ? " ({$attachmentCount} ek)" 
                : "";
            
            foreach ($admins as $admin) {
                // Filament notification - veritabanına kaydet
                \Filament\Notifications\Notification::make()
                    ->title('Banka Cevabı Geldi')
                    ->body(
                        "{$customer->name} firması için " .
                        "{$bank->bank_name} bankasından " .
                        "{$request->year} yılı mutabakat cevabı geldi{$attachmentText}."
                    )
                    ->success()
                    ->icon('heroicon-o-envelope-open')
                    ->actions([
                        \Filament\Notifications\Actions\Action::make('view')
                            ->label('Görüntüle')
                            ->url(\App\Filament\Resources\ReconciliationRequestResource::getUrl('view', ['record' => $request->id]))
                            ->button(),
                    ])
                    ->sendToDatabase($admin);
            }
            
            Log::info('Admin bildirimi gönderildi', [
                'admin_count' => $admins->count(),
                'bank_id' => $bank->id,
                'request_id' => $request->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Bildirim gönderme hatası', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}

