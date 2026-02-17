<?php

namespace App\Filament\Resources\CariMutabakatRequestResource\Pages;

use App\Exports\CariMutabakatExport;
use App\Filament\Resources\CariMutabakatRequestResource;
use App\Models\CariMutabakatItem;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListCariMutabakatRequests extends ListRecords
{
    protected static string $resource = CariMutabakatRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('detayliIslemler')
                ->label('Detaylı İşlemler')
                ->icon('heroicon-o-list-bullet')
                ->url(route('filament.mutabakat.resources.cari-mutabakat-items.index'))
                ->color('gray'),
            Actions\Action::make('exportExcel')
                ->label('Excel\'e Aktar')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function (): StreamedResponse {
                    $requestIds = $this->getFilteredTableQuery()->pluck('id');
                    $items = CariMutabakatItem::query()
                        ->whereIn('request_id', $requestIds)
                        ->with(['request.customer', 'reply'])
                        ->orderBy('request_id')
                        ->orderBy('id')
                        ->get();
                    return (new CariMutabakatExport($items))->export();
                }),
            Actions\CreateAction::make()
                ->label('Yeni Cari Mutabakat Talebi'),
        ];
    }
}
