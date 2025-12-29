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
    protected static ?string $navigationLabel = 'Email Logları';
    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Email Bilgileri')
                    ->schema([
                        Forms\Components\TextInput::make('sent_to')
                            ->label('Gönderilen E-posta')
                            ->email()
                            ->disabled(),
                        Forms\Components\TextInput::make('subject')
                            ->label('Konu')
                            ->disabled(),
                        Forms\Components\Textarea::make('body')
                            ->label('İçerik')
                            ->rows(10)
                            ->disabled(),
                        Forms\Components\Select::make('status')
                            ->label('Durum')
                            ->options([
                                'sent' => 'Gönderildi',
                                'failed' => 'Başarısız',
                                'bounced' => 'Geri Döndü',
                            ])
                            ->disabled(),
                        Forms\Components\DateTimePicker::make('sent_at')
                            ->label('Gönderim Zamanı')
                            ->disabled(),
                        Forms\Components\Textarea::make('error_message')
                            ->label('Hata Mesajı')
                            ->rows(5)
                            ->visible(fn ($record) => $record && $record->status === 'failed')
                            ->disabled(),
                    ]),
                Forms\Components\Section::make('İlişkili Kayıtlar')
                    ->schema([
                        Forms\Components\Select::make('request_id')
                            ->label('Mutabakat Talebi')
                            ->relationship('request', 'id', fn ($query) => $query->with('customer'))
                            ->disabled(),
                        Forms\Components\Select::make('bank_id')
                            ->label('Banka')
                            ->relationship('bank', 'bank_name')
                            ->disabled(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['request.customer', 'bank']))
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('request.customer.name')
                    ->label('Müşteri')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('bank.bank_name')
                    ->label('Banka')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('sent_to')
                    ->label('Gönderilen E-posta')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->icon('heroicon-m-envelope'),

                Tables\Columns\TextColumn::make('subject')
                    ->label('Konu')
                    ->limit(50)
                    ->searchable()
                    ->tooltip(fn ($record) => $record->subject),

                Tables\Columns\TextColumn::make('status')
                    ->label('Durum')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'sent' => 'success',
                        'failed' => 'danger',
                        'bounced' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'sent' => 'Gönderildi',
                        'failed' => 'Başarısız',
                        'bounced' => 'Geri Döndü',
                        default => $state,
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('sent_at')
                    ->label('Gönderim Tarihi')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('error_message')
                    ->label('Hata Mesajı')
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->error_message)
                    ->visible(fn ($record) => $record->status === 'failed')
                    ->color('danger')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Oluşturulma')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Durum')
                    ->options([
                        'sent' => 'Gönderildi',
                        'failed' => 'Başarısız',
                        'bounced' => 'Geri Döndü',
                    ]),

                Tables\Filters\SelectFilter::make('request_id')
                    ->label('Mutabakat Talebi')
                    ->relationship('request', 'id', fn ($query) => $query->with('customer'))
                    ->searchable()
                    ->preload(),

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

                Tables\Filters\Filter::make('has_error')
                    ->label('Hata Olanlar')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('error_message')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultPaginationPageOption(25)
            ->poll('30s');
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

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('status', 'failed')->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }
}
