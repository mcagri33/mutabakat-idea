<?php

namespace App\Filament\Resources\CariMutabakatRequestResource\Pages;

use App\Filament\Resources\CariMutabakatRequestResource;
use App\Jobs\SendCariMutabakatMailJob;
use App\Models\CariMutabakatItem;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateCariMutabakatRequest extends CreateRecord
{
    protected static string $resource = CariMutabakatRequestResource::class;

    protected array $pendingItems = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->pendingItems = $data['items'] ?? [];
        unset($data['items']);
        return $data;
    }

    protected function afterCreate(): void
    {
        $mailsQueued = 0;

        foreach ($this->pendingItems as $item) {
            $email = trim($item['email'] ?? '');
            $hasValidEmail = !empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL);

            $cariItem = CariMutabakatItem::create([
                'request_id' => $this->record->id,
                'hesap_tipi' => $item['hesap_tipi'] ?? null,
                'referans' => $item['referans'] ?? null,
                'cari_kodu' => $item['cari_kodu'] ?? '',
                'unvan' => $item['unvan'] ?? '',
                'email' => $email,
                'cc_email' => $item['cc_email'] ?? null,
                'tel_no' => $item['tel_no'] ?? null,
                'vergi_no' => $item['vergi_no'] ?? null,
                'tarih' => $item['tarih'] ?? now(),
                'bakiye_tipi' => $item['bakiye_tipi'] ?? 'Borç',
                'bakiye' => $item['bakiye'] ?? 0,
                'pb' => $item['pb'] ?? 'TL',
                'karsiligi' => $item['karsiligi'] ?? null,
                'token' => $hasValidEmail ? CariMutabakatItem::generateToken() : null,
            ]);

            if ($hasValidEmail) {
                SendCariMutabakatMailJob::dispatch($cariItem);
                $mailsQueued++;
            }
        }

        if ($mailsQueued > 0) {
            Notification::make()
                ->title('Cari mutabakat talebi oluşturuldu')
                ->body("{$mailsQueued} adrese e-posta gönderimi kuyruğa alındı.")
                ->success()
                ->send();
        }
    }
}
