<?php

namespace App\Filament\Widgets\MutabakatDashboard;

use App\Models\ReconciliationBank;
use App\Models\ReconciliationRequest;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MutabakatStatsOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $currentYear = now()->year;
        
        // Toplam Mutabakat Talebi
        $totalRequests = ReconciliationRequest::whereYear('created_at', $currentYear)->count();
        
        // Toplam Banka Kaydı
        $totalBanks = ReconciliationBank::whereHas('request', function ($query) use ($currentYear) {
            $query->whereYear('created_at', $currentYear);
        })->count();
        
        // Banka Durumları
        $pending = ReconciliationBank::whereHas('request', function ($query) use ($currentYear) {
            $query->whereYear('created_at', $currentYear);
        })->where('reply_status', 'pending')->count();

        $received = ReconciliationBank::whereHas('request', function ($query) use ($currentYear) {
            $query->whereYear('created_at', $currentYear);
        })->where('reply_status', 'received')->count();

        $completed = ReconciliationBank::whereHas('request', function ($query) use ($currentYear) {
            $query->whereYear('created_at', $currentYear);
        })->where('reply_status', 'completed')->count();
        
        // Talep Durumları
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

        $stats = [
            Stat::make('Toplam Mutabakat Talebi', $totalRequests)
                ->description('Bu yıl')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('primary'),
                
            Stat::make('Toplam Banka Kaydı', $totalBanks)
                ->description('Bu yıl')
                ->descriptionIcon('heroicon-m-building-library')
                ->color('info'),
                
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

        // Talep durumlarını ekle (sadece 0'dan büyük olanlar)
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


