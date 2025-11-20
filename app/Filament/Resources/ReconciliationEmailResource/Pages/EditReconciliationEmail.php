<?php

namespace App\Filament\Resources\ReconciliationEmailResource\Pages;

use App\Filament\Resources\ReconciliationEmailResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditReconciliationEmail extends EditRecord
{
    protected static string $resource = ReconciliationEmailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
