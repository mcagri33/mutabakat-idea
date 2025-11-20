<?php

namespace App\Filament\Resources\ReconciliationEmailResource\Pages;

use App\Filament\Resources\ReconciliationEmailResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListReconciliationEmails extends ListRecords
{
    protected static string $resource = ReconciliationEmailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
