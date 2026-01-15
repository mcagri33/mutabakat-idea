<?php

namespace App\Exports;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Font;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerBankExport
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function export(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Firma Bankaları');

        // Başlıklar
        $headings = [
            'ID',
            'Firma Adı',
            'Banka Adı',
            'Şube Adı',
            'Yetkili / Masa Memuru',
            'E-posta',
            'Telefon',
            'Aktif Durumu',
            'Oluşturulma Tarihi',
        ];

        // Başlıkları yaz
        $sheet->fromArray([$headings], null, 'A1');

        // Başlık stilini ayarla
        $headerStyle = [
            'font' => [
                'bold' => true,
                'size' => 12,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E3F2FD'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ];
        $sheet->getStyle('A1:I1')->applyFromArray($headerStyle);

        // Verileri yaz
        $row = 2;

        foreach ($this->data as $bank) {
            $sheet->setCellValue('A' . $row, $bank->id);
            $sheet->setCellValue('B' . $row, $bank->customer->name ?? '-');
            $sheet->setCellValue('C' . $row, $bank->bank_name ?? '-');
            $sheet->setCellValue('D' . $row, $bank->branch_name ?? '-');
            $sheet->setCellValue('E' . $row, $bank->officer_name ?? '-');
            $sheet->setCellValue('F' . $row, $bank->officer_email ?? '-');
            $sheet->setCellValue('G' . $row, $bank->officer_phone ?? '-');
            $sheet->setCellValue('H' . $row, $bank->is_active ? 'Aktif' : 'Pasif');
            $sheet->setCellValue('I' . $row, $bank->created_at ? $bank->created_at->format('d.m.Y H:i') : '-');
            $row++;
        }

        // Kolon genişliklerini ayarla
        $sheet->getColumnDimension('A')->setWidth(10);
        $sheet->getColumnDimension('B')->setWidth(30);
        $sheet->getColumnDimension('C')->setWidth(25);
        $sheet->getColumnDimension('D')->setWidth(20);
        $sheet->getColumnDimension('E')->setWidth(25);
        $sheet->getColumnDimension('F')->setWidth(30);
        $sheet->getColumnDimension('G')->setWidth(20);
        $sheet->getColumnDimension('H')->setWidth(15);
        $sheet->getColumnDimension('I')->setWidth(20);

        // Aktif durumu için renkli hücreler
        $lastRow = $row - 1;
        for ($i = 2; $i <= $lastRow; $i++) {
            $activeValue = $sheet->getCell('H' . $i)->getValue();
            if ($activeValue === 'Aktif') {
                $sheet->getStyle('H' . $i)->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'C8E6C9'],
                    ],
                ]);
            } else {
                $sheet->getStyle('H' . $i)->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'FFCDD2'],
                    ],
                ]);
            }
        }

        // Dosya adı
        $filename = 'firma_bankalari_' . now()->format('Y-m-d_His') . '.xlsx';

        // StreamedResponse oluştur
        $writer = new Xlsx($spreadsheet);
        
        return new StreamedResponse(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'max-age=0',
        ]);
    }
}
