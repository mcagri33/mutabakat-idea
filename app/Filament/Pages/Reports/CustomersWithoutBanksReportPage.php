<?php

namespace App\Filament\Pages\Reports;

use App\Services\MutabakatReportService;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class CustomersWithoutBanksReportPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?string $navigationLabel = 'Bankası Eklenmemiş Firmalar';
    protected static ?string $title = 'Bankası Eklenmemiş Firmalar';
    protected static ?string $navigationGroup = 'Raporlar';
    protected static ?int $navigationSort = 2;
    protected static string $view = 'filament.pages.reports.customers-without-banks-report-page';

    public Collection $customersWithoutBanks;

    public function mount(MutabakatReportService $reportService): void
    {
        $this->customersWithoutBanks = $reportService->getCustomersWithoutBanks();
    }

    public static function getNavigationSort(): ?int
    {
        return 2;
    }
}
