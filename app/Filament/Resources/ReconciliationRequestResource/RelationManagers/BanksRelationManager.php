<?php

namespace App\Filament\Resources\ReconciliationRequestResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class BanksRelationManager extends RelationManager
{
    protected static string $relationship = 'banks';
    protected static ?string $title = 'Banka Listesi';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('bank_name')
                ->label('Banka')
                ->disabled(),

            Forms\Components\TextInput::make('officer_name')
                ->label('Yetkili Adı')
                ->disabled(),

            Forms\Components\TextInput::make('officer_email')
                ->label('Yetkili E-posta')
                ->disabled(),

            Forms\Components\TextInput::make('officer_phone')
                ->label('Telefon')
                ->disabled(),

            Forms\Components\Select::make('mail_status')
            ->label('Mail Durumu')
            ->options([
                'pending' => 'Beklemede',
                'sent' => 'Gönderildi',
                'failed' => 'Hata',
            ])
            ->default('pending'),

            Forms\Components\Select::make('reply_status')
                ->label('Cevap Durumu')
                ->options([
                    'pending' => 'Beklemede',
                    'received' => 'Geldi',
                    'completed' => 'Tamamlandı',
                ])
                ->default('pending'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('bank_name')
            ->columns([
                Tables\Columns\TextColumn::make('bank_name')->label('Banka'),
                Tables\Columns\TextColumn::make('officer_email')->label('Yetkili E-posta'),
                Tables\Columns\TextColumn::make('officer_phone')->label('Telefon'),

                Tables\Columns\BadgeColumn::make('mail_status')
                ->label('Mail Durumu')
                ->colors([
                    'secondary' => 'pending',
                    'primary' => 'sent',
                    'danger' => 'failed',
                ]),

            Tables\Columns\BadgeColumn::make('reply_status')
                ->label('Cevap Durumu')
                ->colors([
                    'secondary' => 'pending',
                    'info' => 'received',
                    'success' => 'completed',
                ]),
            ])
            ->headerActions([]) // Create yok
            ->actions([
    Tables\Actions\EditAction::make(),

    Tables\Actions\Action::make('sendMail')
        ->label('Mail Gönder')
        ->icon('heroicon-o-paper-airplane')
        ->color('primary')
        ->requiresConfirmation()
        ->action(function ($record) {

            // Mail gönderme servisi
            app(\App\Services\ReconciliationMailService::class)
                ->sendBankMail($record);

            // Mail durumu güncelle
            $record->update([
                'mail_status'  => 'sent',
                'mail_sent_at' => now(),
            ]);

            \Filament\Notifications\Notification::make()
                ->title('Mail başarıyla gönderildi')
                ->success()
                ->send();
                 }),
            ])

            ->bulkActions([]);
    }
}
