<?php

namespace App\Filament\Resources\CariMutabakatRequestResource\RelationManagers;

use App\Jobs\SendCariMutabakatMailJob;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Alıcı/Satıcı Listesi';

    public function form(Form $form): Form
    {
        return $form->schema([
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
            Forms\Components\TextInput::make('vergi_no')->label('Vergi No'),
            Forms\Components\DatePicker::make('tarih')->label('Tarih')->required()->default(now()),
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
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('unvan')
            ->columns([
                Tables\Columns\TextColumn::make('cari_kodu')->label('Cari Kodu'),
                Tables\Columns\TextColumn::make('unvan')->label('Ünvan')->searchable(),
                Tables\Columns\TextColumn::make('email')->label('E-Posta'),
                Tables\Columns\TextColumn::make('tarih')->label('Tarih')->date('d.m.Y'),
                Tables\Columns\TextColumn::make('bakiye_tipi')->label('B/A'),
                Tables\Columns\TextColumn::make('bakiye')->label('Bakiye')->numeric(decimalPlaces: 2),
                Tables\Columns\TextColumn::make('mail_status')
                    ->label('Mail')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'pending' => 'Beklemede',
                        'sent' => 'Gönderildi',
                        'failed' => 'Hata',
                        default => '-',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'pending' => 'gray',
                        'sent' => 'success',
                        'failed' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('reply_status')
                    ->label('Cevap')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Beklemede',
                        'received' => 'Geldi',
                        'completed' => 'Tamamlandı',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'gray',
                        'received' => 'warning',
                        'completed' => 'success',
                        default => 'gray',
                    }),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()->label('Satır Ekle'),
            ])
            ->actions([
                Tables\Actions\Action::make('sendMail')
                    ->label('Mail Gönder')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('primary')
                    ->visible(fn ($record) => $record->email && $record->reply_status === 'pending')
                    ->requiresConfirmation()
                    ->modalHeading('Mail Gönder')
                    ->modalDescription('Bu alıcı/satıcıya cari mutabakat maili gönderilecek. Token yoksa oluşturulacak.')
                    ->action(function ($record) {
                        if (empty($record->token)) {
                            $record->update(['token' => \App\Models\CariMutabakatItem::generateToken()]);
                        }
                        SendCariMutabakatMailJob::dispatch($record->fresh());
                        Notification::make()
                            ->title('Mail kuyruğa alındı')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
