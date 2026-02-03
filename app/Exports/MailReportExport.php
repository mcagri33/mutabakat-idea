<?php

namespace App\Exports;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MailReportExport
{
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
        $sheet->setTitle('Firma Banka Mail Raporu');

        $mailLabels = ['sent' => 'Gönderildi', 'failed' => 'Hata', 'pending' => 'Beklemede'];
        $replyLabels = ['received' => 'Geldi', 'completed' => 'Tamamlandı', 'pending' => 'Beklemede'];

        $headings = [
            'Firma',
            'Banka',
            'Yıl',
            'Gönderim Tarihi',
            'Mail Durumu',
            'Cevap Durumu',
            'Cevap Tarihi',
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
        $sheet->getStyle('A1:G1')->applyFromArray($headerStyle);

        $row = 2;
        foreach ($this->rows as $r) {
            $mailStatus = $r['mail_status'] ?? 'pending';
            $replyStatus = $r['reply_status'] ?? 'pending';
            $sheet->setCellValue('A' . $row, $r['customer_name'] ?? '-');
            $sheet->setCellValue('B' . $row, $r['bank_name'] ?? '-');
            $sheet->setCellValue('C' . $row, $r['year'] ?? '-');
            $sheet->setCellValue('D' . $row, $r['mail_sent_at'] ?? '-');
            $sheet->setCellValue('E' . $row, $mailLabels[$mailStatus] ?? $mailStatus);
            $sheet->setCellValue('F' . $row, $replyLabels[$replyStatus] ?? $replyStatus);
            $sheet->setCellValue('G' . $row, $r['reply_received_at'] ?? '-');
            $row++;
        }

        $sheet->getColumnDimension('A')->setWidth(30);
        $sheet->getColumnDimension('B')->setWidth(25);
        $sheet->getColumnDimension('C')->setWidth(8);
        $sheet->getColumnDimension('D')->setWidth(18);
        $sheet->getColumnDimension('E')->setWidth(14);
        $sheet->getColumnDimension('F')->setWidth(14);
        $sheet->getColumnDimension('G')->setWidth(18);

        $filename = 'firma_banka_mail_raporu_' . now()->format('Y-m-d_His') . '.xlsx';
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
