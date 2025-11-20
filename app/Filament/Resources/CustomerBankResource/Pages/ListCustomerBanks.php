<?php

namespace App\Filament\Resources\CustomerBankResource\Pages;

use App\Filament\Resources\CustomerBankResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCustomerBanks extends ListRecords
{
    protected static string $resource = CustomerBankResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
