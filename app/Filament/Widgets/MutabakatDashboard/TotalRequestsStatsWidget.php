<?php

namespace App\Filament\Widgets\MutabakatDashboard;

use App\Models\ReconciliationRequest;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TotalRequestsStatsWidget extends BaseWidget
{
    protected static bool $isLazy = true;
    
    // Bu widget gizli - MutabakatStatsOverviewWidget içinde zaten var
    public static function canView(): bool
    {
        return false;
    }

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

