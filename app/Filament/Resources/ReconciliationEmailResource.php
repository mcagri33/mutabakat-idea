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

            Tables\Columns\TextColumn::make('status')
                ->label('Durum')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'sent' => 'success',
                    'failed' => 'danger',
                    default => 'gray',
                })
                ->formatStateUsing(fn (string $state): string => match ($state) {
                    'sent' => 'Gönderildi',
                    'failed' => 'Başarısız',
                    default => $state,
                }),

            Tables\Columns\TextColumn::make('sent_at')
                ->label('Gönderim Tarihi')
                ->dateTime(),
            ])        
            ->defaultSort('id', 'desc')

            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Durum')
                    ->options([
                        'sent' => 'Gönderildi',
                        'failed' => 'Başarısız',
                    ]),
                Tables\Filters\Filter::make('sent_at')
                    ->form([
                        Forms\Components\DatePicker::make('sent_from')
                            ->label('Başlangıç Tarihi'),
                        Forms\Components\DatePicker::make('sent_until')
                            ->label('Bitiş Tarihi'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['sent_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('sent_at', '>=', $date),
                            )
                            ->when(
                                $data['sent_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('sent_at', '<=', $date),
                            );
                    }),
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
