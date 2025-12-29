<?php

namespace App\Filament\Resources\ReconciliationEmailResource\Pages;

use App\Filament\Resources\ReconciliationEmailResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListReconciliationEmails extends ListRecords
{
    protected static string $resource = ReconciliationEmailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Email logları manuel oluşturulmaz
        ];
    }

    protected function getTableQuery(): Builder
    {
        return parent::getTableQuery()
            ->with(['request.customer', 'bank']);
    }
}
