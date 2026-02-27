<?php

namespace App\Filament\Resources\ReconciliationRequestResource\RelationManagers;

use App\Models\CustomerBank;
use App\Models\ReconciliationBank;
use App\Jobs\SendReconciliationMailJob;
use App\Services\MutabakatService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;

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
            ->headerActions([
                Action::make('addMissingBanks')
                    ->label('Eksik bankaları ekle')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Eksik bankaları ekle')
                    ->modalDescription('Bu talepte henüz yer almayan firma bankaları listeye eklenecek ve e-posta adresi olanlara mutabakat maili gönderilecek. Devam edilsin mi?')
                    ->modalSubmitActionLabel('Ekle ve mail gönder')
                    ->modalCancelActionLabel('İptal')
                    ->action(function (): void {
                        $request = $this->getOwnerRecord();
                        $existingBankIds = $request->banks()->whereNotNull('customer_bank_id')->pluck('customer_bank_id')->filter()->all();
                        $customerBanks = CustomerBank::query()
                            ->where('customer_id', $request->customer_id)
                            ->where('is_active', true)
                            ->when(!empty($existingBankIds), fn ($q) => $q->whereNotIn('id', $existingBankIds))
                            ->get();

                        $added = 0;
                        $mailsQueued = 0;

                        foreach ($customerBanks as $bank) {
                            $recBank = ReconciliationBank::create([
                                'request_id'        => $request->id,
                                'customer_id'       => $request->customer_id,
                                'customer_bank_id'  => $bank->id,
                                'bank_name'         => $bank->bank_name,
                                'branch_name'       => $bank->branch_name,
                                'officer_name'      => $bank->officer_name,
                                'officer_email'     => $bank->officer_email,
                                'officer_phone'     => $bank->officer_phone,
                                'mail_status'       => 'pending',
                                'reply_status'      => 'pending',
                            ]);
                            $added++;
                            if ($bank->officer_email) {
                                SendReconciliationMailJob::dispatch($recBank);
                                $mailsQueued++;
                            }
                        }

                        if ($added > 0) {
                            Notification::make()
                                ->title('Eksik bankalar eklendi')
                                ->body($mailsQueued > 0
                                    ? "{$added} banka eklendi, {$mailsQueued} adrese mail gönderimi kuyruğa alındı."
                                    : "{$added} banka eklendi. E-posta adresi olan banka bulunamadığı için mail gönderilmedi.")
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Eklenecek banka yok')
                                ->body('Bu talepte zaten tüm firma bankaları mevcut veya eklenecek aktif banka bulunamadı.')
                                ->warning()
                                ->send();
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('downloadBankLetterPdf')
                    ->label('Banka Mektubu PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('gray')
                    ->tooltip('Güncel tarihli banka mutabakat mektubu PDF\'i indir (kaşe imzalı gönderim için)')
                    ->action(function ($record) {
                        $record->load(['request.customer', 'customer']);
                        $request = $record->request;
                        $customer = $record->customer;
                        if (!$request || !$customer) {
                            Notification::make()->title('Talep veya müşteri bulunamadı')->danger()->send();
                            return;
                        }
                        try {
                            $mutabakatService = app(MutabakatService::class);
                            $pdfPath = $mutabakatService->generatePdf($request, $customer, $record);
                            $filename = 'Banka-Mutabakat-Mektubu_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $customer->name ?? 'firma') . '_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $record->bank_name ?? 'banka') . '_' . now()->format('Y-m-d') . '.pdf';
                            $response = response()->download($pdfPath, $filename, ['Content-Type' => 'application/pdf']);
                            if (file_exists($pdfPath)) {
                                register_shutdown_function(fn () => @unlink($pdfPath));
                            }
                            return $response;
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('PDF oluşturulamadı')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
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
            // Queue'ya ekle - asenkron gönderim
            \App\Jobs\SendReconciliationMailJob::dispatch($record);

            \Filament\Notifications\Notification::make()
                ->title('Mail gönderimi başlatıldı')
                ->body('Mutabakat maili ' . $record->officer_email . ' adresine gönderilmek üzere kuyruğa eklendi. Email arka planda gönderilecek.')
                ->success()
                ->send();
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

                        // Request status'ünü güncelle (observer otomatik çağrılır ama manuel de çağıralım)
                        $record->updateRequestStatus();

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
