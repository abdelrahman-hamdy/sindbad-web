<?php

namespace App\Filament\Widgets;

use App\Services\ReportService;
use Filament\Widgets\ChartWidget;

class RequestTrendsChartWidget extends ChartWidget
{
    public ?string $filter = '30';

    protected static ?int $sort = 99;

    protected function getType(): string
    {
        return 'line';
    }

    public function getHeading(): string
    {
        return __('Request Volume Trend');
    }

    protected function getFilters(): ?array
    {
        return [
            '7'  => __('7 Days'),
            '30' => __('30 Days'),
            '90' => __('90 Days'),
        ];
    }

    protected function getData(): array
    {
        $data = app(ReportService::class)->getRequestTrendsGrouped((int) $this->filter);

        return [
            'datasets' => [
                [
                    'label'           => __('Service'),
                    'data'            => $data['service'],
                    'borderColor'     => '#0EA5E9',
                    'backgroundColor' => 'rgba(14,165,233,0.1)',
                    'tension'         => 0.4,
                    'fill'            => true,
                ],
                [
                    'label'           => __('Installation'),
                    'data'            => $data['installation'],
                    'borderColor'     => '#8B5CF6',
                    'backgroundColor' => 'rgba(139,92,246,0.1)',
                    'tension'         => 0.4,
                    'fill'            => true,
                ],
            ],
            'labels' => $data['labels'],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => ['position' => 'top'],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks'       => ['stepSize' => 1],
                ],
            ],
        ];
    }
}
