<?php

namespace App\Filament\Pages;

use App\Services\ReportService;
use Filament\Pages\Page;

class ReportsPage extends Page
{
    protected string $view = 'filament.pages.reports';

    public static function getNavigationLabel(): string { return __('Reports'); }

    public static function getNavigationGroup(): ?string { return __('Analytics'); }

    public static function getNavigationIcon(): string|\BackedEnum|\Illuminate\Contracts\Support\Htmlable|null
    {
        return 'heroicon-o-chart-bar';
    }

    public static function getNavigationSort(): ?int { return 11; }

    public string $period = '30';
    public string $trendPeriod = '30';

    public array $stats = [];
    public array $filteredStats = [];
    public array $ratingStats = [];
    public array $leaderboard = [];
    public array $bottomPerformers = [];
    public array $topCustomers = [];
    public array $latestRatings = [];
    public array $ratingBreakdown = [];
    public array $typeBreakdown = [];
    public array $trendData = [];
    public float $avgCompletion = 0.0;

    public function mount(ReportService $service): void
    {
        $this->stats            = $service->getPerformanceSummary();
        $this->filteredStats    = $service->getFilteredStats($this->period);
        $this->ratingStats      = $service->getRatingStats();
        $this->leaderboard      = $service->getLeaderboard(5)->toArray();
        $this->bottomPerformers = $service->getBottomPerformers(5)->toArray();
        $this->topCustomers     = $service->getTopCustomers(10);
        $this->latestRatings    = $service->getLatestRatings(20);
        $this->ratingBreakdown  = $service->getRatingBreakdown();
        $this->typeBreakdown    = $service->getServiceTypeBreakdown();
        $this->avgCompletion    = $service->getAvgCompletionTime();
        $this->trendData        = $service->getRequestTrendsGrouped(30);
    }

    public function updatedPeriod(): void
    {
        $this->filteredStats = app(ReportService::class)->getFilteredStats($this->period);
    }

    public function updatedTrendPeriod(): void
    {
        $this->trendData = app(ReportService::class)->getRequestTrendsGrouped((int) $this->trendPeriod);
    }

    public function getTitle(): string
    {
        return __('Reports & Analytics');
    }
}
