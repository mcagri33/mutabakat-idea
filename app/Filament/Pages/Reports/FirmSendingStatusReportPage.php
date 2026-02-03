<?php

namespace App\Filament\Pages\Reports;

use App\Exports\FirmSendingStatusExport;
use App\Services\MutabakatReportService;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FirmSendingStatusReportPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-paper-airplane';
    protected static ?string $navigationLabel = 'Firma Gönderim Durumu';
    protected static ?string $title = 'Firma Gönderim Durumu';
    protected static ?string $navigationGroup = 'Raporlar';
    protected static ?int $navigationSort = 4;
    protected static string $view = 'filament.pages.reports.firm-sending-status-report-page';

    public int $year = 0;
    public int $page = 1;
    public int $perPage = 15;

    /** @var array<int, array<string, mixed>> */
    public array $tableRows = [];
    public int $totalCount = 0;
    public int $currentPage = 1;
    public int $lastPage = 1;
    public int $firstItem = 0;
    public int $lastItem = 0;

    public function mount(MutabakatReportService $reportService): void
    {
        try {
            if ($this->year <= 0) {
                $this->year = (int) now()->year - 1; // Denetim yılı: cari yıldan 1 önce
            }
            $this->filters = ['year' => $this->year];
            $this->form->fill($this->filters);
            $this->loadData($reportService);
        } catch (\Throwable $e) {
            Log::error('FirmSendingStatusReportPage mount hatası', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    protected function getForms(): array
    {
        return ['form'];
    }

    public function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Filtreler')
                    ->schema([
                        Forms\Components\Grid::make(4)
                            ->schema([
                                Select::make('year')
                                    ->label('Yıl')
                                    ->options(function () {
                                        try {
                                            $years = \App\Models\ReconciliationRequest::query()
                                                ->select('year')
                                                ->distinct()
                                                ->orderByDesc('year')
                                                ->pluck('year', 'year');
                                            $arr = $years->isEmpty() ? [] : $years->toArray();
                                            if (empty($arr)) {
                                                $arr[now()->year] = now()->year;
                                            }
                                            return $arr;
                                        } catch (\Throwable $e) {
                                            Log::warning('FirmSendingStatusReportPage: Yıl listesi alınamadı', ['error' => $e->getMessage()]);
                                            return [now()->year => now()->year];
                                        }
                                    })
                                    ->required()
                                    ->default(now()->year - 1),
                            ]),
                        Forms\Components\Actions::make([
                            Forms\Components\Actions\Action::make('filter')
                                ->label('Filtrele')
                                ->icon('heroicon-o-funnel')
                                ->color('primary')
                                ->action(function (MutabakatReportService $reportService) {
                                    $state = $this->form->getState();
                                    $this->year = (int) ($state['year'] ?? now()->year - 1 - 1);
                                    $this->page = 1;
                                    $this->loadData($reportService);
                                }),
                        ])->fullWidth(),
                    ])
                    ->collapsible()
                    ->collapsed(false),
            ])
            ->statePath('filters');
    }

    /** @var array{year?: int} */
    public array $filters = ['year' => null];

    public function loadData(MutabakatReportService $reportService): void
    {
        try {
            $year = $this->year > 0 ? $this->year : (int) now()->year - 1;
            $this->year = $year;
            $perPage = max(1, min(100, $this->perPage));
            $page = max(1, $this->page);
            $paginator = $reportService->getFirmSendingStatusPaginated($year, $perPage, $page);
            $items = $paginator->items();
            $this->tableRows = is_array($items) ? $items : collect($items)->all();
            $this->totalCount = (int) $paginator->total();
            $this->currentPage = (int) $paginator->currentPage();
            $this->lastPage = max(1, (int) $paginator->lastPage());
            $this->firstItem = (int) ($paginator->firstItem() ?? 0);
            $this->lastItem = (int) ($paginator->lastItem() ?? 0);
        } catch (\Throwable $e) {
            Log::error('FirmSendingStatusReportPage loadData hatası', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->tableRows = [];
            $this->totalCount = 0;
            $this->currentPage = 1;
            $this->lastPage = 1;
            $this->firstItem = 0;
            $this->lastItem = 0;
        }
    }

    public function setPage(int $page): void
    {
        $this->page = max(1, $page);
        $this->loadData(app(MutabakatReportService::class));
    }

    public function updatedPerPage(): void
    {
        $this->page = 1;
        $this->loadData(app(MutabakatReportService::class));
    }

    public function getHeaderActions(): array
    {
        return [
            Action::make('exportExcel')
                ->label('Excel\'e Aktar')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function (): StreamedResponse {
                    $reportService = app(MutabakatReportService::class);
                    $year = $this->year > 0 ? $this->year : (int) now()->year - 1;
                    $paginator = $reportService->getFirmSendingStatusPaginated($year, 50000, 1);
                    $items = $paginator->items();
                    $rows = is_array($items) ? $items : collect($items)->all();
                    return (new FirmSendingStatusExport($rows))->export();
                }),
        ];
    }

    public function getMaxContentWidth(): ?string
    {
        return 'full';
    }

    public static function getNavigationSort(): ?int
    {
        return 4;
    }
}
