<?php

namespace App\Filament\Pages\Reports;

use App\Exports\CustomersWithoutBanksExport;
use App\Services\MutabakatReportService;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    public function getHeaderActions(): array
    {
        return [
            Action::make('exportExcel')
                ->label('Excel\'e Aktar')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(fn (): StreamedResponse => (new CustomersWithoutBanksExport($this->customersWithoutBanks))->export()),
        ];
    }

    public static function getNavigationSort(): ?int
    {
        return 2;
    }
}
