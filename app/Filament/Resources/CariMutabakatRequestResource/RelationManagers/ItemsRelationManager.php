<?php

namespace App\Filament\Resources\CariMutabakatRequestResource\RelationManagers;

use App\Jobs\SendCariMutabakatMailJob;
use App\Services\CariMutabakatPdfService;
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
            ->modifyQueryUsing(fn ($query) => $query->with('reply'))
            ->recordTitleAttribute('unvan')
            ->columns([
                Tables\Columns\TextColumn::make('cari_kodu')->label('Cari Kodu'),
                Tables\Columns\TextColumn::make('unvan')->label('Ünvan')->searchable(),
                Tables\Columns\TextColumn::make('email')->label('E-Posta'),
                Tables\Columns\TextColumn::make('tarih')->label('Tarih')->date('d.m.Y'),
                Tables\Columns\TextColumn::make('request.year')->label('Dönem')->formatStateUsing(fn ($record) => $record && $record->request ? '31.12.' . $record->request->year : '-'),
                Tables\Columns\TextColumn::make('bakiye_tipi')->label('B/A'),
                Tables\Columns\TextColumn::make('bakiye')
                    ->label('Bakiye')
                    ->formatStateUsing(fn ($record) => $record
                        ? number_format($record->bakiye ?? 0, 2, ',', '.') . ' ' . ($record->pb ?? 'TL')
                        : '-'),
                Tables\Columns\TextColumn::make('reply.cevap')
                    ->label('Durum')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'mutabıkız' => 'Mutabıkız',
                        'mutabık_değiliz' => 'Mutabık Değiliz',
                        default => '-',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'mutabıkız' => 'success',
                        'mutabık_değiliz' => 'danger',
                        default => 'gray',
                    })
                    ->placeholder('-'),
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
                Tables\Actions\CreateAction::make()
                    ->label('Satır Ekle')
                    ->mutateFormDataUsing(fn (array $data): array => array_merge($data, ['tarih' => now()])),
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
                Tables\Actions\Action::make('downloadEkstre')
                    ->label('Ekstre İndir')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->visible(fn ($record) => $record->reply?->ekstre_path)
                    ->action(function ($record) {
                        $path = $record->reply->ekstre_path;
                        if (str_contains($path, '..')) {
                            Notification::make()->title('Geçersiz yol')->danger()->send();
                            return;
                        }
                        $fullPath = storage_path('app/public/' . $path);
                        if (!file_exists($fullPath)) {
                            Notification::make()->title('Dosya bulunamadı')->danger()->send();
                            return;
                        }
                        return response()->download($fullPath, basename($path));
                    }),
                Tables\Actions\Action::make('downloadEImzaliForm')
                    ->label('E-İmzalı Form İndir')
                    ->icon('heroicon-o-document')
                    ->color('gray')
                    ->visible(fn ($record) => $record->reply?->e_imzali_form_path)
                    ->action(function ($record) {
                        $path = $record->reply->e_imzali_form_path;
                        if (str_contains($path, '..')) {
                            Notification::make()->title('Geçersiz yol')->danger()->send();
                            return;
                        }
                        $fullPath = storage_path('app/public/' . $path);
                        if (!file_exists($fullPath)) {
                            Notification::make()->title('Dosya bulunamadı')->danger()->send();
                            return;
                        }
                        return response()->download($fullPath, basename($path));
                    }),
                Tables\Actions\Action::make('generateCariGeriDonusPdf')
                    ->label('PDF Oluştur')
                    ->icon('heroicon-o-document-plus')
                    ->color('warning')
                    ->visible(fn ($record) => $record->reply && !$record->reply->pdf_path)
                    ->requiresConfirmation()
                    ->modalHeading('Cari Geri Dönüş PDF Oluştur')
                    ->modalDescription('Bu cevap için PDF oluşturulacak. LibreOffice kurulu olmalıdır.')
                    ->action(function ($record) {
                        try {
                            $pdfService = app(CariMutabakatPdfService::class);
                            $pdfPath = $pdfService->generatePdf($record->fresh());
                            $record->reply->update(['pdf_path' => $pdfPath]);
                            Notification::make()
                                ->title('PDF oluşturuldu')
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('PDF oluşturulamadı')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\Action::make('downloadCariGeriDonusPdf')
                    ->label('Cari Geri Dönüş PDF')
                    ->icon('heroicon-o-document-text')
                    ->color('success')
                    ->visible(fn ($record) => $record->reply?->pdf_path)
                    ->action(function ($record) {
                        $path = $record->reply->pdf_path;
                        if (str_contains($path, '..')) {
                            Notification::make()->title('Geçersiz yol')->danger()->send();
                            return;
                        }
                        $fullPath = \Illuminate\Support\Facades\Storage::disk('local')->path($path);
                        if (!file_exists($fullPath)) {
                            Notification::make()->title('PDF bulunamadı')->danger()->send();
                            return;
                        }
                        $filename = 'Cari_Geri_Donus_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $record->unvan ?? 'item') . '.pdf';
                        return response()->download($fullPath, $filename);
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
