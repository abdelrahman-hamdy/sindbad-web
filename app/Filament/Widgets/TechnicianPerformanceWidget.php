<?php

namespace App\Filament\Widgets;

use App\Services\ReportService;
use Filament\Widgets\Widget;

class TechnicianPerformanceWidget extends Widget
{
    protected static ?int $sort = 5;
    protected int|string|array $columnSpan = 1;
    protected string $view = 'filament.widgets.technician-performance-widget';

    protected function getViewData(): array
    {
        $service = app(ReportService::class);

        return [
            'top'    => $service->getLeaderboard(5),
            'bottom' => $service->getBottomPerformers(5),
        ];
    }
}
