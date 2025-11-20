<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReconciliationEmailResource\Pages;
use App\Filament\Resources\ReconciliationEmailResource\RelationManagers;
use App\Models\ReconciliationEmail;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ReconciliationEmailResource extends Resource
{
    protected static ?string $model = ReconciliationEmail::class;

protected static ?string $navigationGroup = 'Mutabakat Yönetimi';
    protected static ?string $navigationIcon = 'heroicon-o-envelope-open';
    protected static ?string $label = 'Mail Logları';
    protected static ?string $pluralLabel = 'Mail Logları';
    protected static ?string $navigationLabel = 'Gönderilen Mailler';
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
            Forms\Components\TextInput::make('sent_to')->label('Gönderilen E-posta')->disabled(),
            Forms\Components\TextInput::make('subject')->label('Başlık')->disabled(),
            Forms\Components\Textarea::make('body')->label('İçerik')->rows(10)->disabled(),
            Forms\Components\TextInput::make('status')->label('Durum')->disabled(),
            Forms\Components\DateTimePicker::make('sent_at')->label('Gönderim Zamanı')->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sent_to')
                ->label('Gönderilen Kişi')
                ->searchable(),

            Tables\Columns\TextColumn::make('subject')
                ->label('Konu')
                ->limit(40)
                ->searchable(),

            Tables\Columns\BadgeColumn::make('status')
                ->label('Durum')
                ->colors([
                    'success' => 'sent',
                    'danger'  => 'failed',
                ]),

            Tables\Columns\TextColumn::make('sent_at')
                ->label('Gönderim Tarihi')
                ->dateTime(),
            ])        
            ->defaultSort('id', 'desc')

            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
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
            'index' => Pages\ListReconciliationEmails::route('/'),
            'view' => Pages\ViewReconciliationEmail::route('/{record}'),
        ];
    }
}
