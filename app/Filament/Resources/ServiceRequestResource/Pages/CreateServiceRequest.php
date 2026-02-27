<?php

namespace App\Filament\Resources\ServiceRequestResource\Pages;

use App\Enums\RequestType;
use App\Filament\Resources\ServiceRequestResource;
use Filament\Resources\Pages\CreateRecord;

class CreateServiceRequest extends CreateRecord
{
    protected static string $resource = ServiceRequestResource::class;

    private array $pendingTechnicianImages = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['type'] = RequestType::Service->value;
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
