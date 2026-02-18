<?php

namespace App\Filament\Pages;

use App\Models\Customer;
use App\Services\FirmCustomMailService;
use App\Services\MutabakatReportService;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Log;

class FirmalaraMailGonderPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';
    protected static ?string $navigationLabel = 'Firmalara Mail Gönder';
    protected static ?string $title = 'Firmalara Mail Gönder';
    protected static ?string $navigationGroup = 'Raporlar';
    protected static ?int $navigationSort = 5;
    protected static string $view = 'filament.pages.firmalara-mail-gonder';

    public int $year = 0;
    public array $selectedCustomerIds = [];
    public array $tableRows = [];

    public array $formData = [
        'subject' => '',
        'content' => '',
        'attachments' => [],
        'filters' => ['year' => null, 'status' => ''],
    ];

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
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Select::make('filters.year')
                                    ->label('Yıl')
                                    ->options(function () {
                                        $years = \App\Models\ReconciliationRequest::query()
                                            ->select('year')->distinct()->orderByDesc('year')->pluck('year', 'year');
                                        return $years->isEmpty() ? [now()->year - 1 => now()->year - 1] : $years->toArray();
                                    })
                                    ->default(now()->year - 1)
                                    ->required(),
                                Forms\Components\Select::make('filters.status')
                                    ->label('Durum Filtresi')
                                    ->options([
                                        '' => 'Tümü',
                                        'gonderilmedi' => 'Gönderilmedi',
                                        'banka_eklenmemis' => 'Banka eklenmemiş',
                                        'kismen' => 'Kısmen',
                                        'hepsi_gonderildi' => 'Hepsi gönderildi',
                                        'manuel_ile' => 'Manuel ile',
                                    ])
                                    ->default(''),
                                Forms\Components\Actions::make([
                                    Forms\Components\Actions\Action::make('loadFirms')
                                        ->label('Firmaları Yükle')
                                        ->icon('heroicon-o-arrow-path')
                                        ->color('primary')
                                        ->action('loadFirms'),
                                ]),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(false),
                Forms\Components\Section::make('Mail İçeriği')
                    ->schema([
                        Forms\Components\TextInput::make('subject')
                            ->label('Konu')
                            ->required()
                            ->placeholder('E-posta konusu')
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('content')
                            ->label('İçerik')
                            ->required()
                            ->rows(8)
                            ->placeholder("İstediğiniz metni yazın. {firma_adi} kullanarak firma adını ekleyebilirsiniz.")
                            ->helperText('Placeholder: {firma_adi} = firma adı, {yil} = yıl')
                            ->columnSpanFull(),
                        Forms\Components\FileUpload::make('attachments')
                            ->label('Ek Dosyalar')
                            ->disk('local')
                            ->directory('firm_mail_attachments')
                            ->multiple()
                            ->acceptedFileTypes([
                                'application/pdf',
                                'application/msword',
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                'image/png',
                                'image/jpeg',
                                'image/jpg',
                            ])
                            ->maxSize(10240)
                            ->downloadable()
                            ->previewable()
                            ->helperText('PDF, Word veya resim. Maks. 10MB/dosya.')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(false),
            ])
            ->statePath('formData');
    }

    public function mount(): void
    {
        $this->year = (int) (now()->year - 1);
        $this->formData = [
            'subject' => '',
            'content' => '',
            'attachments' => [],
            'filters' => [
                'year' => $this->year,
                'status' => '',
            ],
        ];
        $this->form->fill($this->formData);
        $this->loadFirms();
    }

    public function loadFirms(): void
    {
        try {
            $this->formData = $this->form->getState();
        } catch (\Throwable $e) {
            // Form henüz hazır değilse formData'yı kullan
        }
        $filters = $this->formData['filters'] ?? [];
        $year = (int) ($filters['year'] ?? $this->year);
        $status = $filters['status'] ?? '';

        $this->year = $year;

        $reportService = app(MutabakatReportService::class);
        $this->tableRows = $reportService->getFirmSendingStatusRows($year, $status ?: null);
    }

    public function toggleSelectAll(): void
    {
        $ids = collect($this->tableRows)->pluck('customer_id')->filter()->all();
        if (count($this->selectedCustomerIds) >= count($ids)) {
            $this->selectedCustomerIds = [];
        } else {
            $this->selectedCustomerIds = $ids;
        }
    }

    public function toggleCustomer(int $customerId): void
    {
        if (in_array($customerId, $this->selectedCustomerIds)) {
            $this->selectedCustomerIds = array_values(array_diff($this->selectedCustomerIds, [$customerId]));
        } else {
            $this->selectedCustomerIds[] = $customerId;
        }
    }

    public function isSelected(int $customerId): bool
    {
        return in_array($customerId, $this->selectedCustomerIds);
    }

    public function sendMails(): void
    {
        $this->formData = $this->form->getState();
        $subject = $this->formData['subject'] ?? '';
        $content = $this->formData['content'] ?? '';
        $attachments = $this->formData['attachments'] ?? [];

        \Illuminate\Support\Facades\Validator::make($this->formData, [
            'subject' => 'required|string|max:255',
            'content' => 'required|string',
        ], [], ['subject' => 'Konu', 'content' => 'İçerik'])->validate();

        if (empty($this->selectedCustomerIds)) {
            Notification::make()
                ->title('Firma seçilmedi')
                ->body('Lütfen en az bir firma seçin.')
                ->danger()
                ->send();
            return;
        }

        $customers = Customer::whereIn('id', $this->selectedCustomerIds)->get();
        $mailService = app(FirmCustomMailService::class);
        $sent = 0;
        $skipped = 0;

        foreach ($customers as $customer) {
            if (empty($customer->email) || !filter_var($customer->email, FILTER_VALIDATE_EMAIL)) {
                $skipped++;
                continue;
            }
            try {
                $mailService->sendToCustomer($customer, $subject, $content, $this->year, $attachments);
                $sent++;
            } catch (\Throwable $e) {
                Log::error('Firmalara mail gönderim hatası', [
                    'customer_id' => $customer->id,
                    'error' => $e->getMessage(),
                ]);
                Notification::make()
                    ->title("Hata: {$customer->name}")
                    ->body($e->getMessage())
                    ->danger()
                    ->send();
            }
        }

        $msg = "{$sent} firmaya mail gönderildi.";
        if ($skipped > 0) {
            $msg .= " {$skipped} firma atlandı (e-posta yok).";
        }
        Notification::make()
            ->title('Mail gönderimi tamamlandı')
            ->body($msg)
            ->success()
            ->send();
    }
}
