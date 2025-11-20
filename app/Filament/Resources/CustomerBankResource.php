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
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                 Forms\Components\Select::make('customer_id')
                ->label('Firma')
                ->relationship('customer', 'name')
                ->searchable()
                ->required(),

            Forms\Components\TextInput::make('bank_name')
                ->label('Banka Adı')
                ->required(),

            Forms\Components\TextInput::make('branch_name')
                ->label('Şube Adı'),

            Forms\Components\TextInput::make('officer_name')
                ->label('Yetkili / Masa Memuru'),

            Forms\Components\TextInput::make('officer_email')
                ->label('E-posta')
                ->email()
                ->required(),

            Forms\Components\TextInput::make('officer_phone')
                ->label('Telefon'),

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
                //
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
