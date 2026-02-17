<?php

namespace App\Filament\Resources\ManualReconciliationEntryResource\Pages;

use App\Filament\Resources\ManualReconciliationEntryResource;
use App\Imports\ManualReconciliationEntryImport;
use App\Models\Customer;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Response;

class ListManualReconciliationEntries extends ListRecords
{
    protected static string $resource = ManualReconciliationEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('downloadTemplate')
                ->label('Örnek Excel İndir')
                ->icon('heroicon-o-document-arrow-down')
                ->color('info')
                ->action(function () {
                    $customers = Customer::where('is_active', true)
                        ->orderBy('name')
                        ->get();

                    if (class_exists(\PhpOffice\PhpSpreadsheet\Spreadsheet::class)) {
                        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
                        $sheet = $spreadsheet->getActiveSheet();

                        $headers = [
                            'Firma Adı',
                            'Banka Adı',
                            'Şube',
                            'Yıl',
                            'Talep Tarihi',
                            'Banka Dönüş Tarihi',
                            'Not',
                        ];
                        $sheet->fromArray($headers, null, 'A1');
                        $sheet->getStyle('A1:G1')->getFont()->setBold(true);

                        $row = 2;
                        $currentYear = now()->year;
                        foreach ($customers as $customer) {
                            $sheet->setCellValue('A' . $row, $customer->name);
                            $sheet->setCellValue('B' . $row, '');
                            $sheet->setCellValue('C' . $row, '');
                            $sheet->setCellValue('D' . $row, $currentYear);
                            $sheet->setCellValue('E' . $row, '');
                            $sheet->setCellValue('F' . $row, '');
                            $sheet->setCellValue('G' . $row, '');
                            $row++;
                        }

                        foreach (range('A', 'G') as $col) {
                            $sheet->getColumnDimension($col)->setAutoSize(true);
                        }

                        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
                        $filename = 'manuel_mutabakat_ornek_' . date('Y-m-d') . '.xlsx';
                        $tempFile = tempnam(sys_get_temp_dir(), 'excel_');
                        $writer->save($tempFile);

                        return Response::download($tempFile, $filename, [
                            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        ])->deleteFileAfterSend(true);
                    }

                    $filename = 'manuel_mutabakat_ornek_' . date('Y-m-d') . '.csv';
                    $headers = [
                        'Content-Type' => 'text/csv; charset=UTF-8',
                        'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                    ];

                    $callback = function () use ($customers) {
                        $file = fopen('php://output', 'w');
                        fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
                        fputcsv($file, [
                            'Firma Adı',
                            'Banka Adı',
                            'Şube',
                            'Yıl',
                            'Talep Tarihi',
                            'Banka Dönüş Tarihi',
                            'Not',
                        ], ';');
                        $currentYear = now()->year;
                        foreach ($customers as $customer) {
                            fputcsv($file, [
                                $customer->name,
                                '',
                                '',
                                $currentYear,
                                '',
                                '',
                                '',
                            ], ';');
                        }
                        fclose($file);
                    };

                    return Response::stream($callback, 200, $headers);
                }),
            Actions\Action::make('import')
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
                        ->helperText('Excel dosyasını seçin (.xlsx veya .xls formatında). Sütunlar: Firma Adı, Banka Adı, Şube, Yıl, Talep Tarihi, Banka Dönüş Tarihi, Not')
                        ->disk('local')
                        ->directory('imports')
                        ->visibility('private'),
                ])
                ->action(function (array $data) {
                    $filePath = storage_path('app/' . $data['file']);

                    if (! file_exists($filePath)) {
                        Notification::make()
                            ->title('Hata')
                            ->body('Dosya bulunamadı.')
                            ->danger()
                            ->send();

                        return;
                    }

                    $import = new ManualReconciliationEntryImport();
                    $results = $import->import($filePath);

                    @unlink($filePath);

                    $message = sprintf(
                        'İçe aktarma tamamlandı: %d yeni kayıt eklendi, %d kayıt atlandı',
                        $results['success'],
                        $results['skipped']
                    );

                    if (! empty($results['errors'])) {
                        $errorCount = count($results['errors']);
                        $errorMessage = "\n\nHatalar:\n" . implode("\n", array_slice($results['errors'], 0, 10));
                        if ($errorCount > 10) {
                            $errorMessage .= "\n... ve " . ($errorCount - 10) . ' hata daha';
                        }
                        $message .= $errorMessage;
                    }

                    Notification::make()
                        ->title('İçe Aktarma Sonucu')
                        ->body($message)
                        ->success()
                        ->send();
                })
                ->modalHeading('Excel Dosyası İçe Aktar')
                ->modalDescription('Excel dosyasındaki manuel mutabakat bilgilerini sisteme aktarın. Her satır için yeni bir kayıt oluşturulur.'),
            Actions\CreateAction::make()
                ->label('Yeni manuel giriş'),
        ];
    }
}
