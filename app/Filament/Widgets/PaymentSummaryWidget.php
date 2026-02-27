<?php

namespace App\Filament\Widgets;

use App\Enums\ManualOrderStatus;
use App\Models\ManualOrder;
use Filament\Widgets\Widget;

class PaymentSummaryWidget extends Widget
{
    protected static ?int $sort = 4;
    protected int|string|array $columnSpan = 'full';
    protected string $view = 'filament.widgets.payment-summary-widget';

    protected function getViewData(): array
    {
        $totalInvoiced  = (float) ManualOrder::sum('total_amount');
        $totalCollected = (float) ManualOrder::sum('paid_amount');
        $totalRemaining = (float) ManualOrder::sum('remaining_amount');
        $paidCount      = ManualOrder::where('status', ManualOrderStatus::Paid->value)->count();
        $partialCount   = ManualOrder::where('status', ManualOrderStatus::Partial->value)->count();
        $collectionRate = $totalInvoiced > 0 ? round($totalCollected / $totalInvoiced * 100, 1) : 0;

        return compact(
            'totalInvoiced',
            'totalCollected',
            'totalRemaining',
            'paidCount',
            'partialCount',
            'collectionRate',
        );
    }
}
