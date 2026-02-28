<?php

namespace App\Console\Commands;

use App\Mail\PazarHatirlatmaRaporuMailable;
use App\Models\ReconciliationBank;
use App\Models\User;
use App\Services\ReconciliationMailService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendSundayReminders extends Command
{
    protected $signature = 'mutabakat:send-sunday-reminders';

    protected $description = 'Her Pazar cevap gelmeyen bankalara otomatik hatırlatma maili gönderir (kaşe bekleyen ve cevap gelenler hariç), adminlere rapor iletir';

    public function handle(ReconciliationMailService $mailService): int
    {
        $this->info('Pazar hatırlatma mailleri hazırlanıyor...');

        $currentYear = now()->year;

        // Hatırlatma gönderilecek: cevap bekleyen, mail gitti, kaşe TALEP EDİLMEDİ
        $banksToRemind = ReconciliationBank::query()
            ->with(['customer', 'request'])
            ->where('mail_status', 'sent')
            ->where('reply_status', 'pending')
            ->where(function ($q) {
                $q->where('kase_talep_edildi', false)->orWhereNull('kase_talep_edildi');
            })
            ->whereNotNull('officer_email')
            ->where('officer_email', '!=', '')
            ->whereHas('request', fn ($q) => $q->whereIn('year', [$currentYear, $currentYear - 1]))
            ->get();

        $sentCount = 0;
        $sentItems = [];
        $failedItems = [];

        foreach ($banksToRemind as $bank) {
            try {
                $mailService->sendReminderMail($bank);
                $sentCount++;
                $sentItems[] = [
                    'firma'  => $bank->customer?->name ?? '-',
                    'banka'  => $bank->bank_name,
                    'yil'    => $bank->request?->year ?? '-',
                ];
            } catch (\Throwable $e) {
                Log::error('Pazar hatırlatma maili gönderilemedi', [
                    'bank_id' => $bank->id,
                    'error'   => $e->getMessage(),
                ]);
                $failedItems[] = [
                    'firma'  => $bank->customer?->name ?? '-',
                    'banka'  => $bank->bank_name,
                    'hata'   => $e->getMessage(),
                ];
            }
        }

        // Hariç tutulanlar: kaşe bekleyen, cevap gelen
        $excludedKase = ReconciliationBank::query()
            ->with(['customer', 'request'])
            ->where('mail_status', 'sent')
            ->where('reply_status', 'pending')
            ->where('kase_talep_edildi', true)
            ->whereHas('request', fn ($q) => $q->whereIn('year', [$currentYear, $currentYear - 1]))
            ->get()
            ->map(fn ($b) => ['firma' => $b->customer?->name ?? '-', 'banka' => $b->bank_name])->toArray();

        $excludedReceived = ReconciliationBank::query()
            ->with(['customer', 'request'])
            ->where('mail_status', 'sent')
            ->whereIn('reply_status', ['received', 'completed'])
            ->whereHas('request', fn ($q) => $q->whereIn('year', [$currentYear, $currentYear - 1]))
            ->get()
            ->map(fn ($b) => ['firma' => $b->customer?->name ?? '-', 'banka' => $b->bank_name])->toArray();

        // Adminlere rapor gönder
        $admins = User::whereHas('roles', fn ($q) => $q->where('name', 'admin')->orWhere('name', 'super-admin'))->get();
        if ($admins->isEmpty()) {
            $admins = User::all();
        }
        $adminEmails = $admins->pluck('email')->filter()->unique()->values();

        if ($adminEmails->isNotEmpty()) {
            try {
                $mailable = new PazarHatirlatmaRaporuMailable($sentItems, $failedItems, $excludedKase, $excludedReceived);
                Mail::to($adminEmails->toArray())->send($mailable);
                $this->info('Admin raporu ' . $adminEmails->count() . ' adrese gönderildi.');
            } catch (\Throwable $e) {
                Log::error('Pazar hatırlatma raporu gönderilemedi', ['error' => $e->getMessage()]);
            }
        }

        $this->info("Hatırlatma maili gönderilen: {$sentCount} banka.");
        if (!empty($failedItems)) {
            $this->warn('Başarısız: ' . count($failedItems) . ' banka.');
        }

        return self::SUCCESS;
    }
}
