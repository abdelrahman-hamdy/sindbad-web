<?php

namespace App\Filament\Widgets;

use App\Enums\RequestStatus;
use App\Enums\RequestType;
use App\Filament\Resources\InstallationRequestResource;
use App\Models\Request;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentInstallationRequestsWidget extends BaseWidget
{
    protected static ?int $sort = 7;
    protected ?string $pollingInterval = '30s';
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('Recent Installation Requests'))
            ->query(
                Request::with(['user', 'technician'])
                    ->where('type', RequestType::Installation->value)
                    ->latest()
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('product_type')
                    ->label(__('Product / Invoice'))
                    ->weight('bold')
                    ->color('primary')
                    ->limit(35)
                    ->tooltip(fn($state) => $state)
                    ->description(fn(Request $record) => $record->invoice_number ? '#' . $record->invoice_number : '—')
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label(__('Customer'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('technician.name')
                    ->label(__('Technician'))
                    ->default(__('Unassigned'))
                    ->weight('bold')
                    ->icon(fn($record) => $record->technician_id ? 'heroicon-m-wrench-screwdriver' : null)
                    ->color(fn($record) => $record->technician_id ? null : 'danger'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn($state) => $state instanceof RequestStatus ? $state->label() : RequestStatus::from($state)->label())
                    ->color(fn($state) => $state instanceof RequestStatus ? $state->color() : RequestStatus::from($state)->color()),
                Tables\Columns\TextColumn::make('scheduled_at')
                    ->label(__('Scheduled'))
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Submitted'))
                    ->since()
                    ->sortable(),
            ])
            ->striped()
            ->recordUrl(fn(Request $record) => InstallationRequestResource::getUrl('view', ['record' => $record->id]))
            ->paginated(false)
            ->emptyStateHeading(__('No installation requests yet'))
            ->emptyStateIcon('heroicon-o-home-modern');
    }
}
