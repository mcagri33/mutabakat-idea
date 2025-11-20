<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\MutabakatDashboard as MutabakatWidgets;
use Filament\Pages\Page;

class MutabakatDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static string $view = 'filament.pages.mutabakat-dashboard';

    protected static ?string $navigationGroup = null;

    protected static ?string $navigationLabel = null;

    protected static ?int $navigationSort = null;

    protected static ?string $title = 'Mutabakat Dashboard';
    
    // Sayfa gizli - navigation'da görünmeyecek
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
    
    // Lazy loading ile performansı artır
    protected static bool $isLazy = false;

    public function getHeaderWidgets(): array
    {
        return [
            MutabakatWidgets\MutabakatStatsOverviewWidget::class,
        ];
    }

    public function getFooterWidgets(): array
    {
        return [
            MutabakatWidgets\PendingRequestsTableWidget::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int | array
    {
        // Responsive grid: mobilde 1, tablette 2, desktop'ta 4 kolon
        return [
            'default' => 1,
            'sm' => 2,
            'md' => 3,
            'lg' => 4,
            'xl' => 4,
        ];
    }

    public function getFooterWidgetsColumns(): int | array
    {
        return 1;
    }
}
