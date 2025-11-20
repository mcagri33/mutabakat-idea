<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\MutabakatDashboard as MutabakatWidgets;
use Filament\Pages\Page;

class MutabakatDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static string $view = 'filament.pages.mutabakat-dashboard';

    protected static ?string $navigationGroup = 'Mutabakat Yönetimi';

    protected static ?string $navigationLabel = 'Mutabakat Dashboard';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Mutabakat Dashboard';

    public function getHeaderWidgets(): array
    {
        return [
            MutabakatWidgets\MutabakatStatsOverviewWidget::class,
        ];
    }

    public function getFooterWidgets(): array
    {
        return [
            MutabakatWidgets\MonthlyRequestsChartWidget::class,
            MutabakatWidgets\ReplyStatusChartWidget::class,
            MutabakatWidgets\PendingRequestsTableWidget::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int | array
    {
        return 1;
    }

    public function getFooterWidgetsColumns(): int | array
    {
        return [
            'md' => 1,
            'xl' => 3,
        ];
    }
}
