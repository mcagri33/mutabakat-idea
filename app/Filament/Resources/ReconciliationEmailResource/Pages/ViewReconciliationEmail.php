<?php

namespace App\Filament\Resources\ReconciliationEmailResource\Pages;

use App\Filament\Resources\ReconciliationEmailResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewReconciliationEmail extends ViewRecord
{
    protected static string $resource = ReconciliationEmailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
