<?php

namespace App\Exports;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CariMutabakatExport
{
    protected $items;

    public function __construct($items)
    {
        $this->items = $items;
    }

    public function export(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Cari Mutabakat İşlemleri');

        $headings = [
            'Firma',
            'Yıl',
            'Ay',
            'Cari Kodu',
            'Ünvan',
            'E-Posta',
            'Tarih',
            'B/A',
            'Bakiye',
            'PB',
            'Durum (Mutabıkız/Değiliz)',
            'Mail Durumu',
            'Cevap Durumu',
            'Ekstre',
            'E-İmzalı Form',
            'Cevaplayan Ünvan',
            'Açıklama',
        ];

        $sheet->fromArray([$headings], null, 'A1');

        $headerStyle = [
            'font' => ['bold' => true, 'size' => 11],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E3F2FD'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ];
        $sheet->getStyle('A1:Q1')->applyFromArray($headerStyle);

        $monthNames = [
            1 => 'Ocak', 2 => 'Şubat', 3 => 'Mart', 4 => 'Nisan',
            5 => 'Mayıs', 6 => 'Haziran', 7 => 'Temmuz', 8 => 'Ağustos',
            9 => 'Eylül', 10 => 'Ekim', 11 => 'Kasım', 12 => 'Aralık',
        ];

        $row = 2;
        foreach ($this->items as $item) {
            $request = $item->request;
            $reply = $item->reply;

            $sheet->setCellValue('A' . $row, $request->customer->name ?? '-');
            $sheet->setCellValue('B' . $row, $request->year ?? '-');
            $sheet->setCellValue('C' . $row, $request->month ? ($monthNames[$request->month] ?? $request->month) : '-');
            $sheet->setCellValue('D' . $row, $item->cari_kodu ?? '-');
            $sheet->setCellValue('E' . $row, $item->unvan ?? '-');
            $sheet->setCellValue('F' . $row, $item->email ?? '-');
            $sheet->setCellValue('G' . $row, $item->tarih ? $item->tarih->format('d.m.Y') : '-');
            $sheet->setCellValue('H' . $row, $item->bakiye_tipi ?? '-');
            $sheet->setCellValue('I' . $row, $item->bakiye ?? 0);
            $sheet->setCellValue('J' . $row, $item->pb ?? 'TL');
            $sheet->setCellValue('K' . $row, $reply ? match ($reply->cevap) {
                'mutabıkız' => 'Mutabıkız',
                'mutabık_değiliz' => 'Mutabık Değiliz',
                default => '-',
            } : '-');
            $sheet->setCellValue('L' . $row, match ($item->mail_status) {
                'pending' => 'Beklemede',
                'sent' => 'Gönderildi',
                'failed' => 'Hata',
                default => '-',
            });
            $sheet->setCellValue('M' . $row, match ($item->reply_status) {
                'pending' => 'Beklemede',
                'received' => 'Geldi',
                'completed' => 'Tamamlandı',
                default => '-',
            });
            $sheet->setCellValue('N' . $row, $reply && $reply->ekstre_path ? 'Var' : '-');
            $sheet->setCellValue('O' . $row, $reply && $reply->e_imzali_form_path ? 'Var' : '-');
            $sheet->setCellValue('P' . $row, $reply?->cevaplayan_unvan ?? '-');
            $sheet->setCellValue('Q' . $row, $reply?->aciklama ?? '-');
            $row++;
        }

        foreach (range('A', 'Q') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'cari_mutabakat_' . now()->format('Y-m-d_His') . '.xlsx';

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
