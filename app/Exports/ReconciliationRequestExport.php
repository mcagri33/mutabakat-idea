<?php

namespace App\Exports;

use App\Models\ReconciliationRequest;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class ReconciliationRequestExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [
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
    }

    public function map($request): array
    {
        $statusLabels = [
            'pending' => 'Beklemede',
            'mail_sent' => 'Mail Gönderildi',
            'partially' => 'Kısmi Dönüş',
            'received' => 'Tam Dönüş',
            'completed' => 'Tamamlandı',
            'failed' => 'Hata',
        ];

        return [
            $request->id,
            $request->customer->name ?? '-',
            $request->year,
            $request->type === 'banka' ? 'Banka' : 'Cari',
            $statusLabels[$request->status] ?? $request->status,
            $request->banks_count ?? 0,
            $request->documents_count ?? 0,
            $request->created_at ? $request->created_at->format('d.m.Y H:i') : '-',
            $request->sent_at ? $request->sent_at->format('d.m.Y H:i') : '-',
            $request->received_at ? $request->received_at->format('d.m.Y H:i') : '-',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E3F2FD'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }

    public function title(): string
    {
        return 'Mutabakat Talepleri';
    }
}

