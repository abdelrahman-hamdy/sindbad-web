<?php

namespace App\Filament\Pages;

use App\Enums\RequestStatus;
use App\Enums\RequestType;
use App\Models\Request;
use App\Models\User;
use App\Services\BookingService;
use Filament\Pages\Page;

class BookingCalendarPage extends Page
{
    protected string $view = 'filament.pages.booking-calendar';

    // Sidebar data — loaded on mount and refreshable
    public array $pendingRequests = [];
    public array $techniciansToday = [];

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

    public function loadSidebarData(): void
    {
        // Pending / unassigned requests
        $this->pendingRequests = Request::with('user:id,name')
            ->whereNull('technician_id')
            ->whereNotIn('status', [RequestStatus::Completed->value, RequestStatus::Canceled->value])
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn(Request $r) => [
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

        // Active technicians + today's load
        $today          = now();
        $bookingService = app(BookingService::class);
        $maxPerDay      = (int) \App\Models\AppSetting::get('booking_max_service_per_tech_per_day', '4');

        $this->techniciansToday = User::technicians()
            ->active()
            ->withCount([
                'assignedRequests as today_service_count' => fn($q) => $q
                    ->where('type', RequestType::Service->value)
                    ->whereIn('status', [RequestStatus::Assigned->value, RequestStatus::OnWay->value, RequestStatus::InProgress->value])
                    ->whereDate('scheduled_start_at', $today->toDateString()),
                'assignedRequests as today_total_count' => fn($q) => $q
                    ->whereIn('status', [RequestStatus::Assigned->value, RequestStatus::OnWay->value, RequestStatus::InProgress->value])
                    ->where(function ($q2) use ($today) {
                        $q2->whereDate('scheduled_start_at', $today->toDateString())
                            ->orWhere(function ($q3) use ($today) {
                                $q3->whereNull('scheduled_start_at')
                                    ->whereDate('scheduled_at', $today->toDateString());
                            });
                    }),
            ])
            ->get()
            ->map(fn(User $tech) => [
                'id'              => $tech->id,
                'name'            => $tech->name,
                'today_count'     => $tech->today_total_count,
                'service_count'   => $tech->today_service_count,
                'on_holiday'      => $bookingService->isTechnicianOnHoliday($tech->id, $today),
                'service_full'    => $tech->today_service_count >= $maxPerDay,
            ])
            ->toArray();
    }
}
