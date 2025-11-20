<?php

namespace App\Filament\Widgets\MutabakatDashboard;

use App\Models\ReconciliationBank;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class BankStatusStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $currentYear = now()->year;
        
        $pending = ReconciliationBank::whereHas('request', function ($query) use ($currentYear) {
            $query->whereYear('created_at', $currentYear);
        })->where('reply_status', 'pending')->count();

        $received = ReconciliationBank::whereHas('request', function ($query) use ($currentYear) {
            $query->whereYear('created_at', $currentYear);
        })->where('reply_status', 'received')->count();

        $completed = ReconciliationBank::whereHas('request', function ($query) use ($currentYear) {
            $query->whereYear('created_at', $currentYear);
        })->where('reply_status', 'completed')->count();

        return [
            Stat::make('Bekleyen Banka Sayısı', $pending)
                ->description('Cevap bekleniyor')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Cevap Gelen Banka Sayısı', $received)
                ->description('Belge alındı')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Tamamlanan Banka Sayısı', $completed)
                ->description('İşlem tamamlandı')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('primary'),
        ];
    }
}

