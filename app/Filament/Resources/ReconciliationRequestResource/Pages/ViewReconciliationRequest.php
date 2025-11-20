<?php

namespace App\Filament\Resources\ReconciliationRequestResource\Pages;

use App\Filament\Resources\ReconciliationRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewReconciliationRequest extends ViewRecord
{
    protected static string $resource = ReconciliationRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Resources\ReconciliationRequestResource\Widgets\RequestSummaryWidget::class,
            \App\Filament\Resources\ReconciliationRequestResource\Widgets\RequestProgressWidget::class,
            \App\Filament\Resources\ReconciliationRequestResource\Widgets\RequestTimelineWidget::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int | array
    {
        return [
            'md' => 2,
            'xl' => 3,
        ];
    }
}
