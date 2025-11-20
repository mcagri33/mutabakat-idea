<?php

namespace App\Filament\Resources\ReconciliationRequestResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;

class IncomingEmailsRelationManager extends RelationManager
{
    protected static string $relationship = 'incomingEmails';
    protected static ?string $title = 'Gelen Mail\'ler';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Mail Bilgileri')
                ->schema([
                    Forms\Components\TextInput::make('from_email')
                        ->label('Gönderen E-posta')
                        ->disabled(),
                    
                    Forms\Components\TextInput::make('from_name')
                        ->label('Gönderen Adı')
                        ->disabled(),
                    
                    Forms\Components\TextInput::make('subject')
                        ->label('Konu')
                        ->disabled(),
                    
                    Forms\Components\Textarea::make('body')
                        ->label('Mail İçeriği')
                        ->rows(10)
                        ->disabled()
                        ->columnSpanFull(),
                    
                    Forms\Components\Textarea::make('match_notes')
                        ->label('Eşleştirme Notları')
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('from_name')
                    ->label('Gönderen')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-user'),
                
                Tables\Columns\TextColumn::make('from_email')
                    ->label('E-posta')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-envelope'),
                
                Tables\Columns\TextColumn::make('subject')
                    ->label('Konu')
                    ->limit(50)
                    ->searchable()
                    ->wrap(),
                
                Tables\Columns\IconColumn::make('attachments')
                    ->label('Ek')
                    ->boolean()
                    ->getStateUsing(fn ($record) => !empty($record->attachments))
                    ->trueIcon('heroicon-o-paper-clip')
                    ->falseIcon('heroicon-o-minus'),
                
                Tables\Columns\TextColumn::make('bank.bank_name')
                    ->label('Banka')
                    ->sortable()
                    ->searchable()
                    ->default('Eşleşmedi'),
                
                Tables\Columns\TextColumn::make('match_status')
                    ->label('Durum')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'matched' => 'success',
                        'pending' => 'warning',
                        'unmatched' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'matched' => 'Eşleşti',
                        'pending' => 'Beklemede',
                        'unmatched' => 'Eşleşmedi',
                        default => $state,
                    }),
                
                Tables\Columns\TextColumn::make('received_at')
                    ->label('Geliş Tarihi')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->icon('heroicon-o-clock'),
            ])
            ->headerActions([])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalHeading('Mail Detayı')
                    ->modalContent(function ($record) {
                        return view('filament.resources.reconciliation-request-resource.relation-managers.incoming-email-view', [
                            'email' => $record,
                        ]);
                    })
                    ->modalSubmitAction(false),
                
                Action::make('match')
                    ->label('Eşleştir')
                    ->icon('heroicon-o-link')
                    ->color('warning')
                    ->visible(fn ($record) => $record->match_status === 'unmatched')
                    ->form([
                        Forms\Components\Select::make('bank_id')
                            ->label('Banka')
                            ->relationship('request.banks', 'bank_name')
                            ->searchable()
                            ->required(),
                    ])
                    ->action(function ($record, array $data) {
                        $bank = \App\Models\ReconciliationBank::find($data['bank_id']);
                        if ($bank) {
                            $record->update([
                                'bank_id' => $bank->id,
                                'request_id' => $bank->request_id,
                                'match_status' => 'matched',
                            ]);
                            
                            $bank->update([
                                'reply_status' => 'received',
                                'reply_received_at' => now(),
                            ]);
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Mail eşleştirildi')
                                ->success()
                                ->send();
                        }
                    }),
            ])
            ->defaultSort('received_at', 'desc')
            ->emptyStateHeading('Henüz mail gelmedi')
            ->emptyStateDescription('Bankalardan gelen mutabakat mail\'leri burada görünecektir.')
            ->emptyStateIcon('heroicon-o-envelope');
    }
}

