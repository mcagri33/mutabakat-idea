<?php

namespace App\Filament\Resources\ReconciliationRequestResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Storage;

class DocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'documents';
    protected static ?string $title = 'Gelen Belgeler';

    public function form(Form $form): Form
    {
        $request = $this->ownerRecord;
        
        return $form->schema([
            Forms\Components\Section::make('Belge Bilgileri')
                ->schema([
                    Forms\Components\Select::make('bank_id')
                        ->label('Banka')
                        ->options($request->banks()->pluck('bank_name', 'id'))
                        ->required()
                        ->searchable()
                        ->helperText('Hangi bankadan gelen belge olduğunu seçin.')
                        ->placeholder('Bankayı seçin...'),

                    Forms\Components\FileUpload::make('file_path')
                        ->label('Mutabakat Belgesi')
                        ->directory('reconciliation_documents')
                        ->acceptedFileTypes([
                            'application/pdf',
                            'application/msword',
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            'image/png',
                            'image/jpeg',
                        ])
                        ->maxSize(10240) // 10MB
                        ->required()
                        ->helperText('PDF, Word veya resim formatında mutabakat belgesi yükleyebilirsiniz. Maksimum 10MB.')
                        ->downloadable()
                        ->previewable(),

                    Forms\Components\Textarea::make('notes')
                        ->label('Notlar / Açıklama')
                        ->rows(3)
                        ->placeholder('Belge ile ilgili ek notlar...')
                        ->columnSpanFull(),
                ])
                ->columns(1),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('file_name')
                    ->label('Dosya Adı')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-document')
                    ->iconColor('primary')
                    ->limit(30),

                Tables\Columns\TextColumn::make('file_type')
                    ->label('Dosya Tipi')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => strtoupper($state))
                    ->color(fn (string $state): string => match (strtolower($state)) {
                        'pdf' => 'success',
                        'doc', 'docx' => 'warning',
                        'png', 'jpg', 'jpeg' => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('bank.bank_name')
                    ->label('Banka')
                    ->sortable()
                    ->searchable()
                    ->default('Bilinmiyor'),

                Tables\Columns\TextColumn::make('uploader.name')
                    ->label('Yükleyen')
                    ->sortable()
                    ->default('Sistem'),

                Tables\Columns\TextColumn::make('file_size')
                    ->label('Boyut')
                    ->getStateUsing(function ($record) {
                        if (!Storage::exists($record->file_path)) {
                            return '-';
                        }
                        $size = Storage::size($record->file_path);
                        if ($size < 1024) {
                            return $size . ' B';
                        } elseif ($size < 1048576) {
                            return number_format($size / 1024, 2) . ' KB';
                        } else {
                            return number_format($size / 1048576, 2) . ' MB';
                        }
                    })
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\IconColumn::make('bank.reply_status')
                    ->label('Banka Durumu')
                    ->icon(fn (string $state): string => match ($state) {
                        'completed' => 'heroicon-o-check-circle',
                        'received' => 'heroicon-o-inbox',
                        default => 'heroicon-o-clock',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'completed' => 'success',
                        'received' => 'info',
                        default => 'warning',
                    })
                    ->tooltip(fn ($record) => match ($record->bank->reply_status ?? 'pending') {
                        'completed' => 'Tamamlandı',
                        'received' => 'Belge Alındı',
                        default => 'Beklemede',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Yükleme Tarihi')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->icon('heroicon-o-clock'),
            ])

            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Belge Yükle')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->mutateFormDataUsing(function (array $data) {
                        $data['uploaded_by'] = auth()->id();
                        $data['file_name'] = basename($data['file_path']);
                        $data['file_type'] = pathinfo($data['file_path'], PATHINFO_EXTENSION);
                        $data['uploaded_at'] = now();
                        return $data;
                    })
                    ->after(function ($record) {
                        // Banka durumunu güncelle
                        $bank = $record->bank;
                        if ($bank) {
                            $bank->update([
                                'reply_status' => 'received',
                                'reply_received_at' => now(),
                            ]);
                        }
                    }),
            ])

            ->actions([
                /** 🔥 Filament 3 compatible Download button */
                Action::make('download')
                    ->label('İndir')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(function ($record) {
                        // Güvenlik: Path traversal koruması
                        $filePath = $record->file_path;
                        
                        // Güvenli yol kontrolü
                        if (str_contains($filePath, '..') || !str_starts_with($filePath, 'reconciliation_documents/')) {
                            \Filament\Notifications\Notification::make()
                                ->title('Güvenlik Hatası')
                                ->body('Geçersiz dosya yolu.')
                                ->danger()
                                ->send();
                            return;
                        }
                        
                        // Storage kullanarak güvenli indirme
                        if (!Storage::exists($filePath)) {
                            \Filament\Notifications\Notification::make()
                                ->title('Dosya Bulunamadı')
                                ->body('Dosya sistemde bulunamadı.')
                                ->danger()
                                ->send();
                            return;
                        }
                        
                        return Storage::download($filePath, $record->file_name ?? basename($filePath));
                    }),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('Henüz belge yüklenmedi')
            ->emptyStateDescription('Bankadan gelen mutabakat belgelerini buraya yükleyebilirsiniz.')
            ->emptyStateIcon('heroicon-o-document');
    }
}
