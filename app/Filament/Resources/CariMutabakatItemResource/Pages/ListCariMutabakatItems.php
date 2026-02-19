<?php

namespace App\Filament\Resources\CariMutabakatItemResource\Pages;

use App\Exports\CariMutabakatExport;
use App\Filament\Resources\CariMutabakatItemResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListCariMutabakatItems extends ListRecords
{
    protected static string $resource = CariMutabakatItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('exportExcel')
                ->label('Excel\'e Aktar')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function (): StreamedResponse {
                    $items = $this->getTableQueryForExport()
                        ->with(['request.customer', 'reply'])
                        ->orderBy('request_id')
                        ->orderBy('id')
                        ->get();
                    return (new CariMutabakatExport($items))->export();
                }),
        ];
    }
}
