<?php

namespace App\Filament\Pages\Reports;

use App\Services\MutabakatReportService;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class MailReportPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-envelope';
    protected static ?string $navigationLabel = 'Firma – Banka Bazlı Mail Raporu';
    protected static ?string $title = 'Firma – Banka Bazlı Mail Raporu';
    protected static ?string $navigationGroup = 'Raporlar';
    protected static ?int $navigationSort = 3;
    protected static string $view = 'filament.pages.reports.mail-report-page';

    /** @var array<int, array<string, mixed>> */
    public array $mailReportRows = [];

    public function mount(MutabakatReportService $reportService): void
    {
        $this->mailReportRows = $reportService->getMailReportRows();
    }

    public static function getNavigationSort(): ?int
    {
        return 3;
    }
}
