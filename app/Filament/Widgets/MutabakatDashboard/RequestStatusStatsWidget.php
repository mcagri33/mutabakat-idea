<?php

namespace App\Filament\Widgets\MutabakatDashboard;

use App\Models\ReconciliationRequest;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RequestStatusStatsWidget extends BaseWidget
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
        
        $statuses = ReconciliationRequest::whereYear('created_at', $currentYear)
            ->selectRaw('status, COUNT(*) as count')
            ->groupByRaw('status')
            ->pluck('count', 'status');

        $labels = [
            'pending' => 'Beklemede',
            'mail_sent' => 'Mail Gönderildi',
            'partially' => 'Kısmi Dönüş',
            'received' => 'Tam Dönüş',
            'completed' => 'Tamamlandı',
            'failed' => 'Hata',
        ];

        $colors = [
            'pending' => 'secondary',
            'mail_sent' => 'primary',
            'partially' => 'warning',
            'received' => 'info',
            'completed' => 'success',
            'failed' => 'danger',
        ];

        $stats = [];
        foreach ($labels as $key => $label) {
            $count = $statuses->get($key, 0);
            if ($count > 0) {
                $stats[] = Stat::make($label, $count)
                    ->color($colors[$key] ?? 'gray');
            }
        }

        return $stats;
    }
}
