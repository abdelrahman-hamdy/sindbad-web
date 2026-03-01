<?php

namespace App\Filament\Pages;

use App\Enums\RequestStatus;
use App\Enums\RequestType;
use App\Models\AppSetting;
use App\Models\Request;
use App\Models\User;
use App\Services\BookingService;
use App\Services\RequestService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\HtmlString;

class BookingCalendarPage extends Page
{
    protected string $view = 'filament.pages.booking-calendar';

    // Sidebar data — loaded on mount and refreshable
    public array $pendingRequests = [];
    public array $techniciansToday = [];

    // Filter state (synced to widget via event)
    public string $filterTechnician = '';
    public string $filterType = '';

    // Schedule modal state
    public ?int $schedulingRequestId = null;

    public static function getNavigationLabel(): string { return __('Booking Calendar'); }
    public static function getNavigationGroup(): ?string { return __('Requests'); }
    public static function getNavigationIcon(): string|\BackedEnum|\Illuminate\Contracts\Support\Htmlable|null
    {
        return 'heroicon-o-calendar-days';
    }
    public static function getNavigationSort(): ?int { return 5; }

    public function getTitle(): string { return __('Booking Calendar'); }

    public function mount(): void
    {
        $this->loadSidebarData();
    }

    public function updatedFilterTechnician(): void
    {
        Log::info('[BookingCalendarPage] updatedFilterTechnician', ['value' => $this->filterTechnician]);
        $this->dispatch('booking-filters-updated',
            technician: $this->filterTechnician ?: null,
            type: $this->filterType ?: null,
        )->to(\App\Filament\Widgets\BookingCalendarWidget::class);
    }

    public function updatedFilterType(): void
    {
        $this->dispatch('booking-filters-updated',
            technician: $this->filterTechnician ?: null,
            type: $this->filterType ?: null,
        )->to(\App\Filament\Widgets\BookingCalendarWidget::class);
    }

    public function openScheduleModal(int $id): void
    {
        Log::info('[BookingCalendarPage] openScheduleModal', ['id' => $id]);
        $this->schedulingRequestId = $id;
        $this->mountAction('scheduleRequest');
    }

    public function scheduleRequestAction(): Action
    {
        return Action::make('scheduleRequest')
            ->modalHeading(fn () => __('Schedule Request') . ' #' . $this->schedulingRequestId)
            ->modalWidth('2xl')
            ->fillForm(function () {
                $r = Request::with('user')->find($this->schedulingRequestId);

                return [
                    'req_customer'   => $r?->user?->name ?? '—',
                    'req_type'       => $r?->type?->label() ?? '—',
                    'req_address'    => $r?->address ?? '—',
                    'req_pref_date'  => $r?->scheduled_at?->format('M d, Y') ?? __('No preference'),
                    'req_is_install'      => $r?->type === RequestType::Installation,
                    'technician_id'       => null,
                    'scheduled_at'        => null,
                    'install_scheduled_at' => null,
                    'end_date'            => null,
                ];
            })
            ->schema([
                // Request summary (read-only)
                Section::make(__('Request Details'))->schema([
                    Placeholder::make('req_customer')
                        ->label(__('Customer'))
                        ->content(fn (Get $get) => $get('req_customer')),
                    Placeholder::make('req_type')
                        ->label(__('Type'))
                        ->content(fn (Get $get) => $get('req_type')),
                    Placeholder::make('req_pref_date')
                        ->label(__('Preferred Date'))
                        ->content(fn (Get $get) => $get('req_pref_date')),
                    Placeholder::make('req_address')
                        ->label(__('Address'))
                        ->content(fn (Get $get) => $get('req_address'))
                        ->columnSpanFull(),
                ])->columns(3),

                // Technician selection
                Select::make('technician_id')
                    ->label(__('Assign Technician'))
                    ->options(User::technicians()->active()->pluck('name', 'id'))
                    ->searchable()
                    ->required()
                    ->live(),

                // 14-day workload preview (reactive on technician selection)
                Placeholder::make('workload_preview')
                    ->label(__('Technician Schedule — Next 14 Days'))
                    ->content(function (Get $get) {
                        $techId = $get('technician_id');
                        if (! $techId) {
                            return new HtmlString(
                                '<p class="text-sm text-gray-400 italic">' .
                                __('Select a technician to see their availability') .
                                '</p>'
                            );
                        }

                        $days      = app(BookingService::class)->getTechnicianLoad14Days((int) $techId);
                        $maxPerDay = (int) AppSetting::get('booking_max_service_per_tech_per_day', 4);

                        $cells = collect($days)->map(function ($d) use ($maxPerDay) {
                            $bg = $d['on_holiday'] || ! $d['is_working']
                                ? 'bg-gray-100 dark:bg-white/5 text-gray-400'
                                : ($d['count'] >= $maxPerDay
                                    ? 'bg-red-100 dark:bg-red-500/10 text-red-700'
                                    : ($d['count'] >= $maxPerDay - 1
                                        ? 'bg-amber-100 dark:bg-amber-500/10 text-amber-700'
                                        : 'bg-green-100 dark:bg-green-500/10 text-green-700'));

                            $label = $d['on_holiday']
                                ? '🏖️'
                                : (! $d['is_working'] ? '—' : $d['count'] . '/' . $maxPerDay);

                            return "<div class='rounded p-1.5 text-center {$bg}'>
                                <div class='text-xs font-bold'>{$d['day']}</div>
                                <div class='text-xs'>{$d['date']}</div>
                                <div class='text-xs font-semibold mt-0.5'>{$label}</div>
                            </div>";
                        })->implode('');

                        return new HtmlString(
                            "<div class='grid grid-cols-7 gap-1 text-xs'>{$cells}</div>" .
                            "<p class='text-xs text-gray-400 mt-1'>🟢 " . __('Free') . " &nbsp; 🟡 " . __('Partial') . " &nbsp; 🔴 " . __('Full') . " &nbsp; — " . __('Off/Holiday') . '</p>'
                        );
                    })
                    ->live()
                    ->columnSpanFull(),

                // Date field for service requests
                DatePicker::make('scheduled_at')
                    ->label(__('Schedule Date'))
                    ->required()
                    ->minDate(today())
                    ->visible(fn (Get $get) => ! $get('req_is_install')),

                // Date fields for installation requests
                DatePicker::make('install_scheduled_at')
                    ->label(__('Installation Start Date'))
                    ->required()
                    ->minDate(today())
                    ->visible(fn (Get $get) => (bool) $get('req_is_install')),

                DatePicker::make('end_date')
                    ->label(__('Installation End Date'))
                    ->required()
                    ->afterOrEqual('install_scheduled_at')
                    ->visible(fn (Get $get) => (bool) $get('req_is_install')),
            ])
            ->action(function (array $data) {
                $request = Request::findOrFail($this->schedulingRequestId);

                // Resolve scheduled_at: use install_scheduled_at for installations
                $scheduledAt = $data['scheduled_at'] ?? $data['install_scheduled_at'] ?? null;

                $timing = array_filter([
                    'scheduled_at' => $scheduledAt,
                    'end_date'     => $data['end_date'] ?? null,
                ]);

                app(RequestService::class)->assignTechnician($request, (int) $data['technician_id'], $timing);

                Notification::make()->title(__('Request scheduled successfully'))->success()->send();
                $this->loadSidebarData();
                // Dispatch browser event — FullCalendar JS listens on window for this
                $this->dispatch('filament-fullcalendar--refresh');
            });
    }

