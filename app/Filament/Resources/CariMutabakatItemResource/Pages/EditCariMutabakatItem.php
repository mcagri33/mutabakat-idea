<?php

namespace App\Filament\Resources\CariMutabakatItemResource\Pages;

use App\Filament\Resources\CariMutabakatItemResource;
use App\Services\CariMutabakatPdfService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Log;

class EditCariMutabakatItem extends EditRecord
{
    protected static string $resource = CariMutabakatItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('goToRequest')
                ->label('Talep Görüntüle')
                ->icon('heroicon-o-eye')
                ->url(fn () => route('filament.mutabakat.resources.cari-mutabakat-requests.view', ['record' => $this->record->request_id])),
        ];
    }

    protected function afterSave(): void
    {
        $item = $this->record->fresh(['reply']);
        if ($item->reply) {
            try {
                $pdfService = app(CariMutabakatPdfService::class);
                $pdfPath = $pdfService->generatePdf($item);
                $item->reply->update(['pdf_path' => $pdfPath]);
                Notification::make()
                    ->title('PDF güncellendi')
                    ->success()
                    ->send();
            } catch (\Throwable $e) {
                Log::error('Cari PDF yenileme hatası', ['item_id' => $item->id, 'error' => $e->getMessage()]);
                Notification::make()
                    ->title('PDF güncellenemedi')
                    ->body($e->getMessage())
                    ->warning()
                    ->send();
            }
        }
    }
}
