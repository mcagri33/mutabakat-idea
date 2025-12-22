<?php

namespace App\Filament\Resources\CustomerBankResource\Pages;

use App\Filament\Resources\CustomerBankResource;
use App\Models\Customer;
use App\Imports\CustomerBankImport;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Response;

class ListCustomerBanks extends ListRecords
{
    protected static string $resource = CustomerBankResource::class;

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
                    
                    // PhpSpreadsheet ile Excel dosyası oluştur
                    if (class_exists(\PhpOffice\PhpSpreadsheet\Spreadsheet::class)) {
                        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
                        $sheet = $spreadsheet->getActiveSheet();
                        
                        // Başlık satırı
                        $headers = [
                            'Firma Adı',
                            'Banka Adı',
                            'Şube Adı',
                            'Yetkili / Masa Memuru',
                            'E-posta',
                            'Telefon',
                            'Aktif'
                        ];
                        $sheet->fromArray($headers, null, 'A1');
                        
                        // Başlık satırını kalın yap
                        $sheet->getStyle('A1:G1')->getFont()->setBold(true);
                        
                        // Örnek satırlar (her firma için bir satır)
                        $row = 2;
                        foreach ($customers as $customer) {
                            $sheet->setCellValue('A' . $row, $customer->name); // Firma adı dolu
                            $sheet->setCellValue('B' . $row, ''); // Banka Adı - doldurulacak
                            $sheet->setCellValue('C' . $row, ''); // Şube Adı - doldurulacak
                            $sheet->setCellValue('D' . $row, ''); // Yetkili - doldurulacak
                            $sheet->setCellValue('E' . $row, ''); // E-posta - doldurulacak
                            $sheet->setCellValue('F' . $row, ''); // Telefon - doldurulacak
                            $sheet->setCellValue('G' . $row, '1'); // Aktif - varsayılan 1
                            $row++;
                        }
                        
                        // Sütun genişliklerini ayarla
                        foreach (range('A', 'G') as $col) {
                            $sheet->getColumnDimension($col)->setAutoSize(true);
                        }
                        
                        // Excel dosyasını oluştur
                        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
                        $filename = 'firma_bankalari_ornek_' . date('Y-m-d') . '.xlsx';
                        $tempFile = tempnam(sys_get_temp_dir(), 'excel_');
                        $writer->save($tempFile);
                        
                        return Response::download($tempFile, $filename, [
                            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        ])->deleteFileAfterSend(true);
                    } else {
                        // PhpSpreadsheet yoksa CSV oluştur
                        $filename = 'firma_bankalari_ornek_' . date('Y-m-d') . '.csv';
                        $headers = [
                            'Content-Type' => 'text/csv; charset=UTF-8',
                            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                        ];
                        
                        $callback = function() use ($customers) {
                            $file = fopen('php://output', 'w');
                            
                            // BOM ekle (Excel'de Türkçe karakterler için)
                            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
                            
                            // Başlık satırı
                            fputcsv($file, [
                                'Firma Adı',
                                'Banka Adı',
                                'Şube Adı',
                                'Yetkili / Masa Memuru',
                                'E-posta',
                                'Telefon',
                                'Aktif'
                            ], ';');
                            
                            // Örnek satırlar (her firma için bir satır)
                            foreach ($customers as $customer) {
                                fputcsv($file, [
                                    $customer->name, // Firma adı dolu
                                    '', // Banka Adı - doldurulacak
                                    '', // Şube Adı - doldurulacak
                                    '', // Yetkili - doldurulacak
                                    '', // E-posta - doldurulacak
                                    '', // Telefon - doldurulacak
                                    '1' // Aktif - varsayılan 1
                                ], ';');
                            }
                            
                            fclose($file);
                        };
                        
                        return Response::stream($callback, 200, $headers);
                    }
                }),
            Actions\Action::make('import')
                ->label('Excel\'den İçe Aktar')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->form([
                    Forms\Components\FileUpload::make('file')
                        ->label('Excel Dosyası')
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // .xlsx
                            'application/vnd.ms-excel', // .xls
                        ])
                        ->required()
                        ->helperText('Excel dosyasını seçin (.xlsx veya .xls formatında)')
                        ->disk('local')
                        ->directory('imports')
                        ->visibility('private'),
                ])
                ->action(function (array $data) {
                    $filePath = storage_path('app/' . $data['file']);
                    
                    if (!file_exists($filePath)) {
                        Notification::make()
                            ->title('Hata')
                            ->body('Dosya bulunamadı.')
                            ->danger()
                            ->send();
                        return;
                    }

                    $import = new CustomerBankImport();
                    $results = $import->import($filePath);

                    // Dosyayı sil
                    @unlink($filePath);

                    // Sonuçları göster
                    $message = sprintf(
                        'İçe aktarma tamamlandı: %d yeni kayıt, %d güncellendi, %d atlandı',
                        $results['success'],
                        $results['updated'],
                        $results['skipped']
                    );

                    if (!empty($results['errors'])) {
                        $errorCount = count($results['errors']);
                        $errorMessage = "\n\nHatalar:\n" . implode("\n", array_slice($results['errors'], 0, 10));
                        if ($errorCount > 10) {
                            $errorMessage .= "\n... ve " . ($errorCount - 10) . " hata daha";
                        }
                        $message .= $errorMessage;
                    }

                    Notification::make()
                        ->title('İçe Aktarma Sonucu')
                        ->body($message)
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->modalHeading('Excel Dosyası İçe Aktar')
                ->modalDescription('Excel dosyasındaki banka bilgilerini sisteme aktarın. Mevcut kayıtlar e-posta adresine göre güncellenir.'),
            Actions\CreateAction::make(),
        ];
    }
}
