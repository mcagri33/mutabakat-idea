<?php

namespace App\Console\Commands;

use App\Jobs\SendReminderMailJob;
use App\Mail\PazarHatirlatmaRaporuMailable;
use App\Models\ReconciliationBank;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendSundayReminders extends Command
{
    protected $signature = 'mutabakat:send-sunday-reminders
                            {--dry-run : Mailleri göndermeden sadece listeyi göster}
                            {--test : Mailleri bankaya değil adminlere gönder (test için)}
                            {--limit= : Maksimum gönderilecek mail sayısı (test için, örn: 2)}';

    protected $description = 'Her Pazar cevap gelmeyen bankalara otomatik hatırlatma maili gönderir (kaşe bekleyen ve cevap gelenler hariç), adminlere rapor iletir';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $testMode = $this->option('test');
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;

        if ($dryRun) {
            $this->info('=== DRY-RUN: Mail GÖNDERİLMEYECEK, sadece liste gösterilecek ===');
        }
        if ($testMode) {
            $this->warn('=== TEST MODU: Mailler adminlere gönderilecek (bankalara DEĞİL) ===');
        }

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

        if ($limit !== null && $limit > 0) {
            $banksToRemind = $banksToRemind->take($limit);
            $this->info("Limit uygulandı: en fazla {$limit} mail.");
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

        $admins = User::whereHas('roles', fn ($q) => $q->where('name', 'admin')->orWhere('name', 'super-admin'))->get();
        if ($admins->isEmpty()) {
            $admins = User::all();
        }
        $adminEmails = $admins->pluck('email')->filter()->unique()->values();

        if ($dryRun) {
            $this->table(
                ['Firma', 'Banka', 'Yıl', 'Normalde alıcı'],
                $banksToRemind->map(fn ($b) => [
                    $b->customer?->name ?? '-',
                    $b->bank_name,
                    $b->request?->year ?? '-',
                    $testMode ? 'Admin' : $b->officer_email,
                ])->toArray()
            );
            $this->info("Gönderilecek: " . $banksToRemind->count() . " banka.");
            if (!empty($excludedKase)) {
                $this->newLine();
                $this->warn('Kaşe bekleyen (gönderilmeyecek): ' . count($excludedKase));
                $this->table(['Firma', 'Banka'], $excludedKase);
            }
            if (!empty($excludedReceived)) {
                $this->newLine();
                $this->info('Cevap gelmiş (gönderilmeyecek): ' . count($excludedReceived));
            }
            return self::SUCCESS;
        }

        $overrideRecipients = $testMode ? $adminEmails->toArray() : null;
        $queuedCount = 0;

        foreach ($banksToRemind as $bank) {
            SendReminderMailJob::dispatch($bank, $overrideRecipients);
            $queuedCount++;
        }

        $sentItems = $banksToRemind->map(fn ($b) => [
            'firma' => $b->customer?->name ?? '-',
            'banka' => $b->bank_name,
            'yil'   => $b->request?->year ?? '-',
        ])->toArray();

        if ($adminEmails->isNotEmpty()) {
            try {
                $mailable = new PazarHatirlatmaRaporuMailable($sentItems, [], $excludedKase, $excludedReceived);
                Mail::to($adminEmails->toArray())->send($mailable);
                $this->info('Admin raporu ' . $adminEmails->count() . ' adrese gönderildi.');
            } catch (\Throwable $e) {
                Log::error('Pazar hatırlatma raporu gönderilemedi', ['error' => $e->getMessage()]);
            }
        }

        $this->info("Hatırlatma maili kuyruğa alındı: {$queuedCount} banka (arka planda gönderilecek).");

        return self::SUCCESS;
    }
}
