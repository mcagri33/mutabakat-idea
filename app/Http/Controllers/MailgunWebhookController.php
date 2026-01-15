<?php

namespace App\Http\Controllers;

use App\Services\ReconciliationIncomingMailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class MailgunWebhookController extends Controller
{
    protected $mailService;

    public function __construct(ReconciliationIncomingMailService $mailService)
    {
        $this->mailService = $mailService;
    }

    /**
     * Mailgun'den gelen mail webhook'u
     * POST /api/webhook/mailgun/incoming
     */
    public function handleIncomingMail(Request $request)
    {
        try {
            // Mailgun signature doğrulama
            if (!$this->verifySignature($request)) {
                Log::warning('Mailgun webhook signature doğrulaması başarısız', [
                    'ip' => $request->ip(),
                ]);
                return response('Unauthorized', 401);
            }

            // Mailgun'den gelen veriler
            $signature = $request->input('signature');
            $timestamp = $request->input('timestamp');
            $token = $request->input('token');
            
            // Mail içeriği - Mailgun'den mail'i çekmek için message-url kullanılır
            $messageUrl = $request->input('message-url');
            
            if (!$messageUrl) {
                // Eğer message-url yoksa, body-mime'ı kullan
                $bodyMime = $request->input('body-mime');
                if ($bodyMime) {
                    $this->processMimeMessage($bodyMime);
                    return response('OK', 200);
                }
                
                Log::warning('Mailgun webhook: message-url ve body-mime bulunamadı', [
                    'all_inputs' => $request->all(),
                ]);
                return response('Bad Request', 400);
            }

            // Mailgun API'den mail'i çek
            $mailData = $this->fetchMessageFromMailgun($messageUrl);
            
            if (!$mailData) {
                Log::error('Mailgun API\'den mail çekilemedi', [
                    'message_url' => $messageUrl,
                ]);
                return response('Internal Server Error', 500);
            }

            // Mail'i işle
            $this->processMailgunMessage($mailData);

            return response('OK', 200);
        } catch (\Exception $e) {
            Log::error('Mailgun webhook işleme hatası', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response('Internal Server Error', 500);
        }
    }

    /**
     * Mailgun signature doğrulama
     */
    protected function verifySignature(Request $request): bool
    {
        // HTTP Webhook Signing Key kullan (API Key değil!)
        $webhookSigningKey = config('services.mailgun.webhook_signing_key');
        if (!$webhookSigningKey) {
            Log::warning('Mailgun HTTP Webhook Signing Key yapılandırılmamış');
            return false;
        }

        $signature = $request->input('signature');
        $timestamp = $request->input('timestamp');
        $token = $request->input('token');

        if (!$signature || !$timestamp || !$token) {
            return false;
        }

        // Mailgun signature doğrulama algoritması
        // HTTP Webhook Signing Key ile doğrula
        $hmac = hash_hmac('sha256', $timestamp . $token, $webhookSigningKey);
        
        return hash_equals($signature, $hmac) && 
               abs(time() - $timestamp) < 15; // 15 saniye içinde olmalı
    }

    /**
     * Mailgun API'den mail'i çek
     */
    protected function fetchMessageFromMailgun(string $messageUrl): ?array
    {
        try {
            $apiKey = config('services.mailgun.secret');
            if (!$apiKey) {
                Log::error('Mailgun API key yapılandırılmamış');
                return null;
            }

            // Mailgun API endpoint'i
            // messageUrl format: https://storage.mailgun.net/v3/domains/mg.ideadocs.com.tr/messages/...
            // API endpoint: https://api.mailgun.net/v3/domains/mg.ideadocs.com.tr/messages/...
            
            // Storage URL'den API URL'ye çevir
            $apiUrl = str_replace('storage.mailgun.net/v3', 'api.mailgun.net/v3', $messageUrl);
            
            $response = Http::withBasicAuth('api', $apiKey)
                ->get($apiUrl);

            if (!$response->successful()) {
                Log::error('Mailgun API çağrısı başarısız', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'url' => $apiUrl,
                ]);
                return null;
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Mailgun API çağrısı hatası', [
                'error' => $e->getMessage(),
                'message_url' => $messageUrl,
            ]);
            return null;
        }
    }

    /**
     * Mailgun mesajını işle
     */
    protected function processMailgunMessage(array $mailData): void
    {
        try {
            // Mailgun'den gelen veri formatı
            $from = $mailData['from'] ?? '';
            $subject = $mailData['subject'] ?? '';
            $bodyPlain = $mailData['body-plain'] ?? '';
            $bodyHtml = $mailData['body-html'] ?? '';
            $messageId = $mailData['Message-Id'] ?? ($mailData['message-id'] ?? null);
            $timestamp = $mailData['timestamp'] ?? time();
            
            // From email'i parse et
            preg_match('/<(.+)>/', $from, $matches);
            $fromEmail = $matches[1] ?? $from;
            $fromName = preg_replace('/<.+>/', '', $from);
            $fromName = trim($fromName, ' "');

            // Ekleri al
            $attachments = [];
            if (isset($mailData['attachment-count']) && $mailData['attachment-count'] > 0) {
                // Mailgun'den attachment bilgilerini al
                for ($i = 1; $i <= $mailData['attachment-count']; $i++) {
                    $attachments[] = [
                        'name' => $mailData["attachment-{$i}"] ?? "attachment-{$i}",
                        'size' => $mailData["attachment-{$i}-size"] ?? 0,
                        'type' => $mailData["attachment-{$i}-content-type"] ?? 'application/octet-stream',
                    ];
                }
            }

            // ReconciliationIncomingMailService'e uygun formatta mesaj oluştur
            $message = new class($fromEmail, $fromName, $subject, $bodyPlain, $bodyHtml, $messageId, $timestamp, $attachments) {
                public $fromEmail;
                public $fromName;
                public $subject;
                public $bodyPlain;
                public $bodyHtml;
                public $messageId;
                public $timestamp;
                public $attachments;

                public function __construct($fromEmail, $fromName, $subject, $bodyPlain, $bodyHtml, $messageId, $timestamp, $attachments)
                {
                    $this->fromEmail = $fromEmail;
                    $this->fromName = $fromName;
                    $this->subject = $subject;
                    $this->bodyPlain = $bodyPlain;
                    $this->bodyHtml = $bodyHtml;
                    $this->messageId = $messageId;
                    $this->timestamp = $timestamp;
                    $this->attachments = $attachments;
                }

                public function getFrom()
                {
                    return [
                        (object)[
                            'mail' => $this->fromEmail,
                            'personal' => $this->fromName,
                        ]
                    ];
                }

                public function getSubject()
                {
                    return $this->subject;
                }

                public function getTextBody()
                {
                    return $this->bodyPlain;
                }

                public function getHTMLBody()
                {
                    return $this->bodyHtml;
                }

                public function getMessageId()
                {
                    return $this->messageId;
                }

                public function getDate()
                {
                    return date('Y-m-d H:i:s', $this->timestamp);
                }

                public function getAttachments()
                {
                    return $this->attachments;
                }

                public function setFlag($flag)
                {
                    // Mailgun için gerekli değil
                }
            };

            // Mevcut service'i kullanarak işle
            $reflection = new \ReflectionClass($this->mailService);
            $method = $reflection->getMethod('processIncomingMail');
            $method->setAccessible(true);
            $method->invoke($this->mailService, $message);

            Log::info('Mailgun webhook\'tan mail işlendi', [
                'from' => $fromEmail,
                'subject' => $subject,
            ]);
        } catch (\Exception $e) {
            Log::error('Mailgun mesaj işleme hatası', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * MIME mesajını işle (body-mime varsa)
     */
    protected function processMimeMessage(string $bodyMime): void
    {
        // MIME mesajını parse etmek için bir kütüphane gerekebilir
        // Şimdilik basit bir yaklaşım
        Log::info('MIME mesajı alındı (henüz işlenmedi)', [
            'size' => strlen($bodyMime),
        ]);
        // TODO: MIME parser ekle
    }
}
