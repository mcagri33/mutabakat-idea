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
        $sheet->setTitle('Firma Bazlı Mail Raporu');

        $headings = [
            'Firma',
            'Yıl',
            'Gönderildi',
            'Bankadan Cevap Geldi',
            'Bankadan Cevap Bekliyor',
            'Durum / Özet',
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
            $sent = $r['sent_count'] ?? 0;
            $manual = $r['manual_count'] ?? 0;
            $sentText = '-';
            if ($sent > 0 && $manual > 0) {
                $sentText = $sent . ' banka + ' . $manual . ' manuel';
            } elseif ($sent > 0) {
                $sentText = $sent . ' banka';
            } elseif ($manual > 0) {
                $sentText = $manual . ' manuel';
            }

            $replyReceived = $r['reply_received_count'] ?? 0;
            $replyPending = $r['reply_pending_count'] ?? 0;

            $sheet->setCellValue('A' . $row, $r['customer_name'] ?? '-');
            $sheet->setCellValue('B' . $row, $r['year'] ?? '-');
            $sheet->setCellValue('C' . $row, $sentText);
            $sheet->setCellValue('D' . $row, $replyReceived > 0 ? $replyReceived . ' banka' : '-');
            $sheet->setCellValue('E' . $row, $replyPending > 0 ? $replyPending . ' banka' : '-');
            $sheet->setCellValue('F' . $row, $r['summary'] ?? '-');
            $row++;
        }

        $sheet->getColumnDimension('A')->setWidth(30);
        $sheet->getColumnDimension('B')->setWidth(8);
        $sheet->getColumnDimension('C')->setWidth(25);
        $sheet->getColumnDimension('D')->setWidth(22);
        $sheet->getColumnDimension('E')->setWidth(24);
        $sheet->getColumnDimension('F')->setWidth(50);

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
