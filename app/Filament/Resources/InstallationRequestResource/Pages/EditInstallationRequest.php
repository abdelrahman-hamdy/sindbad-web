<?php

namespace App\Filament\Resources\InstallationRequestResource\Pages;

use App\Filament\Resources\InstallationRequestResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditInstallationRequest extends EditRecord
{
    protected static string $resource = InstallationRequestResource::class;

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
