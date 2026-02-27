<?php

namespace App\Filament\Widgets;

use App\Services\ReportService;
use Filament\Widgets\Widget;

class CustomerSatisfactionWidget extends Widget
{
    protected static ?int $sort = 6;
    protected int|string|array $columnSpan = 1;
    protected string $view = 'filament.widgets.customer-satisfaction-widget';

    protected function getViewData(): array
    {
        $service     = app(ReportService::class);
        $ratingStats = $service->getRatingStats();
        $breakdown   = $service->getRatingBreakdown();
        $overallAvg  = round(($ratingStats['avg_product'] + $ratingStats['avg_service']) / 2, 1);

        return compact('ratingStats', 'breakdown', 'overallAvg');
    }
}
