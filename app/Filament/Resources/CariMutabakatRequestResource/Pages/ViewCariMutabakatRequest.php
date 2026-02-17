<?php

namespace App\Filament\Resources\CariMutabakatRequestResource\Pages;

use App\Filament\Resources\CariMutabakatRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCariMutabakatRequest extends ViewRecord
{
    protected static string $resource = CariMutabakatRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
