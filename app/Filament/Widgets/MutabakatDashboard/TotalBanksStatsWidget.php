<?php

namespace App\Filament\Widgets\MutabakatDashboard;

use App\Models\ReconciliationBank;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TotalBanksStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $currentYear = now()->year;
        
        $totalBanks = ReconciliationBank::whereHas('request', function ($query) use ($currentYear) {
            $query->whereYear('created_at', $currentYear);
        })->count();

        return [
            Stat::make('Toplam Banka Kaydı', $totalBanks)
                ->description('Bu yıl')
                ->descriptionIcon('heroicon-m-building-library')
                ->color('info'),
        ];
    }
}

