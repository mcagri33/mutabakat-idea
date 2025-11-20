<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Services\CustomerSyncService;
use Filament\Notifications\Notification;

class ListCustomers extends ListRecords
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
             Actions\Action::make('sync')
                ->label('Firmaları Senkronize Et')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->requiresConfirmation()
                ->action(function () {
                    $ok = app(CustomerSyncService::class)->sync();

                    if ($ok) {
                        Notification::make()
                            ->title('Senkron tamamlandı')
                            ->body('Firmalar ana sistemden başarıyla güncellendi.')
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Senkron hatası')
                            ->body('CustomerSyncService API ile iletişim kuramadı. Logları kontrol edin.')
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
