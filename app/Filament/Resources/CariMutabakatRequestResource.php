<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CariMutabakatRequestResource\Pages;
use App\Models\CariMutabakatRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CariMutabakatRequestResource extends Resource
{
    protected static ?string $model = CariMutabakatRequest::class;

    protected static ?string $navigationGroup = 'Mutabakat Yönetimi';
    protected static ?string $navigationLabel = 'Cari Mutabakat Talepleri';
    protected static ?string $pluralLabel = 'Cari Mutabakat Talepleri';
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['customer', 'items.reply']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Talep Bilgileri')
                    ->schema([
                        Forms\Components\Select::make('customer_id')
                            ->label('Denetlenen Firma')
                            ->relationship('customer', 'name')
                            ->searchable()
                            ->required()
                            ->placeholder('Firma ara...'),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('year')
                                    ->label('Yıl')
                                    ->numeric()
                                    ->required()
                                    ->default(now()->year - 1)
                                    ->minValue(2020)
                                    ->maxValue(now()->year),

                                Forms\Components\Select::make('month')
                                    ->label('Ay')
                                    ->options([
                                        1 => 'Ocak', 2 => 'Şubat', 3 => 'Mart', 4 => 'Nisan',
                                        5 => 'Mayıs', 6 => 'Haziran', 7 => 'Temmuz', 8 => 'Ağustos',
                                        9 => 'Eylül', 10 => 'Ekim', 11 => 'Kasım', 12 => 'Aralık',
                                    ])
                                    ->default(12)
                                    ->placeholder('Seçin'),
                            ]),

                        Forms\Components\Textarea::make('notes')
                            ->label('Notlar')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('İşlem Girişleri (Alıcı/Satıcı Listesi)')
                    ->description('120 = Alıcılar, 320 = Satıcılar. Her satır için mutabakat mektubu gönderilir.')
                    ->visibleOn('create')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->schema([
                                Forms\Components\TextInput::make('hesap_tipi')->label('Hesap Tipi'),
                                Forms\Components\TextInput::make('referans')->label('Referans'),
                                Forms\Components\TextInput::make('cari_kodu')
                                    ->label('Cari Kodu')
                                    ->placeholder('120 veya 320')
                                    ->required(),
                                Forms\Components\TextInput::make('unvan')->label('Ünvan')->required(),
                                Forms\Components\TextInput::make('email')->label('E-Posta')->email()->required(),
                                Forms\Components\TextInput::make('cc_email')->label('CC E-Posta')->email(),
                                Forms\Components\TextInput::make('tel_no')->label('Tel No'),
                                Forms\Components\Select::make('bakiye_tipi')
                                    ->label('B/A')
                                    ->options(['Borç' => 'Borç', 'Alacak' => 'Alacak'])
                                    ->default('Borç'),
                                Forms\Components\TextInput::make('bakiye')
                                    ->label('Bakiye')
                                    ->numeric()
                                    ->default(0),
                                Forms\Components\TextInput::make('pb')->label('PB')->placeholder('TL')->default('TL'),
                                Forms\Components\TextInput::make('karsiligi')->label('Karşılığı')->numeric(),
                            ])
                            ->columns(4)
                            ->defaultItems(1)
                            ->addActionLabel('Satır Ekle')
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => ($state['unvan'] ?? null) . ' (' . ($state['cari_kodu'] ?? '') . ')'),
                    ])
                    ->collapsed(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('customer.name')->label('Denetlenen Firma')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('year')->label('Yıl')->sortable(),
                Tables\Columns\TextColumn::make('month')
                    ->label('Ay')
                    ->formatStateUsing(fn ($state) => $state ? [
                    1 => 'Ocak', 2 => 'Şubat', 3 => 'Mart', 4 => 'Nisan',
                    5 => 'Mayıs', 6 => 'Haziran', 7 => 'Temmuz', 8 => 'Ağustos',
                    9 => 'Eylül', 10 => 'Ekim', 11 => 'Kasım', 12 => 'Aralık',
                ][(int) $state] ?? $state : '-'),
                Tables\Columns\TextColumn::make('items_count')->label('Satır')->counts('items')->sortable(),
                Tables\Columns\TextColumn::make('mutabikiz_count')
                    ->label('Mutabıkız')
                    ->getStateUsing(fn ($record) => $record->items->filter(fn ($i) => $i->reply?->cevap === 'mutabıkız')->count())
                    ->badge()
                    ->color('success')
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('mutabik_degiliz_count')
                    ->label('Mutabık Değiliz')
                    ->getStateUsing(fn ($record) => $record->items->filter(fn ($i) => $i->reply?->cevap === 'mutabık_değiliz')->count())
                    ->badge()
                    ->color('danger')
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('bekleyen_count')
                    ->label('Bekleyen')
                    ->getStateUsing(fn ($record) => $record->items->where('reply_status', 'pending')->count())
                    ->badge()
                    ->color('warning')
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Durum')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'pending' => 'Beklemede',
                        'sent' => 'Gönderildi',
                        'partially_received' => 'Kısmi Dönüş',
                        'completed' => 'Tamamlandı',
                        'failed' => 'Hata',
                        default => $state,
                    })
                    ->color(fn ($state) => match ($state) {
                        'pending' => 'gray',
                        'sent' => 'primary',
                        'partially_received' => 'warning',
                        'completed' => 'success',
                        'failed' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')->label('Oluşturulma')->dateTime('d.m.Y H:i')->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('customer_id')
                    ->label('Firma')
                    ->relationship('customer', 'name')
                    ->searchable(),
            ])
            ->actions([Tables\Actions\ViewAction::make(), Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Resources\CariMutabakatRequestResource\RelationManagers\ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCariMutabakatRequests::route('/'),
            'create' => Pages\CreateCariMutabakatRequest::route('/create'),
            'view' => Pages\ViewCariMutabakatRequest::route('/{record}'),
            'edit' => Pages\EditCariMutabakatRequest::route('/{record}/edit'),
        ];
    }
}
