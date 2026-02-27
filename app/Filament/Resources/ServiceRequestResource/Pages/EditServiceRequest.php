<?php

namespace App\Filament\Resources\ServiceRequestResource\Pages;

use App\Filament\Resources\ServiceRequestResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditServiceRequest extends EditRecord
{
    protected static string $resource = ServiceRequestResource::class;

    private array $pendingTechnicianImages = [];

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->pendingTechnicianImages = $data['technician_images_upload'] ?? [];
        unset($data['technician_images_upload']);
        return $data;
    }

    protected function afterSave(): void
    {
        foreach ($this->pendingTechnicianImages as $path) {
            $this->record->addMediaFromDisk($path, 'public')->toMediaCollection('technician_images');
        }
    }
}
