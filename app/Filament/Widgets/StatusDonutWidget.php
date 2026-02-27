<?php

namespace App\Filament\Widgets;

use App\Enums\RequestStatus;
use App\Services\ReportService;
use Filament\Widgets\ChartWidget;

class StatusDonutWidget extends ChartWidget
{
    protected string $view = 'filament.widgets.status-donut-widget';
    public function getHeading(): string { return __('Requests by Status'); }
    protected static ?int $sort = 2;
    protected int|string|array $columnSpan = 1;
    protected ?string $maxHeight = '260px';

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $counts = app(ReportService::class)->getStatusCounts();

        $labels = [];
        $data   = [];

        foreach (RequestStatus::cases() as $status) {
            $labels[] = $status->label();
            $data[]   = $counts[$status->value] ?? 0;
        }

        return [
            'datasets' => [
                [
                    'data'            => $data,
                    'backgroundColor' => ['#F59E0B', '#3B82F6', '#8B5CF6', '#6366F1', '#10B981', '#EF4444'],
                    'borderWidth'     => 2,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => ['position' => 'bottom'],
            ],
            'layout' => [
                'padding' => ['top' => 8, 'bottom' => 8, 'left' => 24, 'right' => 24],
            ],
        ];
    }
}
