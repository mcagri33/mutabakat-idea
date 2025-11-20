<?php

namespace App\Filament\Resources\ReconciliationRequestResource\Widgets;

use Filament\Widgets\Widget;

class RequestProgressWidget extends Widget
{
    protected static string $view = 'filament.resources.reconciliation-request-resource.widgets.progress';

    public $record;
    public int $total;
    public int $received;
    public int $percent;

    public function mount($record)
    {
        $this->record = $record;
        $this->total = $record->banks()->count();
        $this->received = $record->banks()->where('reply_status', 'received')->count();
        $this->percent = $this->total > 0 ? round(($this->received / $this->total) * 100) : 0;
    }
}
