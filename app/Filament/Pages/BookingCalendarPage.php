<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\BookingCalendarWidget;
use Filament\Pages\Page;

class BookingCalendarPage extends Page
{
    protected string $view = 'filament.pages.booking-calendar';

    public static function getNavigationLabel(): string
    {
        return __('Booking Calendar');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Requests');
    }

    public static function getNavigationIcon(): string|\BackedEnum|\Illuminate\Contracts\Support\Htmlable|null
    {
        return 'heroicon-o-calendar-days';
    }

    public static function getNavigationSort(): ?int
    {
        return 5;
    }

    public function getTitle(): string
    {
        return __('Booking Calendar');
    }

    protected function getFooterWidgets(): array
    {
        return [
            BookingCalendarWidget::class,
        ];
    }
}
