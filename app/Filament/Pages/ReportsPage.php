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

    public array $stats = [];
    public array $ratingStats = [];
    public array $leaderboard = [];
    public array $topCustomers = [];
    public array $latestRatings = [];
    public array $ratingBreakdown = [];
    public string $dailyDate = '';
    public array $dailyActivity = [];

    public function mount(ReportService $service): void
    {
        $this->stats = $service->getPerformanceSummary();
        $this->ratingStats = $service->getRatingStats();
        $this->leaderboard = $service->getLeaderboard(5)->toArray();
        $this->topCustomers = $service->getTopCustomers(10);
        $this->latestRatings = $service->getLatestRatings(20);
        $this->ratingBreakdown = $service->getRatingBreakdown();
        $this->dailyDate = now()->format('Y-m-d');
        $this->loadDailyActivity();
    }

    public function loadDailyActivity(): void
    {
        if ($this->dailyDate) {
            $this->dailyActivity = app(ReportService::class)->getDailyActivity($this->dailyDate);
        }
    }

    public function updatedDailyDate(): void
    {
        $this->loadDailyActivity();
    }

    public function getTitle(): string
    {
        return __('Reports & Analytics');
    }
}
