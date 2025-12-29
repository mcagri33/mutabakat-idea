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

                Tables\Columns\TextColumn::make('mail_status')
                    ->label('Mail Durumu')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'secondary',
                        'sent' => 'primary',
                        'failed' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Beklemede',
                        'sent' => 'Gönderildi',
                        'failed' => 'Hata',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('reply_status')
                    ->label('Cevap Durumu')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'secondary',
                        'received' => 'info',
                        'completed' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Beklemede',
                        'received' => 'Geldi',
                        'completed' => 'Tamamlandı',
                        default => $state,
                    }),
            ])
            ->headerActions([]) // Create yok
            ->actions([
    Tables\Actions\EditAction::make(),

    Tables\Actions\Action::make('sendMail')
        ->label('Mail Gönder')
        ->icon('heroicon-o-paper-airplane')
        ->color('primary')
        ->requiresConfirmation()
        ->modalHeading('Mail Gönder')
        ->modalDescription('Bu bankaya mutabakat maili gönderilecek. Devam etmek istiyor musunuz?')
        ->modalSubmitActionLabel('Gönder')
        ->modalCancelActionLabel('İptal')
        ->action(function ($record) {
            try {
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
                    ->body('Mutabakat maili ' . $record->officer_email . ' adresine gönderildi.')
                    ->success()
                    ->send();
            } catch (\Exception $e) {
                // Hata durumunda güncelle
                $record->update([
                    'mail_status' => 'failed',
                ]);

                \Filament\Notifications\Notification::make()
                    ->title('Mail gönderilemedi')
                    ->body('Hata: ' . $e->getMessage())
                    ->danger()
                    ->send();
            }
        }),

                Tables\Actions\Action::make('markAsReceived')
                    ->label('Cevap Geldi Olarak İşaretle')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => $record->reply_status === 'pending')
                    ->requiresConfirmation()
                    ->modalHeading('Cevap Geldi Olarak İşaretle')
                    ->modalDescription('Bu bankadan cevap geldi olarak işaretlenecek. Belge yüklü değilse sonradan "Gelen Belgeler" sekmesinden ekleyebilirsiniz.')
                    ->modalSubmitActionLabel('İşaretle')
                    ->modalCancelActionLabel('İptal')
                    ->action(function ($record) {
                        $record->update([
                            'reply_status' => 'received',
                            'reply_received_at' => now(),
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Durum güncellendi')
                            ->body($record->bank_name . ' bankasından cevap geldi olarak işaretlendi.')
                            ->success()
                            ->send();
                    }),
            ])

            ->bulkActions([]);
    }
}
