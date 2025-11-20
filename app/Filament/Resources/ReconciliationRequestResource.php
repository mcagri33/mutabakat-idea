<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReconciliationRequestResource\Pages;
use App\Models\ReconciliationRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ReconciliationRequestResource extends Resource
{
    protected static ?string $model = ReconciliationRequest::class;

    protected static ?string $navigationGroup = 'Mutabakat Yönetimi';
    protected static ?string $navigationLabel = 'Mutabakat Talepleri';
    protected static ?string $pluralLabel = 'Mutabakat Talepleri';
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['customer', 'banks']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('customer_id')
                    ->label('Firma')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->required()
                    ->reactive()
                    ->helperText('Mutabakat talebi oluşturulacak firmayı seçin.')
                    ->placeholder('Firma ara...'),

                Forms\Components\Select::make('type')
                    ->label('Mutabakat Tipi')
                    ->options([
                        'banka' => 'Banka Mutabakatı',
                        'cari' => 'Cari Mutabakat',
                    ])
                    ->default('banka')
                    ->required()
                    ->helperText('Mutabakat tipini seçin. Banka mutabakatı için banka bilgileri kullanılacaktır.'),

                Forms\Components\TextInput::make('year')
                    ->label('Yıl')
                    ->numeric()
                    ->required()
                    ->minValue(2020)
                    ->maxValue(now()->year + 1)
                    ->default(now()->year)
                    ->helperText('Mutabakat yılı seçin. Minimum 2020, maksimum ' . (now()->year + 1) . ' olabilir.'),

                Forms\Components\Select::make('status')
                    ->label('Durum')
                    ->options([
                        'pending' => 'Beklemede',
                        'mail_sent' => 'Mail Gönderildi',
                        'partially' => 'Kısmi Dönüş',
                        'received' => 'Tam Dönüş',
                        'completed' => 'Tamamlandı',
                        'failed' => 'Hata',
                    ])
                    ->default('pending')
                    ->disabled()
                    ->helperText('Durum otomatik olarak güncellenir.'),

                Forms\Components\Textarea::make('notes')
                    ->label('Notlar')
                    ->rows(3)
                    ->maxLength(1000)
                    ->helperText('Mutabakat talebi ile ilgili ek notlar (maksimum 1000 karakter)')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Firma')
                    ->sortable()
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('year')
                    ->label('Yıl')
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Tür')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'banka' => 'info',
                        'cari' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => $state === 'banka' ? 'Banka' : 'Cari'),

                Tables\Columns\TextColumn::make('banks_count')
                    ->label('Banka Sayısı')
                    ->counts('banks')
                    ->sortable()
                    ->alignCenter()
                    ->icon('heroicon-o-building-library'),

                Tables\Columns\TextColumn::make('documents_count')
                    ->label('Belge')
                    ->counts('documents')
                    ->sortable()
                    ->alignCenter()
                    ->icon('heroicon-o-document'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Durum')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'secondary',
                        'mail_sent' => 'primary',
                        'partially' => 'warning',
                        'received' => 'info',
                        'completed' => 'success',
                        'failed' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(function ($state) {
                        return match ($state) {
                            'pending'    => 'Beklemede',
                            'mail_sent'  => 'Mail Gönderildi',
                            'partially'  => 'Kısmi Dönüş',
                            'received'   => 'Tam Dönüş',
                            'completed'  => 'Tamamlandı',
                            'failed'     => 'Hata',
                            default      => $state,
                        };
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Oluşturulma')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                // EN ÖNEMLİ: Firma filtresi (ilk sırada)
                Tables\Filters\SelectFilter::make('customer_id')
                    ->label('Firma')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload()
                    ->placeholder('Tüm Firmalar'),
                
                Tables\Filters\SelectFilter::make('status')
                    ->label('Durum')
                    ->options([
                        'pending' => 'Beklemede',
                        'mail_sent' => 'Mail Gönderildi',
                        'partially' => 'Kısmi Dönüş',
                        'received' => 'Tam Dönüş',
                        'completed' => 'Tamamlandı',
                        'failed' => 'Hata',
                    ]),
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tip')
                    ->options([
                        'banka' => 'Banka Mutabakatı',
                        'cari' => 'Cari Mutabakat',
                    ]),
                Tables\Filters\Filter::make('year')
                    ->form([
                        Forms\Components\TextInput::make('year')
                            ->label('Yıl')
                            ->numeric()
                            ->default(now()->year),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['year'],
                            fn (Builder $query, $year): Builder => $query->where('year', $year),
                        );
                    }),
            ])
            ->filtersLayout(Tables\Enums\FiltersLayout::AboveContent)
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('updateStatus')
                        ->label('Durum Güncelle')
                        ->icon('heroicon-o-arrow-path')
                        ->form([
                            Forms\Components\Select::make('status')
                                ->label('Yeni Durum')
                                ->options([
                                    'pending' => 'Beklemede',
                                    'mail_sent' => 'Mail Gönderildi',
                                    'partially' => 'Kısmi Dönüş',
                                    'received' => 'Tam Dönüş',
                                    'completed' => 'Tamamlandı',
                                    'failed' => 'Hata',
                                ])
                                ->required(),
                        ])
                        ->action(function ($records, array $data) {
                            $count = $records->count();
                            $records->each->update(['status' => $data['status']]);
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Durum güncellendi')
                                ->body("{$count} kaydın durumu güncellendi.")
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
        \App\Filament\Resources\ReconciliationRequestResource\RelationManagers\BanksRelationManager::class,
        \App\Filament\Resources\ReconciliationRequestResource\RelationManagers\IncomingEmailsRelationManager::class,
        \App\Filament\Resources\ReconciliationRequestResource\RelationManagers\DocumentsRelationManager::class,
        \App\Filament\Resources\ReconciliationRequestResource\RelationManagers\EmailsRelationManager::class,
    ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReconciliationRequests::route('/'),
            'create' => Pages\CreateReconciliationRequest::route('/create'),
            'view' => Pages\ViewReconciliationRequest::route('/{record}'),
            'edit' => Pages\EditReconciliationRequest::route('/{record}/edit'),
        ];
    }
}
