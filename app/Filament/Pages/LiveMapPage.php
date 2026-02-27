<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class LiveMapPage extends Page
{
    protected string $view = 'filament.pages.live-map';

    public static function getNavigationLabel(): string { return __('Live Map'); }

    public static function getNavigationGroup(): ?string { return __('Analytics'); }

    public static function getNavigationIcon(): string|\BackedEnum|\Illuminate\Contracts\Support\Htmlable|null
    {
        return 'heroicon-o-map';
    }

    public static function getNavigationSort(): ?int { return 10; }

    public function getTitle(): string
    {
        return __('Live Technician Map');
    }
}
