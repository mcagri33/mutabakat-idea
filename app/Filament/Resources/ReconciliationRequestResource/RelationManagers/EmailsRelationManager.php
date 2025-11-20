<?php

namespace App\Filament\Resources\ReconciliationRequestResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class EmailsRelationManager extends RelationManager
{
    protected static string $relationship = 'emails';
    protected static ?string $title = 'Mail Kayıtları';

    public function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([

                Tables\Columns\TextColumn::make('sent_to')
                    ->label('Kime')
                    ->searchable(),

                Tables\Columns\TextColumn::make('subject')
                    ->label('Konu'),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Durum')
                    ->colors([
                        'success' => 'sent',
                        'danger' => 'failed',
                        'warning' => 'bounced',
                    ]),

                Tables\Columns\TextColumn::make('sent_at')
                    ->label('Gönderim Tarihi')
                    ->dateTime('d.m.Y H:i'),
            ])

            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('İçeriği Göster')
                    ->modalContent(fn($record) => view('emails.log-view', ['record' => $record])),
            ]);
    }
}
