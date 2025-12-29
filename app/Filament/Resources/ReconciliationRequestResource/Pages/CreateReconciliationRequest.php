<?php

namespace App\Filament\Resources\ReconciliationRequestResource\Pages;

use App\Filament\Resources\ReconciliationRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Models\CustomerBank;
use App\Models\ReconciliationBank;

class CreateReconciliationRequest extends CreateRecord
{
    protected static string $resource = ReconciliationRequestResource::class;

 protected function afterCreate(): void
{
    $request = $this->record;

    // 1) Firmanın banka listesi
    $customerBanks = \App\Models\CustomerBank::where('customer_id', $request->customer_id)
        ->where('is_active', true)
        ->get();

    // 2) Her banka için ReconciliationBank kaydı oluştur + Mail gönder
    $mailService = app(\App\Services\ReconciliationMailService::class);

    foreach ($customerBanks as $bank) {

        // Banka satırını oluştur
        $recBank = \App\Models\ReconciliationBank::create([
            'request_id'        => $request->id,
            'customer_id'       => $request->customer_id,
            'customer_bank_id'  => $bank->id,
            'bank_name'         => $bank->bank_name,
            'branch_name'       => $bank->branch_name,
            'officer_name'      => $bank->officer_name,
            'officer_email'     => $bank->officer_email,
            'officer_phone'     => $bank->officer_phone,
            'mail_status'       => 'pending',
            'reply_status'      => 'pending',
        ]);

        // Eğer bankanın mail adresi yoksa atla
        if (!$bank->officer_email) {
            continue;
        }

        // 3) Mail gönder
        try {
            $mailService->sendBankMail($recBank);

            // Başarılı → Güncelle
            $recBank->update([
                'mail_status'  => 'sent',
                'mail_sent_at' => now(),
            ]);

        } catch (\Throwable $e) {

            // Hatalı → Logla ama sistemi durdurma
            $recBank->update([
                'mail_status' => 'failed',
            ]);

            \Log::error("Mutabakat mail gönderilemedi", [
                'bank_id' => $recBank->id,
                'error'   => $e->getMessage(),
            ]);
        }
    }

    // Request status'ünü güncelle
    // Eğer en az bir mail gönderildiyse status'ü 'mail_sent' yap
    $sentBanksCount = $request->banks()->where('mail_status', 'sent')->count();
    if ($sentBanksCount > 0) {
        $request->update([
            'status' => 'mail_sent',
            'sent_at' => now(),
        ]);
    }
}

}