    public function loadSidebarData(): void
    {
        // Pending / unassigned requests
        $this->pendingRequests = Request::with('user:id,name')
            ->whereNull('technician_id')
            ->whereNotIn('status', [RequestStatus::Completed->value, RequestStatus::Canceled->value])
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn (Request $r) => [
                'id'           => $r->id,
                'customer'     => $r->user?->name ?? '—',
                'type'         => $r->type->value,
                'type_label'   => $r->type->label(),
                'status'       => $r->status->value,
                'status_label' => $r->status->label(),
                'date'         => $r->scheduled_at?->format('M d') ?? __('No date'),
                'view_url'     => $r->isService()
                    ? \App\Filament\Resources\ServiceRequestResource::getUrl('view', ['record' => $r->id])
                    : \App\Filament\Resources\InstallationRequestResource::getUrl('view', ['record' => $r->id]),
            ])
            ->toArray();

        // Active technicians — today's request count by scheduled_at date
        $today          = now();
        $bookingService = app(BookingService::class);
        $maxPerDay      = (int) AppSetting::get('booking_max_service_per_tech_per_day', '4');

        $this->techniciansToday = User::technicians()
            ->active()
            ->withCount([
                'assignedRequests as today_count' => fn ($q) => $q
                    ->whereIn('status', [
                        RequestStatus::Assigned->value,
                        RequestStatus::OnWay->value,
                        RequestStatus::InProgress->value,
                    ])
                    ->whereDate('scheduled_at', $today->toDateString()),
                'assignedRequests as today_service_count' => fn ($q) => $q
                    ->where('type', RequestType::Service->value)
                    ->whereIn('status', [
                        RequestStatus::Assigned->value,
                        RequestStatus::OnWay->value,
                        RequestStatus::InProgress->value,
                    ])
                    ->whereDate('scheduled_at', $today->toDateString()),
            ])
            ->get()
            ->map(fn (User $tech) => [
                'id'           => $tech->id,
                'name'         => $tech->name,
                'today_count'  => $tech->today_count,
                'on_holiday'   => $bookingService->isTechnicianOnHoliday($tech->id, $today),
                'service_full' => $tech->today_service_count >= $maxPerDay,
            ])
            ->toArray();
    }
}
