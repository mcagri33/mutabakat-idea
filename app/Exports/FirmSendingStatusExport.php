<?php

namespace App\Exports;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FirmSendingStatusExport
{
    /** @var array<int, array<string, mixed>> */
    protected $rows;

    /** @param array<int, array<string, mixed>> $rows */
    public function __construct(array $rows)
    {
        $this->rows = $rows;
    }

    public function export(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Firma Gönderim Durumu');

        $statusLabels = [
            'hepsi_gonderildi' => 'Hepsi gönderildi',
            'manuel_ile' => 'Manuel ile',
            'kismen' => 'Kısmen',
            'gonderilmedi' => 'Gönderilmedi',
            'banka_eklenmemis' => 'Banka eklenmemiş',
        ];

        $headings = [
            'Firma',
            'Yıl',
            'Banka Sayısı',
            'Sistemden Gönderilen',
            'Manuel Giriş',
            'Durum',
        ];
        $sheet->fromArray([$headings], null, 'A1');

        $headerStyle = [
            'font' => ['bold' => true, 'size' => 12],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E8F5E9'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ];
        $sheet->getStyle('A1:F1')->applyFromArray($headerStyle);

        $row = 2;
        foreach ($this->rows as $r) {
            $status = $r['status'] ?? 'gonderilmedi';
            $statusLabel = $statusLabels[$status] ?? $status;
            $sheet->setCellValue('A' . $row, $r['customer_name'] ?? '-');
            $sheet->setCellValue('B' . $row, $r['year'] ?? '-');
            $sheet->setCellValue('C' . $row, (int) ($r['bank_count'] ?? 0));
            $sheet->setCellValue('D' . $row, (int) ($r['sent_count'] ?? 0));
            $sheet->setCellValue('E' . $row, (int) ($r['manual_count'] ?? 0));
            $sheet->setCellValue('F' . $row, $statusLabel);
            $row++;
        }

        $sheet->getColumnDimension('A')->setWidth(35);
        $sheet->getColumnDimension('B')->setWidth(8);
        $sheet->getColumnDimension('C')->setWidth(14);
        $sheet->getColumnDimension('D')->setWidth(22);
        $sheet->getColumnDimension('E')->setWidth(14);
        $sheet->getColumnDimension('F')->setWidth(22);

        $filename = 'firma_gonderim_durumu_' . now()->format('Y-m-d_His') . '.xlsx';
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
