<?php

namespace App\Filament\Resources\CariMutabakatRequestResource\Pages;

use App\Filament\Resources\CariMutabakatRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCariMutabakatRequests extends ListRecords
{
    protected static string $resource = CariMutabakatRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Yeni Cari Mutabakat Talebi'),
        ];
    }
}
