<?php

namespace App\Filament\Resources\CustomerBankResource\Pages;

use App\Filament\Resources\CustomerBankResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCustomerBank extends EditRecord
{
    protected static string $resource = CustomerBankResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
