<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReconciliationRequestResource\Pages;
use App\Models\ReconciliationRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ReconciliationRequestResource extends Resource
{
    protected static ?string $model = ReconciliationRequest::class;

    protected static ?string $navigationGroup = 'Mutabakat Yönetimi';
    protected static ?string $navigationLabel = 'Mutabakat Talepleri';
    protected static ?string $pluralLabel = 'Mutabakat Talepleri';
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('customer_id')
                    ->label('Firma')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->required()
                    ->reactive(),

                Forms\Components\Select::make('type')
                    ->label('Mutabakat Tipi')
                    ->options([
                        'banka' => 'Banka Mutabakatı',
                        'cari' => 'Cari Mutabakat',
                    ])
                    ->default('banka')
                    ->required(),

                Forms\Components\TextInput::make('year')
                    ->label('Yıl')
                    ->numeric()
                    ->default(now()->year)
                    ->required(),

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
                    ->disabled(),

                Forms\Components\Textarea::make('notes')
                    ->label('Notlar')
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
                    ->searchable(),

                Tables\Columns\TextColumn::make('year')
                    ->label('Yıl'),

                Tables\Columns\BadgeColumn::make('type')
                    ->label('Tür')
                    ->colors([
                        'info' => 'banka',
                        'warning' => 'cari',
                    ])
                    ->formatStateUsing(fn ($state) => $state === 'banka' ? 'Banka' : 'Cari'),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Durum')
                    ->colors([
                        'secondary' => 'pending',
                        'primary' => 'mail_sent',
                        'warning' => 'partially',
                        'info' => 'received',
                        'success' => 'completed',
                        'danger' => 'failed',
                    ])
                    ->formatStateUsing(function ($state) {
                        return match ($state) {
                            'pending'    => 'Beklemede',
                            'mail_sent'  => 'Mail Gönderildi',
                            'partially'  => 'Kısmi Dönüş',
                            'received'   => 'Tam Dönüş',
                            'completed'  => 'Tamamlandı',
                            'failed'     => 'Hata',
                        };
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Oluşturulma')
                    ->dateTime('d.m.Y H:i'),
            ])
            ->defaultSort('id', 'desc')

            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
        \App\Filament\Resources\ReconciliationRequestResource\RelationManagers\BanksRelationManager::class,
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
