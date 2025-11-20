<?php

namespace App\Filament\Widgets\MutabakatDashboard;

use App\Models\ReconciliationRequest;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class PendingRequestsTableWidget extends BaseWidget
{
    protected static ?string $heading = 'Tamamlanmamış Mutabakat Talepleri';

    protected static ?int $sort = 4;

    protected static ?int $pollingInterval = null;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ReconciliationRequest::query()
                    ->whereHas('banks', function ($query) {
                        $query->where('reply_status', '!=', 'completed');
                    })
                    ->withCount([
                        'banks as total_banks',
                        'banks as received_banks' => function ($query) {
                            $query->whereIn('reply_status', ['received', 'completed']);
                        },
                        'banks as pending_banks' => function ($query) {
                            $query->where('reply_status', 'pending');
                        },
                    ])
                    ->with('customer')
            )
            ->columns([
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Firma')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('year')
                    ->label('Yıl')
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('total_banks')
                    ->label('Toplam Banka')
                    ->counts('banks')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('received_banks')
                    ->label('Gelen')
                    ->alignCenter()
                    ->color('success'),

                Tables\Columns\TextColumn::make('pending_banks')
                    ->label('Bekleyen')
                    ->alignCenter()
                    ->color('warning'),

                Tables\Columns\TextColumn::make('completion_rate')
                    ->label('Tamamlanma Oranı')
                    ->getStateUsing(function (ReconciliationRequest $record): string {
                        if ($record->total_banks == 0) {
                            return '0%';
                        }
                        $rate = round(($record->received_banks / $record->total_banks) * 100, 1);
                        return $rate . '%';
                    })
                    ->alignCenter()
                    ->color(fn ($state) => match (true) {
                        (float) str_replace('%', '', $state) == 100 => 'success',
                        (float) str_replace('%', '', $state) >= 50 => 'warning',
                        default => 'danger',
                    }),
            ])
            ->defaultSort('year', 'desc')
            ->paginated([10, 25, 50])
            ->poll(null);
    }
}

