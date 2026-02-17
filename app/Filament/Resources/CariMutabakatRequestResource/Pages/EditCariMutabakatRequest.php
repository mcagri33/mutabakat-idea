<?php

namespace App\Filament\Resources\CariMutabakatRequestResource\Pages;

use App\Filament\Resources\CariMutabakatRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCariMutabakatRequest extends EditRecord
{
    protected static string $resource = CariMutabakatRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
