<?php

namespace App\Filament\Widgets\MutabakatDashboard;

use App\Models\CariMutabakatRequest;
use App\Models\CariMutabakatItem;
use App\Models\ReconciliationBank;
use App\Models\ReconciliationRequest;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class MutabakatStatsOverviewWidget extends BaseWidget
{
    // 30 saniyede bir otomatik yenileme
    protected static ?string $pollingInterval = '30s';
    
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $currentYear = now()->year;
        
        // Cache süresini 30 saniyeye düşür (daha hızlı güncelleme)
        return Cache::remember("mutabakat_stats_{$currentYear}", 30, function () use ($currentYear) {
            // Tek sorguda tüm banka istatistiklerini al (JOIN ile optimize)
            $bankStats = ReconciliationBank::join('reconciliation_requests', 'reconciliation_banks.request_id', '=', 'reconciliation_requests.id')
                ->whereYear('reconciliation_requests.created_at', $currentYear)
                ->selectRaw('
                    COUNT(*) as total_banks,
                    SUM(CASE WHEN reconciliation_banks.reply_status = "pending" THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN reconciliation_banks.reply_status = "received" THEN 1 ELSE 0 END) as received,
                    SUM(CASE WHEN reconciliation_banks.reply_status = "completed" THEN 1 ELSE 0 END) as completed
                ')
                ->first();
            
            // Toplam Mutabakat Talebi (Banka)
            $totalRequests = ReconciliationRequest::whereYear('created_at', $currentYear)->count();

            // Cari Mutabakat istatistikleri
            $cariTotal = CariMutabakatRequest::whereYear('created_at', $currentYear)->count();
            $cariItemsPending = CariMutabakatItem::whereHas('request', fn ($q) => $q->whereYear('created_at', $currentYear))
                ->where('reply_status', 'pending')->count();
            $cariItemsReceived = CariMutabakatItem::whereHas('request', fn ($q) => $q->whereYear('created_at', $currentYear))
                ->whereIn('reply_status', ['received', 'completed'])->count();
            
            // Talep Durumları
            $statuses = ReconciliationRequest::whereYear('created_at', $currentYear)
                ->selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
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

            // Stat'ları mantıklı sıraya koy: Önce toplamlar, sonra banka durumları, en son talep durumları
            $stats = [
                // 1. GENEL TOPLAMLAR
                Stat::make('Toplam Mutabakat Talebi', $totalRequests)
                    ->description('Bu yıl')
                    ->descriptionIcon('heroicon-m-arrow-trending-up')
                    ->color('primary'),
                    
                Stat::make('Toplam Banka Kaydı', $bankStats->total_banks ?? 0)
                    ->description('Bu yıl')
                    ->descriptionIcon('heroicon-m-building-library')
                    ->color('info'),
                
                // 2. BANKA DURUMLARI
                Stat::make('Bekleyen Banka Sayısı', $bankStats->pending ?? 0)
                    ->description('Cevap bekleniyor')
                    ->descriptionIcon('heroicon-m-clock')
                    ->color('warning'),

                Stat::make('Cevap Gelen Banka Sayısı', $bankStats->received ?? 0)
                    ->description('Belge alındı')
                    ->descriptionIcon('heroicon-m-check-circle')
                    ->color('success'),

                Stat::make('Tamamlanan Banka Sayısı', $bankStats->completed ?? 0)
                    ->description('İşlem tamamlandı')
                    ->descriptionIcon('heroicon-m-check-badge')
                    ->color('primary'),

                // CARİ MUTABAKAT
                Stat::make('Cari Mutabakat Talebi', $cariTotal)
                    ->description('Bu yıl')
                    ->descriptionIcon('heroicon-m-users')
                    ->color('info')
                    ->url(route('filament.mutabakat.resources.cari-mutabakat-requests.index')),

                Stat::make('Cari Bekleyen', $cariItemsPending)
                    ->description('Cevap bekleniyor')
                    ->descriptionIcon('heroicon-m-clock')
                    ->color('warning'),

                Stat::make('Cari Cevap Gelen', $cariItemsReceived)
                    ->description('Alıcı/Satıcı cevabı')
                    ->descriptionIcon('heroicon-m-check-circle')
                    ->color('success'),
            ];

            // 3. TALEP DURUMLARI (sıralı - sadece 0'dan büyük olanlar)
            $statusOrder = ['pending', 'mail_sent', 'partially', 'received', 'completed', 'failed'];
            foreach ($statusOrder as $key) {
                if (isset($labels[$key])) {
                    $count = $statuses->get($key, 0);
                    if ($count > 0) {
                        $stats[] = Stat::make($labels[$key], $count)
                            ->color($colors[$key] ?? 'gray');
                    }
                }
            }

            return $stats;
        });
    }
}


