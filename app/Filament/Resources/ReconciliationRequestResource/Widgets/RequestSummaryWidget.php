<?php

namespace App\Filament\Resources\ReconciliationRequestResource\Widgets;

use Filament\Widgets\Widget;

class RequestSummaryWidget extends Widget
{
    protected static string $view = 'filament.resources.reconciliation-request-resource.widgets.request-summary-widget';

    public $record;

    public function mount($record)
    {
        $this->record = $record;
    }
}
