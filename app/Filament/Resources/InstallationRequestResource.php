<?php

namespace App\Filament\Resources;

use App\Enums\RequestStatus;
use App\Enums\RequestType;
use App\Filament\Resources\InstallationRequestResource\Pages;
use App\Models\Request;
use App\Models\User;
use App\Services\Odoo\OdooServiceInterface;
use App\Services\RequestService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Enums\FiltersLayout;
use Illuminate\Database\Eloquent\Builder;

class InstallationRequestResource extends Resource
{
    protected static ?string $model = Request::class;

    public static function getNavigationLabel(): string { return __('Installations'); }
    public static function getModelLabel(): string { return __('Installation Request'); }
    public static function getPluralModelLabel(): string { return __('Installation Requests'); }

    public static function getNavigationGroup(): ?string { return __('Requests'); }

    public static function getNavigationIcon(): string|\BackedEnum|\Illuminate\Contracts\Support\Htmlable|null
    {
        return 'heroicon-o-home-modern';
    }

    public static function getNavigationSort(): ?int { return 2; }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('type', RequestType::Installation->value);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)->columnSpanFull()->schema([

                // ── Left column: Basic Info + Installation Details ────────
                Group::make([
                    Section::make(__('Basic Information'))->schema([
                        Forms\Components\Select::make('user_id')
                            ->label(__('Customer'))
                            ->relationship('user', 'name', fn($query) => $query->where('role', \App\Enums\UserRole::Customer->value))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live(),
                        Forms\Components\Select::make('invoice_number')
                            ->label(__('Invoice Number'))
                            ->required()
                            ->searchable()
                            ->native(false)
                            ->placeholder(fn(Get $get) => $get('user_id') ? __('Select an invoice…') : __('Choose a customer first'))
                            ->disabled(fn(Get $get) => !$get('user_id'))
                            ->options(function (Get $get) {
                                $userId = $get('user_id');
                                if (!$userId) return [];
                                $user = \App\Models\User::find($userId);
                                if (!$user || !$user->odoo_id) return [];
                                $orders = app(OdooServiceInterface::class)->getCustomerOrders($user->odoo_id);
                                return collect($orders)->mapWithKeys(function ($order) {
                                    $date  = isset($order['date_order']) ? substr($order['date_order'], 0, 10) : '';
                                    $total = number_format($order['amount_total'] ?? 0, 3);
                                    $label = $order['name'] . ($date ? " ({$date})" : '') . " — {$total} OMR";
                                    return [$order['name'] => $label];
                                })->toArray();
                            }),
                        Forms\Components\DatePicker::make('scheduled_at')->label(__('Scheduled Date'))->required(),
                        Forms\Components\DatePicker::make('end_date')->label(__('End Date')),
                    ])->columns(2),

                    Section::make(__('Installation Details'))->schema([
                        Forms\Components\TextInput::make('product_type')->label(__('Product Type'))->required(),
                        Forms\Components\TextInput::make('quantity')->label(__('Quantity'))->numeric()->default(1)->minValue(1),
                        Forms\Components\Toggle::make('is_site_ready')->label(__('Site Ready')),
                        Forms\Components\Textarea::make('description')->label(__('Description'))->columnSpanFull(),
                    ])->columns(2),
                ]),

                // ── Right column: Location ────────────────────────────────
                Section::make(__('Location'))->schema([
                    Forms\Components\Hidden::make('latitude'),
                    Forms\Components\Hidden::make('longitude'),
                    Forms\Components\TextInput::make('address')
                        ->label(__('Address'))
                        ->required()
                        ->readOnly()
                        ->placeholder(__('Automatically filled when you pick a location below'))
                        ->columnSpanFull(),
                    View::make('filament.forms.location-picker')->columnSpanFull(),
                ]),

            ]),

            Section::make(__('Technician Images'))
                ->columnSpanFull()
                ->schema([
                    Forms\Components\Placeholder::make('existing_technician_images')
                        ->label(__('Current Images'))
                        ->content(function (?\App\Models\Request $record) {
                            if (!$record) return new \Illuminate\Support\HtmlString('');
                            $images = $record->getMedia('technician_images');
                            if ($images->isEmpty()) {
                                return new \Illuminate\Support\HtmlString('<p class="text-sm text-gray-400 italic">' . __('No technician images yet') . '</p>');
                            }
                            $tags = $images->map(fn($m) =>
                                '<a href="' . e($m->getUrl()) . '" target="_blank">' .
                                '<img src="' . e($m->getUrl()) . '" class="h-20 w-20 object-cover rounded-lg border border-gray-200" /></a>'
                            )->implode('');
                            return new \Illuminate\Support\HtmlString('<div class="flex flex-wrap gap-2">' . $tags . '</div>');
                        })
                        ->visibleOn('edit')
                        ->columnSpanFull(),
                    Forms\Components\FileUpload::make('technician_images_upload')
                        ->label(__('Upload Images'))
                        ->helperText(__('Images will be added to the technician images collection.'))
                        ->multiple()
                        ->image()
                        ->maxFiles(10)
                        ->disk('public')
                        ->directory('technician-images')
                        ->columnSpanFull(),
                ])
                ->columns(1),

            Section::make(__('Assignment'))
                ->columnSpanFull()
                ->schema([
                    Forms\Components\Select::make('technician_id')
                        ->label(__('Technician'))
                        ->relationship('technician', 'name', fn($query) => $query->where('role', \App\Enums\UserRole::Technician->value)->where('is_active', true))
                        ->searchable()
                        ->preload()
                        ->nullable(),
                    Forms\Components\Select::make('status')
                        ->label(__('Status'))
                        ->options(collect(RequestStatus::cases())->mapWithKeys(fn($s) => [$s->value => $s->label()]))
                        ->required(),
                    Forms\Components\DateTimePicker::make('task_start_time')->label(__('Task Start'))->disabled(),
                    Forms\Components\DateTimePicker::make('task_end_time')->label(__('Task End'))->disabled(),
                    Forms\Components\Placeholder::make('task_time_note')
                        ->hiddenLabel()
                        ->content(new \Illuminate\Support\HtmlString(
                            '<p class="text-sm italic text-gray-400 dark:text-gray-500">' . __('Task start and end times are recorded automatically when the technician submits their work through the mobile app.') . '</p>'
                        ))
                        ->columnSpanFull(),
                ])
                ->columns(['default' => 2, 'lg' => 4])
                ->visibleOn('edit'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('product_type')
                    ->label(__('Product / Invoice'))
                    ->weight('bold')
                    ->color('primary')
                    ->limit(40)
                    ->tooltip(fn($state) => $state)
                    ->description(fn(Request $record) => $record->invoice_number ? '#' . $record->invoice_number : '—')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label(__('Customer'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('technician.name')
                    ->label(__('Technician'))
                    ->default(__('Unassigned'))
                    ->weight('bold')
                    ->icon(fn($record) => $record->technician_id ? 'heroicon-m-wrench-screwdriver' : null)
                    ->color(fn($record) => $record->technician_id ? null : 'danger'),
                Tables\Columns\TextColumn::make('quantity')
                    ->label(__('Qty'))
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('is_site_ready')
                    ->label(__('Site Ready'))
                    ->formatStateUsing(fn($state) => $state ? __('Ready') : __('Not Ready'))
                    ->badge()
                    ->color(fn($state) => $state ? 'success' : 'warning')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('Status'))
                    ->badge()
                    ->formatStateUsing(fn($state) => $state instanceof \App\Enums\RequestStatus ? $state->label() : \App\Enums\RequestStatus::from($state)->label())
                    ->color(fn($state) => $state instanceof \App\Enums\RequestStatus ? $state->color() : \App\Enums\RequestStatus::from($state)->color()),
                Tables\Columns\TextColumn::make('scheduled_at')->label(__('Scheduled'))->date()->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('created_at')->label(__('Created At'))->since()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('Status'))
                    ->options(collect(RequestStatus::cases())->mapWithKeys(fn($s) => [$s->value => $s->label()])),
                Tables\Filters\TernaryFilter::make('is_site_ready')->label(__('Site Ready')),
                Tables\Filters\SelectFilter::make('technician_id')
                    ->label(__('Technician'))
                    ->relationship('technician', 'name'),
                Tables\Filters\Filter::make('scheduled_at')
                    ->form([
                        Forms\Components\DatePicker::make('scheduled_from')->label(__('From Date')),
                        Forms\Components\DatePicker::make('scheduled_until')->label(__('Until Date')),
                    ])
                    ->columns(2)
                    ->columnSpan(2)
                    ->query(function (Builder $query, array $data) {
                        $query
                            ->when($data['scheduled_from'], fn($q, $d) => $q->whereDate('scheduled_at', '>=', $d))
                            ->when($data['scheduled_until'], fn($q, $d) => $q->whereDate('scheduled_at', '<=', $d));
                    }),
            ])
            ->deferFilters(false)
            ->actions([
                Action::make('assign')
                    ->label(__('Assign'))
                    ->icon('heroicon-o-user-plus')
                    ->color('info')
                    ->visible(fn(Request $record) => !in_array($record->status, [RequestStatus::Completed, RequestStatus::Canceled]))
                    ->fillForm(fn(Request $record) => [
                        'technician_id' => $record->technician_id,
                        'scheduled_at' => $record->scheduled_at?->toDateString(),
                        'end_date' => $record->end_date?->toDateString(),
                    ])
                    ->form([
                        Forms\Components\Select::make('technician_id')
                            ->label(__('Technician'))
                            ->options(User::technicians()->active()->pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                        Forms\Components\DatePicker::make('scheduled_at')
                            ->label(__('Installation Start Date'))
                            ->required()
                            ->minDate(today()),
                        Forms\Components\DatePicker::make('end_date')
                            ->label(__('Installation End Date'))
                            ->required()
                            ->afterOrEqual('scheduled_at'),
                    ])
                    ->action(function (Request $record, array $data) {
                        $timing = [
                            'scheduled_at'       => $data['scheduled_at'],
                            'end_date'           => $data['end_date'],
                            'scheduled_start_at' => $data['scheduled_at'] . ' 08:00:00',
                            'scheduled_end_at'   => $data['end_date'] . ' 17:00:00',
                        ];

                        try {
                            app(RequestService::class)->assignTechnician($record, $data['technician_id'], $timing);
                            Notification::make()->title(__('Technician assigned successfully'))->success()->send();
                        } catch (\Exception $e) {
                            Notification::make()->title(__('Booking Conflict'))->body($e->getMessage())->danger()->send();
                        }
                    }),
                Action::make('updateStatus')
                    ->label(__('Change Status'))
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->modalWidth('sm')
                    ->fillForm(fn(Request $record) => [
                        'status' => $record->status instanceof RequestStatus ? $record->status->value : $record->status,
                    ])
                    ->form([
                        Forms\Components\Select::make('status')
                            ->label(__('New Status'))
                            ->options(collect(RequestStatus::cases())->mapWithKeys(fn($s) => [$s->value => $s->label()]))
                            ->required(),
                        Forms\Components\Toggle::make('notify_client')
                            ->label(__('Notify client'))
                            ->default(true),
                    ])
                    ->action(function (Request $record, array $data) {
                        $newStatus = RequestStatus::from($data['status']);
                        if ($data['notify_client']) {
                            app(RequestService::class)->updateStatus($record, $newStatus, auth()->user());
                        } else {
                            $updateData = ['status' => $newStatus->value];
                            if ($newStatus === RequestStatus::Completed) {
                                $updateData['completed_at'] = now();
                            }
                            $record->update($updateData);
                        }
                        Notification::make()->title(__('Status updated to :status', ['status' => $newStatus->label()]))->success()->send();
                    }),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->deferColumnManager(false)
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->recordUrl(fn($record) => static::getUrl('view', ['record' => $record->id]))
            ->emptyStateHeading(__('No installation requests yet'))
            ->emptyStateIcon('heroicon-o-home-modern')
            ->paginationPageOptions([10, 25, 50]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([

            // ── Top row: Status · Invoice · Scheduling ───────────────────────
            \Filament\Schemas\Components\Grid::make(3)->schema([

                Section::make()->schema([
                    \Filament\Infolists\Components\TextEntry::make('status')
                        ->label(__('Status'))
                        ->badge()
                        ->formatStateUsing(fn($state) => $state instanceof \App\Enums\RequestStatus ? $state->label() : \App\Enums\RequestStatus::from($state)->label())
                        ->color(fn($state) => $state instanceof \App\Enums\RequestStatus ? $state->color() : \App\Enums\RequestStatus::from($state)->color()),
                    \Filament\Infolists\Components\TextEntry::make('id')
                        ->label(__('Request ID'))
                        ->formatStateUsing(fn($state) => '#' . $state),
                ])->columns(2),

                Section::make()->schema([
                    \Filament\Infolists\Components\TextEntry::make('invoice_number')
                        ->label(__('Invoice Number'))
                        ->formatStateUsing(fn($state) => $state ?: '—')
                        ->copyable()
                        ->default(__('Not assigned')),
                    \Filament\Infolists\Components\TextEntry::make('product_type')
                        ->label(__('Product')),
                ])->columns(2),

                Section::make()->schema([
                    \Filament\Infolists\Components\TextEntry::make('scheduled_at')
                        ->label(__('Scheduled Date'))
                        ->date()
                        ->icon('heroicon-m-calendar'),
                    \Filament\Infolists\Components\TextEntry::make('created_at')
                        ->label(__('Submitted'))
                        ->since()
                        ->icon('heroicon-m-clock'),
                ])->columns(2),

            ]),

            // ── Customer & Technician ────────────────────────────────────────
            \Filament\Schemas\Components\Grid::make(2)->schema([

                Section::make(__('Customer'))
                    ->icon('heroicon-m-user-circle')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('user.name')
                            ->label(__('Name'))
                            ->icon('heroicon-m-user'),
                        \Filament\Infolists\Components\TextEntry::make('user.phone')
                            ->label(__('Phone'))
                            ->icon('heroicon-m-phone')
                            ->copyable(),
                    ])->columns(2),

                Section::make(__('Assigned Technician'))
                    ->icon('heroicon-m-wrench-screwdriver')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('technician.name')
                            ->label(__('Name'))
                            ->icon('heroicon-m-user')
                            ->default(__('Unassigned')),
                        \Filament\Infolists\Components\TextEntry::make('technician.phone')
                            ->label(__('Phone'))
                            ->icon('heroicon-m-phone')
                            ->copyable()
                            ->default('—'),
                    ])->columns(2),

            ]),

            // ── Location ─────────────────────────────────────────────────────
            Section::make(__('Location'))
                ->icon('heroicon-m-map-pin')
                ->schema([
                    \Filament\Infolists\Components\TextEntry::make('address')
                        ->label(__('Address'))
                        ->columnSpanFull(),
                    \Filament\Infolists\Components\TextEntry::make('latitude')
                        ->label(__('Coordinates'))
                        ->formatStateUsing(fn($state, $record) => "{$record->latitude}, {$record->longitude}")
                        ->url(fn($record) => "https://www.google.com/maps?q={$record->latitude},{$record->longitude}")
                        ->openUrlInNewTab()
                        ->icon('heroicon-m-arrow-top-right-on-square')
                        ->iconPosition(\Filament\Support\Enums\IconPosition::After)
                        ->color('info'),
                    \Filament\Infolists\Components\TextEntry::make('end_date')
                        ->label(__('End Date'))
                        ->formatStateUsing(fn($state) => $state?->format('M d, Y') ?? '—'),
                ])->columns(2),

            // ── Installation Details & Timeline ──────────────────────────────
            \Filament\Schemas\Components\Grid::make(2)->schema([

                Section::make(__('Installation Details'))
                    ->icon('heroicon-m-home-modern')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('quantity')
                            ->label(__('Quantity')),
                        \Filament\Infolists\Components\IconEntry::make('is_site_ready')
                            ->label(__('Site Ready'))
                            ->boolean(),
                        \Filament\Infolists\Components\TextEntry::make('notes')
                            ->label(__('Notes'))
                            ->default(__('No notes provided'))
                            ->columnSpanFull(),
                    ])->columns(2),

                Section::make(__('Timeline'))
                    ->icon('heroicon-m-bars-3-bottom-left')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('technician_accepted_at')
                            ->label(__('Accepted by Technician'))
                            ->formatStateUsing(fn($state) => $state?->format('M d, Y H:i') ?? '—'),
                        \Filament\Infolists\Components\TextEntry::make('task_start_time')
                            ->label(__('Work Started'))
                            ->formatStateUsing(fn($state) => $state?->format('M d, Y H:i') ?? '—'),
                        \Filament\Infolists\Components\TextEntry::make('task_end_time')
                            ->label(__('Work Ended'))
                            ->formatStateUsing(fn($state) => $state?->format('M d, Y H:i') ?? '—'),
                        \Filament\Infolists\Components\TextEntry::make('completed_at')
                            ->label(__('Completed At'))
                            ->formatStateUsing(fn($state) => $state?->format('M d, Y H:i') ?? '—'),
                    ])->columns(2),

            ]),

            // ── Customer Rating (only when rated) ────────────────────────────
            Section::make(__('Customer Rating'))
                ->icon('heroicon-m-star')
                ->schema([
                    \Filament\Infolists\Components\TextEntry::make('rating.product_rating')
                        ->label(__('Product Rating'))
                        ->badge()
                        ->color('warning'),
                    \Filament\Infolists\Components\TextEntry::make('rating.service_rating')
                        ->label(__('Service Rating'))
                        ->badge()
                        ->color('warning'),
                    \Filament\Infolists\Components\TextEntry::make('rating.how_found_us')
                        ->label(__('How They Found Us')),
                    \Filament\Infolists\Components\TextEntry::make('rating.customer_notes')
                        ->label(__('Customer Notes'))
                        ->columnSpanFull()
                        ->default('—'),
                ])
                ->columns(3)
                ->visible(fn($record) => $record->hasRating()),

            // ── Activity Log ─────────────────────────────────────────────────
            Section::make(__('Activity Log'))
                ->icon('heroicon-m-clipboard-document-list')
                ->schema([
                    \Filament\Infolists\Components\RepeatableEntry::make('activities')
                        ->label('')
                        ->schema([
                            \Filament\Infolists\Components\TextEntry::make('created_at')
                                ->label(__('When'))
                                ->since()
                                ->columnSpan(1),
                            \Filament\Infolists\Components\TextEntry::make('causer.name')
                                ->label(__('By'))
                                ->default(__('System'))
                                ->columnSpan(1),
                            \Filament\Infolists\Components\TextEntry::make('description')
                                ->label(__('Action'))
                                ->badge()
                                ->color('gray')
                                ->columnSpan(1),
                            \Filament\Infolists\Components\TextEntry::make('properties')
                                ->label(__('Changes'))
                                ->formatStateUsing(fn($state) => !empty($state['attributes'])
                                    ? collect($state['attributes'])->map(fn($v, $k) => "{$k}: {$v}")->implode(', ')
                                    : '—'
                                )
                                ->columnSpan(1),
                        ])
                        ->columns(4),
                ])
                ->collapsible()
                ->collapsed(),

        ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListInstallationRequests::route('/'),
            'create' => Pages\CreateInstallationRequest::route('/create'),
            'view'   => Pages\ViewInstallationRequest::route('/{record}'),
            'edit'   => Pages\EditInstallationRequest::route('/{record}/edit'),
        ];
    }
}
