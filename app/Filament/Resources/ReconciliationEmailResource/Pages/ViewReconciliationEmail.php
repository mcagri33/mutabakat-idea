<?php

namespace App\Filament\Resources\ReconciliationEmailResource\Pages;

use App\Filament\Resources\ReconciliationEmailResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewReconciliationEmail extends ViewRecord
{
    protected static string $resource = ReconciliationEmailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('view_request')
                ->label('Mutabakat Talebini Görüntüle')
                ->icon('heroicon-o-document-text')
                ->url(fn () => $this->record->request 
                    ? route('filament.mutabakat.resources.reconciliation-requests.view', $this->record->request_id)
                    : null)
                ->visible(fn () => $this->record->request_id !== null)
                ->color('info'),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Email Detayları')
                    ->schema([
                        Infolists\Components\TextEntry::make('sent_to')
                            ->label('Gönderilen E-posta')
                            ->icon('heroicon-m-envelope')
                            ->copyable(),
                        Infolists\Components\TextEntry::make('subject')
                            ->label('Konu'),
                        Infolists\Components\TextEntry::make('status')
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
                            }),
                        Infolists\Components\TextEntry::make('sent_at')
                            ->label('Gönderim Zamanı')
                            ->dateTime('d.m.Y H:i:s'),
                        Infolists\Components\TextEntry::make('error_message')
                            ->label('Hata Mesajı')
                            ->visible(fn ($record) => $record->status === 'failed')
                            ->color('danger')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Email İçeriği')
                    ->schema([
                        Infolists\Components\TextEntry::make('body')
                            ->label('İçerik')
                            ->html()
                            ->visible(fn ($record) => $record->body !== null)
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => $record->body !== null),

                Infolists\Components\Section::make('İlişkili Kayıtlar')
                    ->schema([
                        Infolists\Components\TextEntry::make('request.customer.name')
                            ->label('Müşteri')
                            ->url(fn ($record) => $record->request?->customer 
                                ? route('filament.mutabakat.resources.customers.edit', $record->request->customer_id)
                                : null)
                            ->visible(fn ($record) => $record->request?->customer !== null),
                        Infolists\Components\TextEntry::make('request.id')
                            ->label('Mutabakat Talebi ID')
                            ->url(fn ($record) => $record->request_id 
                                ? route('filament.mutabakat.resources.reconciliation-requests.view', $record->request_id)
                                : null)
                            ->visible(fn ($record) => $record->request_id !== null),
                        Infolists\Components\TextEntry::make('bank.bank_name')
                            ->label('Banka')
                            ->visible(fn ($record) => $record->bank !== null),
                        Infolists\Components\TextEntry::make('bank.officer_name')
                            ->label('Yetkili Adı')
                            ->visible(fn ($record) => $record->bank?->officer_name !== null),
                    ])
                    ->columns(2),
            ]);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Relationship'leri eager load et
        $this->record->load(['request.customer', 'bank']);
        
        return $data;
    }
}
