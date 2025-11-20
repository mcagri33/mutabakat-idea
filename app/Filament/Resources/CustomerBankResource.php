<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerBankResource\Pages;
use App\Filament\Resources\CustomerBankResource\RelationManagers;
use App\Models\CustomerBank;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CustomerBankResource extends Resource
{
    protected static ?string $model = CustomerBank::class;

    protected static ?string $navigationGroup = 'Tanımlar';
    protected static ?string $navigationLabel = 'Firma Bankaları';
    protected static ?string $pluralLabel = 'Firma Bankaları';
    protected static ?string $slug = 'customer-banks';

    protected static ?string $navigationIcon = 'heroicon-o-building-library';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['customer']);
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
                ->helperText('Banka bilgilerinin bağlı olacağı firmayı seçin.')
                ->placeholder('Firma ara...'),

            Forms\Components\TextInput::make('bank_name')
                ->label('Banka Adı')
                ->required()
                ->maxLength(255)
                ->helperText('Banka adını girin (örn: Ziraat Bankası)'),

            Forms\Components\TextInput::make('branch_name')
                ->label('Şube Adı')
                ->maxLength(255)
                ->helperText('Şube adı (opsiyonel)'),

            Forms\Components\TextInput::make('officer_name')
                ->label('Yetkili / Masa Memuru')
                ->maxLength(255)
                ->helperText('Banka yetkilisinin veya masa memurunun adı'),

            Forms\Components\TextInput::make('officer_email')
                ->label('E-posta')
                ->email()
                ->required()
                ->rules(['email:rfc,dns'])
                ->helperText('Geçerli bir e-posta adresi girin. DNS kontrolü yapılacaktır.'),

            Forms\Components\TextInput::make('officer_phone')
                ->label('Telefon')
                ->tel()
                ->rules(['nullable', 'regex:/^[0-9+\-\s()]+$/'])
                ->maxLength(20)
                ->helperText('Telefon numarası formatı: +90 555 123 45 67'),

            Forms\Components\Toggle::make('is_active')
                ->label('Aktif')
                ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('customer.name')->label('Firma')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('bank_name')->label('Banka')->sortable(),
                Tables\Columns\TextColumn::make('officer_email')->label('E-posta'),
                Tables\Columns\IconColumn::make('is_active')->boolean()->label('Aktif'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('customer_id')
                    ->label('Firma')
                    ->relationship('customer', 'name')
                    ->searchable(),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Aktiflik')
                    ->trueLabel('Aktif')
                    ->falseLabel('Pasif')
                    ->nullable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomerBanks::route('/'),
            'create' => Pages\CreateCustomerBank::route('/create'),
            'edit' => Pages\EditCustomerBank::route('/{record}/edit'),
        ];
    }
}
