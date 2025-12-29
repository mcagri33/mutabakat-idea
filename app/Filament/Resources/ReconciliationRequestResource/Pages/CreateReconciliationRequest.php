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

    // 2) Her banka için ReconciliationBank kaydı oluştur + Mail gönder (Queue ile)
    $banksWithEmail = 0;

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

        // 3) Mail gönderimi için Queue'ya ekle (asenkron)
        \App\Jobs\SendReconciliationMailJob::dispatch($recBank);
        $banksWithEmail++;
    }

    // Kullanıcıya bildirim göster
    if ($banksWithEmail > 0) {
        \Filament\Notifications\Notification::make()
            ->title('Mutabakat talebi oluşturuldu')
            ->body("{$banksWithEmail} bankaya email gönderimi başlatıldı. Email'ler arka planda gönderilecek.")
            ->success()
            ->send();
    } else {
        \Filament\Notifications\Notification::make()
            ->title('Mutabakat talebi oluşturuldu')
            ->body('Banka kayıtları oluşturuldu ancak email adresi olan banka bulunamadı.')
            ->warning()
            ->send();
    }
}

}
