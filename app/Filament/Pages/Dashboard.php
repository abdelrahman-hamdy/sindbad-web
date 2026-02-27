<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\CustomerSatisfactionWidget;
use App\Filament\Widgets\FilterableStatsWidget;
use App\Filament\Widgets\PaymentSummaryWidget;
use App\Filament\Widgets\RecentInstallationRequestsWidget;
use App\Filament\Widgets\RecentServiceRequestsWidget;
use App\Filament\Widgets\StatusDonutWidget;
use App\Filament\Widgets\TechnicianPerformanceWidget;
use App\Filament\Widgets\UsersDataWidget;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Livewire as LivewireWidget;
use Filament\Schemas\Schema;

class Dashboard extends BaseDashboard
{
    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)
                ->extraAttributes(['class' => 'fi-dashboard-grid'])
                ->schema([
                    // Row 1: full width
                    LivewireWidget::make(FilterableStatsWidget::class)->columnSpanFull(),

                    // Row 2: half | half  (equal height via fi-dashboard-grid CSS)
                    LivewireWidget::make(StatusDonutWidget::class)->columnSpan(1),
                    LivewireWidget::make(UsersDataWidget::class)->columnSpan(1),

                    // Row 3: full width
                    LivewireWidget::make(PaymentSummaryWidget::class)->columnSpanFull(),

                    // Row 4: half | half  (equal height via fi-dashboard-grid CSS)
                    LivewireWidget::make(TechnicianPerformanceWidget::class)->columnSpan(1),
                    LivewireWidget::make(CustomerSatisfactionWidget::class)->columnSpan(1),

                    // Row 5: full width
                    LivewireWidget::make(RecentInstallationRequestsWidget::class)->columnSpanFull(),

                    // Row 6: full width
                    LivewireWidget::make(RecentServiceRequestsWidget::class)->columnSpanFull(),
                ]),
        ]);
    }
}
