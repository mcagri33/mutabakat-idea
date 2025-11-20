<?php

namespace App\Filament\Resources\ReconciliationRequestResource\Widgets;

use Filament\Widgets\Widget;

class RequestTimelineWidget extends Widget
{
    protected static string $view = ' filament.resources.reconciliation-request-resource.widgets.timeline';

    public $record;

    public function mount($record)
    {
        $this->record = $record;
    }
}
