<?php

namespace App\Filament\Widgets\MutabakatDashboard;

use App\Models\ReconciliationRequest;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TotalRequestsStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $currentYear = now()->year;
        
        $totalRequests = ReconciliationRequest::whereYear('created_at', $currentYear)->count();

        return [
            Stat::make('Toplam Mutabakat Talebi', $totalRequests)
                ->description('Bu yıl')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('primary'),
        ];
    }
}

