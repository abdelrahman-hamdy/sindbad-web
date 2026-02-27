<?php

namespace App\Filament\Resources\InstallationRequestResource\Pages;

use App\Filament\Resources\InstallationRequestResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListInstallationRequests extends ListRecords
{
    protected static string $resource = InstallationRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
