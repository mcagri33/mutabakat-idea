<?php

namespace App\Filament\Resources\ManualReconciliationEntryResource\Pages;

use App\Filament\Resources\ManualReconciliationEntryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListManualReconciliationEntries extends ListRecords
{
    protected static string $resource = ManualReconciliationEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Yeni manuel giriş'),
        ];
    }
}
