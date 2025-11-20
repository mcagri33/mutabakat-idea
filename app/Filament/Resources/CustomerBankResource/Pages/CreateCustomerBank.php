<?php

namespace App\Filament\Resources\CustomerBankResource\Pages;

use App\Filament\Resources\CustomerBankResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomerBank extends CreateRecord
{
    protected static string $resource = CustomerBankResource::class;
}
