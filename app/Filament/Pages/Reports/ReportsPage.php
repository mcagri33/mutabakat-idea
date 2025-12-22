<?php

namespace App\Filament\Pages\Reports;

use App\Models\Customer;
use App\Models\ReconciliationRequest;
use App\Exports\ReconciliationRequestExport;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static ?string $navigationLabel = 'Raporlar';
    protected static ?string $title = 'Raporlar';
    protected static ?string $navigationGroup = 'Mutabakat Yönetimi';
    protected static ?int $navigationSort = 10;
    protected static string $view = 'filament.pages.reports.reports-page';

    // Filtreler
    public array $filters = [
        'customer_id' => null,
        'start_date' => null,
        'end_date' => null,
        'status' => null,
        'type' => null,
        'year' => null,
    ];

    // Rapor verileri
    public $reportData = [];
    public $summaryStats = [];

    public function mount(): void
    {
        $this->filters['year'] = now()->year;
        $this->form->fill($this->filters);
        $this->loadReportData();
    }

    protected function getForms(): array
    {
        return [
            'form',
        ];
    }

    public function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Filtreler')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Select::make('customer_id')
                                    ->label('Firma')
                                    ->options(Customer::query()->pluck('name', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->placeholder('Tüm Firmalar'),

                                Select::make('status')
                                    ->label('Durum')
                                    ->options([
                                        'pending' => 'Beklemede',
                                        'mail_sent' => 'Mail Gönderildi',
                                        'partially' => 'Kısmi Dönüş',
                                        'received' => 'Tam Dönüş',
                                        'completed' => 'Tamamlandı',
                                        'failed' => 'Hata',
                                    ])
                                    ->placeholder('Tüm Durumlar'),

                                Select::make('type')
                                    ->label('Tip')
                                    ->options([
                                        'banka' => 'Banka Mutabakatı',
                                        'cari' => 'Cari Mutabakat',
                                    ])
                                    ->placeholder('Tüm Tipler'),
                            ]),

                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('year')
                                    ->label('Yıl')
                                    ->numeric()
                                    ->default(now()->year)
                                    ->minValue(2020)
                                    ->maxValue(now()->year + 1),

                                DatePicker::make('start_date')
                                    ->label('Başlangıç Tarihi')
                                    ->displayFormat('d/m/Y')
                                    ->native(false),

                                DatePicker::make('end_date')
                                    ->label('Bitiş Tarihi')
                                    ->displayFormat('d/m/Y')
                                    ->native(false),
                            ]),

                        Forms\Components\Actions::make([
                            Forms\Components\Actions\Action::make('filter')
                                ->label('Filtrele')
                                ->icon('heroicon-o-funnel')
                                ->color('primary')
                                ->action(function () {
                                    $this->filters = $this->form->getState();
                                    $this->loadReportData();
                                }),
                            Forms\Components\Actions\Action::make('reset')
                                ->label('Temizle')
                                ->icon('heroicon-o-x-mark')
                                ->color('gray')
                                ->action(function () {
                                    $this->filters = [
                                        'customer_id' => null,
                                        'start_date' => null,
                                        'end_date' => null,
                                        'status' => null,
                                        'type' => null,
                                        'year' => now()->year,
                                    ];
                                    $this->form->fill($this->filters);
                                    $this->loadReportData();
                                }),
                        ])->fullWidth(),
                    ])
                    ->collapsible()
                    ->collapsed(false),
            ])
            ->statePath('filters');
    }

    public function loadReportData(): void
    {
        $query = ReconciliationRequest::query()
            ->with(['customer', 'banks'])
            ->withCount(['banks', 'documents']);

        // Filtreler
        if (!empty($this->filters['customer_id'])) {
            $query->where('customer_id', $this->filters['customer_id']);
        }

        if (!empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        if (!empty($this->filters['type'])) {
            $query->where('type', $this->filters['type']);
        }

        if (!empty($this->filters['year'])) {
            $query->where('year', $this->filters['year']);
        }

        if (!empty($this->filters['start_date'])) {
            $query->whereDate('created_at', '>=', $this->filters['start_date']);
        }

        if (!empty($this->filters['end_date'])) {
            $query->whereDate('created_at', '<=', $this->filters['end_date']);
        }

        $this->reportData = $query->orderBy('id', 'desc')->get();

        // Özet istatistikler
        $this->summaryStats = [
            'total' => $this->reportData->count(),
            'pending' => $this->reportData->where('status', 'pending')->count(),
            'mail_sent' => $this->reportData->where('status', 'mail_sent')->count(),
            'partially' => $this->reportData->where('status', 'partially')->count(),
            'received' => $this->reportData->where('status', 'received')->count(),
            'completed' => $this->reportData->where('status', 'completed')->count(),
            'failed' => $this->reportData->where('status', 'failed')->count(),
            'banka' => $this->reportData->where('type', 'banka')->count(),
            'cari' => $this->reportData->where('type', 'cari')->count(),
        ];
    }

    public function exportExcel(): StreamedResponse
    {
        $export = new ReconciliationRequestExport($this->reportData);
        return $export->export();
    }
}

