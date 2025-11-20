<?php

namespace App\Filament\Resources\ReconciliationRequestResource\Pages;

use App\Filament\Resources\ReconciliationRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewReconciliationRequest extends ViewRecord
{
    protected static string $resource = ReconciliationRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return []; // İstatistik widget'ları kaldırıldı
    }
}
