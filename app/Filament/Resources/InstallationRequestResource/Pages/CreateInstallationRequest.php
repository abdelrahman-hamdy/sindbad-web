<?php

namespace App\Filament\Resources\InstallationRequestResource\Pages;

use App\Enums\RequestType;
use App\Filament\Resources\InstallationRequestResource;
use Filament\Resources\Pages\CreateRecord;

class CreateInstallationRequest extends CreateRecord
{
    protected static string $resource = InstallationRequestResource::class;

    private array $pendingTechnicianImages = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['type'] = RequestType::Installation->value;
        $this->pendingTechnicianImages = $data['technician_images_upload'] ?? [];
        unset($data['technician_images_upload']);
        return $data;
    }

    protected function afterCreate(): void
    {
        foreach ($this->pendingTechnicianImages as $path) {
            $this->record->addMediaFromDisk($path, 'public')->toMediaCollection('technician_images');
        }
    }
}
