<?php

namespace App\Exports;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Font;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReconciliationRequestExport
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
        $sheet->setTitle('Mutabakat Talepleri');

        // Başlıklar
        $headings = [
            'ID',
            'Firma',
            'Yıl',
            'Tip',
            'Durum',
            'Banka Sayısı',
            'Belge Sayısı',
            'Oluşturulma Tarihi',
            'Mail Gönderim Tarihi',
            'Yanıt Alma Tarihi',
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
        $sheet->getStyle('A1:J1')->applyFromArray($headerStyle);

        // Verileri yaz
        $row = 2;
        $statusLabels = [
            'pending' => 'Beklemede',
            'mail_sent' => 'Mail Gönderildi',
            'partially' => 'Kısmi Dönüş',
            'received' => 'Tam Dönüş',
            'completed' => 'Tamamlandı',
            'failed' => 'Hata',
        ];

        foreach ($this->data as $request) {
            $sheet->setCellValue('A' . $row, $request->id);
            $sheet->setCellValue('B' . $row, $request->customer->name ?? '-');
            $sheet->setCellValue('C' . $row, $request->year);
            $sheet->setCellValue('D' . $row, $request->type === 'banka' ? 'Banka' : 'Cari');
            $sheet->setCellValue('E' . $row, $statusLabels[$request->status] ?? $request->status);
            $sheet->setCellValue('F' . $row, $request->banks_count ?? 0);
            $sheet->setCellValue('G' . $row, $request->documents_count ?? 0);
            $sheet->setCellValue('H' . $row, $request->created_at ? $request->created_at->format('d.m.Y H:i') : '-');
            $sheet->setCellValue('I' . $row, $request->sent_at ? $request->sent_at->format('d.m.Y H:i') : '-');
            $sheet->setCellValue('J' . $row, $request->received_at ? $request->received_at->format('d.m.Y H:i') : '-');
            $row++;
        }

        // Kolon genişliklerini ayarla
        $sheet->getColumnDimension('A')->setWidth(10);
        $sheet->getColumnDimension('B')->setWidth(30);
        $sheet->getColumnDimension('C')->setWidth(10);
        $sheet->getColumnDimension('D')->setWidth(15);
        $sheet->getColumnDimension('E')->setWidth(20);
        $sheet->getColumnDimension('F')->setWidth(15);
        $sheet->getColumnDimension('G')->setWidth(15);
        $sheet->getColumnDimension('H')->setWidth(20);
        $sheet->getColumnDimension('I')->setWidth(20);
        $sheet->getColumnDimension('J')->setWidth(20);

        // Dosya adı
        $filename = 'mutabakat_talepleri_' . now()->format('Y-m-d_His') . '.xlsx';

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

