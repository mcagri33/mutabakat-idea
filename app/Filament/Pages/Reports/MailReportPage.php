<?php

namespace App\Filament\Pages\Reports;

use App\Models\Customer;
use App\Services\MutabakatReportService;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as LengthAwarePaginatorConcrete;
use Illuminate\Support\Facades\Log;

class MailReportPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';
    protected static ?string $navigationLabel = 'Firma – Banka Bazlı Mail Raporu';
    protected static ?string $title = 'Firma – Banka Bazlı Mail Raporu';
    protected static ?string $navigationGroup = 'Raporlar';
    protected static ?int $navigationSort = 3;
    protected static string $view = 'filament.pages.reports.mail-report-page';

    /** @var array{customer_id?: int|null, year?: int|null, mail_status?: string|null, reply_status?: string|null} */
    public array $filters = [
        'customer_id' => null,
        'year' => null,
        'mail_status' => null,
        'reply_status' => null,
    ];

    public int $page = 1;
    public int $perPage = 15;
    public ?LengthAwarePaginator $banksPaginator = null;

    public function mount(MutabakatReportService $reportService): void
    {
        $this->banksPaginator = new LengthAwarePaginatorConcrete([], 0, 15, 1);
        try {
            $this->filters['year'] = $this->filters['year'] ?? now()->year;
            $this->form->fill($this->filters);
            $this->loadData($reportService);
        } catch (\Throwable $e) {
            Log::error('MailReportPage mount hatası', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->banksPaginator = new LengthAwarePaginatorConcrete([], 0, max(1, $this->perPage), 1);
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
                                Select::make('customer_id')
                                    ->label('Firma')
                                    ->options(function () {
                                        try {
                                            return Customer::query()->orderBy('name')->pluck('name', 'id')->toArray();
                                        } catch (\Throwable $e) {
                                            Log::warning('MailReportPage: Firma listesi alınamadı', ['error' => $e->getMessage()]);
                                            return [];
                                        }
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->placeholder('Tümü'),
                                Select::make('year')
                                    ->label('Yıl')
                                    ->options(function () {
                                        try {
                                            $years = \App\Models\ReconciliationRequest::query()
                                                ->select('year')
                                                ->distinct()
                                                ->orderByDesc('year')
                                                ->pluck('year', 'year');
                                            return $years->isEmpty() ? [now()->year => now()->year] : $years->toArray();
                                        } catch (\Throwable $e) {
                                            Log::warning('MailReportPage: Yıl listesi alınamadı', ['error' => $e->getMessage()]);
                                            return [now()->year => now()->year];
                                        }
                                    })
                                    ->placeholder('Tümü'),
                                Select::make('mail_status')
                                    ->label('Mail Durumu')
                                    ->options([
                                        'pending' => 'Beklemede',
                                        'sent' => 'Gönderildi',
                                        'failed' => 'Hata',
                                    ])
                                    ->placeholder('Tümü'),
                                Select::make('reply_status')
                                    ->label('Cevap Durumu')
                                    ->options([
                                        'pending' => 'Beklemede',
                                        'received' => 'Geldi',
                                        'completed' => 'Tamamlandı',
                                    ])
                                    ->placeholder('Tümü'),
                            ]),
                        Forms\Components\Actions::make([
                            Forms\Components\Actions\Action::make('filter')
                                ->label('Filtrele')
                                ->icon('heroicon-o-funnel')
                                ->color('primary')
                                ->action(function (MutabakatReportService $reportService) {
                                    $this->filters = $this->form->getState();
                                    $this->page = 1;
                                    $this->loadData($reportService);
                                }),
                            Forms\Components\Actions\Action::make('reset')
                                ->label('Temizle')
                                ->icon('heroicon-o-x-mark')
                                ->color('gray')
                                ->action(function (MutabakatReportService $reportService) {
                                    $this->filters = [
                                        'customer_id' => null,
                                        'year' => now()->year,
                                        'mail_status' => null,
                                        'reply_status' => null,
                                    ];
                                    $this->form->fill($this->filters);
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

    public function loadData(MutabakatReportService $reportService): void
    {
        try {
            $perPage = max(1, min(100, $this->perPage));
            $page = max(1, $this->page);
            $this->banksPaginator = $reportService->getMailReportBanksPaginated(
                $this->filters,
                $perPage,
                $page
            );
        } catch (\Throwable $e) {
            Log::error('MailReportPage loadData hatası', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->banksPaginator = new LengthAwarePaginatorConcrete([], 0, max(1, $this->perPage), 1);
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

    public function getMaxContentWidth(): ?string
    {
        return 'full';
    }

    public static function getNavigationSort(): ?int
    {
        return 3;
    }
}
