<?php

namespace App\Console\Commands;

use App\Jobs\SendCariReminderMailJob;
use App\Mail\CariHatirlatmaRaporuMailable;
use App\Models\CariMutabakatItem;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendCariReminders extends Command
{
    protected $signature = 'mutabakat:send-cari-reminders
                            {--dry-run : Mailleri göndermeden sadece listeyi göster}
                            {--test : Mailleri alıcıya değil adminlere gönder (test için)}
                            {--limit= : Maksimum gönderilecek mail sayısı (test için, örn: 2)}';

    protected $description = 'Haftalık cari mutabakat hatırlatması - cevap gelmeyen alıcı/satıcılara otomatik mail gönderir (kuyrukta)';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $testMode = $this->option('test');
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;

        if ($dryRun) {
            $this->info('=== DRY-RUN: Mail GÖNDERİLMEYECEK, sadece liste gösterilecek ===');
        }
        if ($testMode) {
            $this->warn('=== TEST MODU: Mailler adminlere gönderilecek (alıcı/satıcıya DEĞİL) ===');
        }

        $this->info('Cari hatırlatma mailleri hazırlanıyor...');

        $currentYear = now()->year;

        // Hatırlatma gönderilecek: mail gitti, cevap bekleniyor
        $itemsToRemind = CariMutabakatItem::query()
            ->with(['request.customer'])
            ->where('mail_status', 'sent')
            ->where('reply_status', 'pending')
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->whereNotNull('token')
            ->whereHas('request', fn ($q) => $q->whereIn('year', [$currentYear, $currentYear - 1]))
            ->get();

        $itemsToRemind = $itemsToRemind->filter(function ($item) {
            return filter_var($item->email, FILTER_VALIDATE_EMAIL);
        })->values();

        if ($limit !== null && $limit > 0) {
            $itemsToRemind = $itemsToRemind->take($limit);
            $this->info("Limit uygulandı: en fazla {$limit} mail.");
        }

        $admins = User::whereHas('roles', fn ($q) => $q->where('name', 'admin')->orWhere('name', 'super-admin'))->get();
        if ($admins->isEmpty()) {
            $admins = User::all();
        }
        $adminEmails = $admins->pluck('email')->filter()->unique()->values();

        if ($dryRun) {
            $this->table(
                ['Firma', 'Ünvan', 'Yıl', 'Normalde alıcı'],
                $itemsToRemind->map(fn ($i) => [
                    $i->request?->customer?->name ?? '-',
                    $i->unvan ?? '-',
                    $i->request?->year ?? '-',
                    $testMode ? 'Admin' : $i->email,
                ])->toArray()
            );
            $this->info("Gönderilecek: " . $itemsToRemind->count() . " cari.");
            return self::SUCCESS;
        }

        $overrideRecipients = $testMode ? $adminEmails->toArray() : null;

        $jobs = $itemsToRemind->map(
            fn ($item) => new SendCariReminderMailJob($item, $overrideRecipients)
        )->all();

        $sentItems = $itemsToRemind->map(fn ($i) => [
            'firma' => $i->request?->customer?->name ?? '-',
            'unvan' => $i->unvan ?? '-',
            'yil'   => $i->request?->year ?? '-',
        ])->toArray();

        $adminEmailsArr = $adminEmails->toArray();

        Bus::batch($jobs)
            ->then(function () use ($sentItems, $adminEmailsArr) {
                if (!empty($adminEmailsArr)) {
                    try {
                        Mail::to($adminEmailsArr)->send(new CariHatirlatmaRaporuMailable($sentItems));
                        Log::info('Cari hatırlatma raporu adminlere gönderildi', ['count' => count($adminEmailsArr)]);
                    } catch (\Throwable $e) {
                        Log::error('Cari hatırlatma raporu gönderilemedi', ['error' => $e->getMessage()]);
                    }
                }
            })
            ->dispatch();

        $this->info("Hatırlatma maili kuyruğa alındı: " . count($jobs) . " cari. Admin raporu tüm mailler gönderildikten sonra iletilecek.");

        return self::SUCCESS;
    }
}
