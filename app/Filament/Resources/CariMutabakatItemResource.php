<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CariMutabakatItemResource\Pages;
use App\Jobs\SendCariMutabakatMailJob;
use App\Models\CariMutabakatItem;
use App\Services\CariMutabakatPdfService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CariMutabakatItemResource extends Resource
{
    protected static ?string $model = CariMutabakatItem::class;

    protected static ?string $navigationGroup = 'Mutabakat Yönetimi';
    protected static ?string $navigationLabel = 'Cari Mutabakat İşlemleri';
    protected static ?string $pluralLabel = 'Cari Mutabakat İşlemleri';
    protected static ?string $slug = 'cari-mutabakat-items';
    protected static ?string $navigationIcon = 'heroicon-o-list-bullet';
    protected static ?int $navigationSort = 3;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['request.customer', 'reply']);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('hesap_tipi')->label('Hesap Tipi'),
            Forms\Components\TextInput::make('referans')->label('Referans'),
            Forms\Components\TextInput::make('cari_kodu')->label('Cari Kodu')->required(),
            Forms\Components\TextInput::make('unvan')->label('Ünvan')->required(),
            Forms\Components\TextInput::make('email')->label('E-Posta')->email()->required(),
            Forms\Components\TextInput::make('cc_email')->label('CC E-Posta')->email(),
            Forms\Components\TextInput::make('tel_no')->label('Tel No'),
            Forms\Components\DatePicker::make('tarih')->label('Tarih')->displayFormat('d.m.Y')->native(false),
            Forms\Components\Select::make('bakiye_tipi')
                ->label('B/A')
                ->options(['Borç' => 'Borç', 'Alacak' => 'Alacak'])
                ->default('Borç'),
            Forms\Components\TextInput::make('bakiye')->label('Bakiye')->numeric()->default(0),
            Forms\Components\TextInput::make('pb')->label('PB')->placeholder('TL')->default('TL'),
            Forms\Components\TextInput::make('karsiligi')->label('Karşılığı')->numeric(),
            Forms\Components\TextInput::make('karsiligi_pb')->label('Karşılığı PB')->placeholder('TRY')->default('TRY'),
        ]);
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('reply_status', 'pending')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('request.customer.name')
                    ->label('Firma')
                    ->searchable()
                    ->sortable()
                    ->url(fn ($record) => route('filament.mutabakat.resources.cari-mutabakat-requests.view', ['record' => $record->request_id])),

                Tables\Columns\TextColumn::make('request.year')->label('Yıl')->sortable()->alignCenter(),
                Tables\Columns\TextColumn::make('cari_kodu')->label('Cari Kodu'),
                Tables\Columns\TextColumn::make('unvan')->label('Ünvan')->searchable(),
                Tables\Columns\TextColumn::make('email')->label('E-Posta')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('tarih')->label('Tarih')->date('d.m.Y'),
                Tables\Columns\TextColumn::make('request.year')->label('Dönem')->formatStateUsing(fn ($record) => $record && $record->request ? '31.12.' . $record->request->year : '-'),
                Tables\Columns\TextColumn::make('bakiye_tipi')->label('B/A'),
                Tables\Columns\TextColumn::make('bakiye')
                    ->label('Bakiye')
                    ->formatStateUsing(fn ($record) => number_format($record->bakiye ?? 0, 2, ',', '.') . ' ' . ($record->pb ?? 'TL')),
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
                Tables\Columns\TextColumn::make('reply.ekstre_path')
                    ->label('Ekstre')
                    ->formatStateUsing(fn (?string $state): string => $state ? 'Var' : '-')
                    ->badge()
                    ->color(fn (?string $state): string => $state ? 'success' : 'gray'),
                Tables\Columns\TextColumn::make('reply.aciklama')
                    ->label('Açıklama')
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->reply?->aciklama)
                    ->placeholder('-'),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('request_id')
                    ->label('Talep')
                    ->relationship('request', 'id', fn ($query) => $query->with('customer')->orderBy('id', 'desc'))
                    ->getOptionLabelFromRecordUsing(fn ($record) => ($record->customer?->name ?? 'Firma') . ' - ' . ($record->year ?? ''))
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('reply_status')
                    ->label('Cevap Durumu')
                    ->options([
                        'pending' => 'Beklemede',
                        'received' => 'Geldi',
                        'completed' => 'Tamamlandı',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('downloadEkstre')
                    ->label('Ekstre')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->visible(fn ($record) => $record->reply?->ekstre_path)
                    ->action(function ($record) {
                        $path = $record->reply->ekstre_path;
                        if (str_contains($path, '..')) {
                            \Filament\Notifications\Notification::make()->title('Geçersiz yol')->danger()->send();
                            return;
                        }
                        $fullPath = storage_path('app/public/' . $path);
                        if (!file_exists($fullPath)) {
                            \Filament\Notifications\Notification::make()->title('Dosya bulunamadı')->danger()->send();
                            return;
                        }
                        return response()->download($fullPath, basename($path));
                    }),
                Tables\Actions\Action::make('downloadEImzaliForm')
                    ->label('E-İmzalı Form')
                    ->icon('heroicon-o-document')
                    ->color('gray')
                    ->visible(fn ($record) => $record->reply?->e_imzali_form_path)
                    ->action(function ($record) {
                        $path = $record->reply->e_imzali_form_path;
                        if (str_contains($path, '..')) {
                            \Filament\Notifications\Notification::make()->title('Geçersiz yol')->danger()->send();
                            return;
                        }
                        $fullPath = storage_path('app/public/' . $path);
                        if (!file_exists($fullPath)) {
                            \Filament\Notifications\Notification::make()->title('Dosya bulunamadı')->danger()->send();
                            return;
                        }
                        return response()->download($fullPath, basename($path));
                    }),
                Tables\Actions\Action::make('generateCariGeriDonusPdf')
                    ->label(fn ($record) => $record->reply?->pdf_path ? 'PDF Yenile' : 'PDF Oluştur')
                    ->icon('heroicon-o-document-plus')
                    ->color('warning')
                    ->visible(fn ($record) => $record->reply)
                    ->requiresConfirmation()
                    ->modalHeading(fn ($record) => $record->reply?->pdf_path ? 'PDF Yenile' : 'PDF Oluştur')
                    ->modalDescription('Cari geri dönüş PDF oluşturulacak/yenilenecek. LibreOffice kurulu olmalıdır.')
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
                Tables\Actions\Action::make('tekrarMailGonder')
                    ->label('Tekrar Mail Gönder')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('primary')
                    ->visible(fn ($record) => $record->reply?->cevap === 'mutabık_değiliz' && $record->email)
                    ->requiresConfirmation()
                    ->modalHeading('Tekrar Mail Gönder')
                    ->modalDescription('Karşılık düzeltildikten sonra tekrar mail gönderilecek. Mevcut cevap silinecek, karşı taraf yeni link ile tekrar cevap verebilecek.')
                    ->action(function ($record) {
                        $record->reply?->delete();
                        $record->update([
                            'reply_status' => 'pending',
                            'reply_received_at' => null,
                        ]);
                        if (empty($record->token)) {
                            $record->update(['token' => CariMutabakatItem::generateToken()]);
                        }
                        SendCariMutabakatMailJob::dispatch($record->fresh());
                        Notification::make()
                            ->title('Mail kuyruğa alındı')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('downloadCariGeriDonusPdf')
                    ->label('Cari Geri Dönüş PDF')
                    ->icon('heroicon-o-document-text')
                    ->color('success')
                    ->visible(fn ($record) => $record->reply?->pdf_path)
                    ->action(function ($record) {
                        $path = $record->reply->pdf_path;
                        if (str_contains($path, '..')) {
                            \Filament\Notifications\Notification::make()->title('Geçersiz yol')->danger()->send();
                            return;
                        }
                        $fullPath = \Illuminate\Support\Facades\Storage::disk('local')->path($path);
                        if (!file_exists($fullPath)) {
                            \Filament\Notifications\Notification::make()->title('PDF bulunamadı')->danger()->send();
                            return;
                        }
                        $filename = 'Cari_Geri_Donus_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $record->unvan ?? 'item') . '.pdf';
                        return response()->download($fullPath, $filename);
                    }),
                Tables\Actions\Action::make('viewRequest')
                    ->label('Talep')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => route('filament.mutabakat.resources.cari-mutabakat-requests.view', ['record' => $record->request_id])),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCariMutabakatItems::route('/'),
            'edit' => Pages\EditCariMutabakatItem::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
