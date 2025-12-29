<?php

namespace App\Filament\Resources\ReconciliationRequestResource\Pages;

use App\Filament\Resources\ReconciliationRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListReconciliationRequests extends ListRecords
{
    protected static string $resource = ReconciliationRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
            ->label('Yeni Mutabakat Talebi'),
        
        ];
    }
}
