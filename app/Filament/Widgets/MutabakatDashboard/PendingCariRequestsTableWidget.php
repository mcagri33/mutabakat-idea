<?php

namespace App\Filament\Widgets\MutabakatDashboard;

use App\Models\CariMutabakatRequest;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class PendingCariRequestsTableWidget extends BaseWidget
{
    protected static ?string $heading = 'Cari Mutabakat Talepleri';

    protected static ?int $sort = 5;

    protected static ?int $pollingInterval = null;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                CariMutabakatRequest::query()
                    ->withCount([
                        'items as total_items',
                        'items as pending_items' => fn ($q) => $q->where('reply_status', 'pending'),
                        'items as received_items' => fn ($q) => $q->whereIn('reply_status', ['received', 'completed']),
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

                Tables\Columns\TextColumn::make('month')
                    ->label('Ay')
                    ->formatStateUsing(fn ($state) => $state ? [
                        1 => 'Ocak', 2 => 'Şubat', 3 => 'Mart', 4 => 'Nisan',
                        5 => 'Mayıs', 6 => 'Haziran', 7 => 'Temmuz', 8 => 'Ağustos',
                        9 => 'Eylül', 10 => 'Ekim', 11 => 'Kasım', 12 => 'Aralık',
                    ][(int) $state] ?? $state : '-')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('total_items')
                    ->label('Toplam Satır')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('received_items')
                    ->label('Cevap Gelen')
                    ->alignCenter()
                    ->color('success'),

                Tables\Columns\TextColumn::make('pending_items')
                    ->label('Bekleyen')
                    ->alignCenter()
                    ->color('warning'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Durum')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'pending' => 'Beklemede',
                        'sent' => 'Gönderildi',
                        'partially_received' => 'Kısmi Dönüş',
                        'completed' => 'Tamamlandı',
                        'failed' => 'Hata',
                        default => $state ?? '-',
                    })
                    ->color(fn ($state) => match ($state) {
                        'pending' => 'gray',
                        'sent' => 'primary',
                        'partially_received' => 'warning',
                        'completed' => 'success',
                        'failed' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([5, 10, 25])
            ->headerActions([
                Tables\Actions\Action::make('viewAll')
                    ->label('Tümünü Görüntüle')
                    ->url(route('filament.mutabakat.resources.cari-mutabakat-requests.index'))
                    ->icon('heroicon-m-arrow-right'),
            ])
            ->recordUrl(fn (CariMutabakatRequest $record) => route('filament.mutabakat.resources.cari-mutabakat-requests.view', ['record' => $record]));
    }
}
