<?php

namespace App\Filament\Resources\ManualReconciliationEntryResource\Pages;

use App\Filament\Resources\ManualReconciliationEntryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditManualReconciliationEntry extends EditRecord
{
    protected static string $resource = ManualReconciliationEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
