<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Widgets\Widget;

class UsersDataWidget extends Widget
{
    protected static ?int $sort = 3;
    protected int|string|array $columnSpan = 1;
    protected string $view = 'filament.widgets.users-data-widget';

    protected function getViewData(): array
    {
        $total       = User::count();
        $customers   = User::customers()->count();
        $technicians = User::technicians()->count();
        $active      = User::active()->count();
        $newThisMonth = User::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $customerPct   = $total > 0 ? round($customers / $total * 100) : 0;
        $technicianPct = 100 - $customerPct;

        return compact(
            'total',
            'customers',
            'technicians',
            'active',
            'newThisMonth',
            'customerPct',
            'technicianPct',
        );
    }
}
