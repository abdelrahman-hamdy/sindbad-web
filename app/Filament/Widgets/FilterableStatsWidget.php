<?php

namespace App\Filament\Widgets;

use App\Enums\RequestStatus;
use App\Enums\RequestType;
use App\Models\AppSetting;
use App\Models\Request;
use Filament\Widgets\Widget;

class FilterableStatsWidget extends Widget
{
    protected static ?int $sort = 1;
    protected int|string|array $columnSpan = 'full';
    protected ?string $pollingInterval = '30s';
    protected string $view = 'filament.widgets.filterable-stats-widget';

    public string $filter = 'month';

    public function mount(): void
    {
        $this->filter = AppSetting::get('dashboard_default_filter', 'month');
    }

    protected function getViewData(): array
    {
        return [
            'stats'     => $this->computeStats(),
            'monthName' => now()->format('F'),
        ];
    }

    private function computeStats(): array
    {
        $result = [];

        foreach ([RequestType::Service->value, RequestType::Installation->value] as $type) {
            $base = Request::where('type', $type);

            match ($this->filter) {
                'today' => $base->whereDate('created_at', today()),
                'week'  => $base->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]),
                'month' => $base->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year),
                default => null,
            };

            $result[$type] = [
                'total'     => (clone $base)->count(),
                'pending'   => (clone $base)->where('status', RequestStatus::Pending->value)->count(),
                'active'    => (clone $base)->whereIn('status', [
                    RequestStatus::Assigned->value,
                    RequestStatus::OnWay->value,
                    RequestStatus::InProgress->value,
                ])->count(),
                'completed' => (clone $base)->where('status', RequestStatus::Completed->value)->count(),
                'canceled'  => (clone $base)->where('status', RequestStatus::Canceled->value)->count(),
            ];
        }

        return $result;
    }
}
