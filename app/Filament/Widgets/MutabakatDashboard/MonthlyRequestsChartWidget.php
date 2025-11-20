<?php

namespace App\Filament\Widgets\MutabakatDashboard;

use App\Models\ReconciliationRequest;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;

class MonthlyRequestsChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Aylık Mutabakat Talepleri';

    protected static ?int $sort = 2;

    protected static ?string $pollingInterval = null;
    
    // Bu widget gizli - dashboard'dan kaldırıldı
    public static function canView(): bool
    {
        return false;
    }

    protected function getData(): array
    {
        $currentYear = now()->year;
        
        // Cache ile optimize edilmiş sorgu - 5 dakika cache
        return Cache::remember("monthly_requests_chart_{$currentYear}", 300, function () use ($currentYear) {
            $data = ReconciliationRequest::whereYear('created_at', $currentYear)
                ->selectRaw('MONTH(created_at) as month, COUNT(*) as count')
                ->groupByRaw('MONTH(created_at)')
                ->orderBy('month')
                ->pluck('count', 'month');

            $months = [
                1 => 'Ocak', 2 => 'Şubat', 3 => 'Mart', 4 => 'Nisan',
                5 => 'Mayıs', 6 => 'Haziran', 7 => 'Temmuz', 8 => 'Ağustos',
                9 => 'Eylül', 10 => 'Ekim', 11 => 'Kasım', 12 => 'Aralık',
            ];

            $labels = [];
            $values = [];

            for ($i = 1; $i <= 12; $i++) {
                $labels[] = $months[$i];
                $values[] = $data->get($i, 0);
            }

            return [
                'datasets' => [
                    [
                        'label' => 'Mutabakat Talepleri',
                        'data' => $values,
                        'backgroundColor' => 'rgba(59, 130, 246, 0.5)',
                        'borderColor' => 'rgba(59, 130, 246, 1)',
                        'borderWidth' => 2,
                    ],
                ],
                'labels' => $labels,
            ];
        });
    }

    protected function getType(): string
    {
        return 'line';
    }
}
