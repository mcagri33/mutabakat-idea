<?php

namespace App\Filament\Resources\CustomerBankResource\Pages;

use App\Filament\Resources\CustomerBankResource;
use App\Filament\Resources\CustomerBankResource\Imports\CustomerBankImport;
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
                    
                    // CSV dosyası oluştur (Excel'de açılabilir)
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
                }),
            Actions\ImportAction::make()
                ->label('Excel İçe Aktar')
                ->icon('heroicon-o-arrow-down-tray')
                ->importer(CustomerBankImport::class)
                ->color('success')
                ->form([
                    \Filament\Forms\Components\FileUpload::make('file')
                        ->label('Dosya')
                        ->acceptedFileTypes([
                            'text/csv',
                            'text/plain',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // .xlsx
                            'application/vnd.ms-excel', // .xls
                        ])
                        ->required()
                        ->helperText('CSV, TXT veya Excel (.xlsx, .xls) dosyası yükleyebilirsiniz.'),
                ]),
            Actions\CreateAction::make(),
        ];
    }
}
