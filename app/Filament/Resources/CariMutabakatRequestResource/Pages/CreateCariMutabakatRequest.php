<?php

namespace App\Filament\Resources\CariMutabakatRequestResource\Pages;

use App\Filament\Resources\CariMutabakatRequestResource;
use App\Imports\CariMutabakatItemImport;
use App\Jobs\SendCariMutabakatMailJob;
use App\Models\CariMutabakatItem;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;

class CreateCariMutabakatRequest extends CreateRecord
{
    protected static string $resource = CariMutabakatRequestResource::class;

    protected array $pendingItems = [];

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('downloadTemplate')
                ->label('Örnek Excel İndir')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->action(function () {
                    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
                    $sheet = $spreadsheet->getActiveSheet();
                    $headers = CariMutabakatItemImport::getTemplateHeaders();
                    $sheet->fromArray($headers, null, 'A1');
                    $sheet->getStyle('A1:L1')->getFont()->setBold(true);
                    foreach (range('A', 'L') as $col) {
                        $sheet->getColumnDimension($col)->setAutoSize(true);
                    }
                    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
                    $filename = 'cari_mutabakat_ornek_' . date('Y-m-d') . '.xlsx';
                    $tempFile = tempnam(sys_get_temp_dir(), 'excel_');
                    $writer->save($tempFile);
                    return Response::download($tempFile, $filename, [
                        'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    ])->deleteFileAfterSend(true);
                }),
            Actions\Action::make('importExcel')
                ->label('Excel\'den İçe Aktar')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->form([
                    Forms\Components\FileUpload::make('file')
                        ->label('Excel Dosyası')
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-excel',
                        ])
                        ->required()
                        ->helperText('Excel dosyasını seçin (.xlsx veya .xls). Sütunlar: Hesap Tipi, Referans, Cari Kodu, Ünvan, E-Posta, CC E-Posta, Tel No, B/A, Bakiye, PB, Karşılığı, Karşılığı PB')
                        ->disk('local')
                        ->directory('imports')
                        ->visibility('private'),
                ])
                ->action(function (array $data) {
                    $filePath = Storage::path($data['file']);
                    if (! $filePath || ! file_exists($filePath)) {
                        Notification::make()
                            ->title('Hata')
                            ->body('Dosya bulunamadı.')
                            ->danger()
                            ->send();
                        return;
                    }
                    try {
                        $import = new CariMutabakatItemImport();
                        $parsedItems = $import->parse($filePath);
                        @unlink($filePath);
                        if (empty($parsedItems)) {
                            Notification::make()
                                ->title('Uyarı')
                                ->body('Excel dosyasında geçerli satır bulunamadı. Cari Kodu, Ünvan ve E-Posta zorunludur.')
                                ->warning()
                                ->send();
                            return;
                        }
                        $current = $this->form->getState()['items'] ?? [];
                        $merged = array_merge($current, $parsedItems);
                        $this->form->fill(['items' => $merged]);
                        Notification::make()
                            ->title('Excel içe aktarıldı')
                            ->body(count($parsedItems) . ' satır eklendi.')
                            ->success()
                            ->send();
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('Hata')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->modalHeading('Excel\'den İçe Aktar')
                ->modalDescription('Excel dosyasındaki alıcı/satıcı listesini forma aktarır. Mevcut satırlar korunur, yeni satırlar eklenir.'),
        ];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->pendingItems = $data['items'] ?? [];
        unset($data['items']);
        return $data;
    }

    protected function afterCreate(): void
    {
        $mailsQueued = 0;

        foreach ($this->pendingItems as $item) {
            $email = trim($item['email'] ?? '');
            $hasValidEmail = !empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL);

            $cariItem = CariMutabakatItem::create([
                'request_id' => $this->record->id,
                'hesap_tipi' => $item['hesap_tipi'] ?? null,
                'referans' => $item['referans'] ?? null,
                'cari_kodu' => $item['cari_kodu'] ?? '',
                'unvan' => $item['unvan'] ?? '',
                'email' => $email,
                'cc_email' => $item['cc_email'] ?? null,
                'tel_no' => $item['tel_no'] ?? null,
                'tarih' => now(),
                'bakiye_tipi' => $item['bakiye_tipi'] ?? 'Borç',
                'bakiye' => $item['bakiye'] ?? 0,
                'pb' => $item['pb'] ?? 'TL',
                'karsiligi' => $item['karsiligi'] ?? null,
                'karsiligi_pb' => $item['karsiligi_pb'] ?? 'TRY',
                'token' => $hasValidEmail ? CariMutabakatItem::generateToken() : null,
            ]);

            if ($hasValidEmail) {
                SendCariMutabakatMailJob::dispatch($cariItem);
                $mailsQueued++;
            }
        }

        if ($mailsQueued > 0) {
            Notification::make()
                ->title('Cari mutabakat talebi oluşturuldu')
                ->body("{$mailsQueued} adrese e-posta gönderimi kuyruğa alındı.")
                ->success()
                ->send();
        }
    }
}
