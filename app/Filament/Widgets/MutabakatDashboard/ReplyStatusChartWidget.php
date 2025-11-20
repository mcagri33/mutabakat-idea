<?php

namespace App\Filament\Widgets\MutabakatDashboard;

use App\Models\ReconciliationBank;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;

class ReplyStatusChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Banka Cevap Durumu Dağılımı';

    protected static ?int $sort = 3;

    protected static ?string $pollingInterval = null;
    
    // Bu widget gizli - dashboard'dan kaldırıldı
    public static function canView(): bool
    {
        return false;
    }

    protected function getData(): array
    {
        $currentYear = now()->year;
        
        // Cache ile optimize edilmiş sorgu - JOIN kullanarak - 5 dakika cache
        return Cache::remember("reply_status_chart_{$currentYear}", 300, function () use ($currentYear) {
            $data = ReconciliationBank::join('reconciliation_requests', 'reconciliation_banks.request_id', '=', 'reconciliation_requests.id')
                ->whereYear('reconciliation_requests.created_at', $currentYear)
                ->selectRaw('reconciliation_banks.reply_status, COUNT(*) as count')
                ->groupBy('reconciliation_banks.reply_status')
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
        });
    }

    protected function getType(): string
    {
        return 'pie';
    }
}
