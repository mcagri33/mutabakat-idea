<?php

namespace App\Filament\Resources\ReconciliationRequestResource\Pages;

use App\Filament\Resources\ReconciliationRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditReconciliationRequest extends EditRecord
{
    protected static string $resource = ReconciliationRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
