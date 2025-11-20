<?php

namespace App\Filament\Widgets\MutabakatDashboard;

use App\Models\ReconciliationBank;
use Filament\Widgets\ChartWidget;

class ReplyStatusChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Banka Cevap Durumu Dağılımı';

    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $currentYear = now()->year;
        
        $data = ReconciliationBank::whereHas('request', function ($query) use ($currentYear) {
            $query->whereYear('created_at', $currentYear);
        })
        ->selectRaw('reply_status, COUNT(*) as count')
        ->groupByRaw('reply_status')
        ->pluck('count', 'reply_status');

        $labels = [
            'pending' => 'Beklemede',
            'received' => 'Cevap Gelen',
            'completed' => 'Tamamlanan',
        ];

        $chartLabels = [];
        $chartValues = [];
        $colors = [];

        foreach ($labels as $key => $label) {
            $count = $data->get($key, 0);
            if ($count > 0) {
                $chartLabels[] = $label;
                $chartValues[] = $count;
                
                $colorMap = [
                    'pending' => 'rgba(234, 179, 8, 0.8)',   // warning/yellow
                    'received' => 'rgba(34, 197, 94, 0.8)',  // success/green
                    'completed' => 'rgba(59, 130, 246, 0.8)', // primary/blue
                ];
                $colors[] = $colorMap[$key] ?? 'rgba(156, 163, 175, 0.8)';
            }
        }

        return [
            'datasets' => [
                [
                    'data' => $chartValues,
                    'backgroundColor' => $colors,
                    'borderColor' => $colors,
                    'borderWidth' => 1,
                ],
            ],
            'labels' => $chartLabels,
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }
}
