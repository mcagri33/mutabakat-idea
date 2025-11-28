<?php

namespace App\Filament\Resources\CustomerBankResource\Pages;

use App\Filament\Resources\CustomerBankResource;
use App\Models\Customer;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
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
            Actions\CreateAction::make(),
        ];
    }
}
