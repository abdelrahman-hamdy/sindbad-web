<?php

namespace App\Filament\Pages;

use App\Enums\RequestStatus;
use App\Filament\Resources\InstallationRequestResource;
use App\Filament\Resources\ServiceRequestResource;
use App\Models\Request;
use Carbon\Carbon;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class CalendarPage extends Page
{
    protected string $view = 'filament.pages.calendar';

    public static function getNavigationLabel(): string { return __('Calendar'); }

    public static function getNavigationGroup(): ?string { return __('Analytics'); }

    public static function getNavigationIcon(): string|\BackedEnum|\Illuminate\Contracts\Support\Htmlable|null
    {
        return 'heroicon-o-calendar-days';
    }

    public static function getNavigationSort(): ?int
    {
        return 13;
    }

    public function getTitle(): string
    {
        return __('Request Calendar');
    }

    public string $currentMonth;
    public ?string $selectedDate = null;
    public array $calendarData = [];
    public array $dayRequests = [];

    public function mount(): void
    {
        $this->currentMonth = now()->format('Y-m');
        $this->loadCalendar();
    }

    public function loadCalendar(): void
    {
        [$year, $month] = explode('-', $this->currentMonth);

        $rows = Request::selectRaw('
            DATE(scheduled_at) as day,
            COUNT(*) as total,
            SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status IN (?,?,?) THEN 1 ELSE 0 END) as active,
            SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as canceled
        ', [
            RequestStatus::Pending->value,
            RequestStatus::Assigned->value,
            RequestStatus::OnWay->value,
            RequestStatus::InProgress->value,
            RequestStatus::Completed->value,
            RequestStatus::Canceled->value,
        ])
            ->whereYear('scheduled_at', $year)
            ->whereMonth('scheduled_at', $month)
            ->groupBy('day')
            ->get();

        $this->calendarData = [];
        foreach ($rows as $row) {
            $this->calendarData[$row->day] = [
                'total'     => (int) $row->total,
                'pending'   => (int) $row->pending,
                'active'    => (int) $row->active,
                'completed' => (int) $row->completed,
                'canceled'  => (int) $row->canceled,
            ];
        }
    }

    public function selectDate(string $date): void
    {
        $this->selectedDate = $date;
        $this->dayRequests = Request::with(['user:id,name', 'technician:id,name'])
            ->whereDate('scheduled_at', $date)
            ->orderBy('scheduled_at')
            ->get()
            ->map(fn($req) => [
                'id'             => $req->id,
                'invoice_number' => $req->invoice_number,
                'type'           => $req->type->value,
                'customer'       => $req->user?->name ?? '-',
                'technician'     => $req->technician?->name ?? __('Unassigned'),
                'status'         => $req->status->value,
                'status_label'   => $req->status->label(),
                'scheduled_time' => $req->scheduled_at?->format('H:i'),
                'url'            => $req->type->value === 'service'
                    ? ServiceRequestResource::getUrl('view', ['record' => $req->id])
                    : InstallationRequestResource::getUrl('view', ['record' => $req->id]),
            ])
            ->toArray();
    }

    public function previousMonth(): void
    {
        $this->currentMonth = Carbon::parse($this->currentMonth . '-01')
            ->subMonth()
            ->format('Y-m');
        $this->selectedDate = null;
        $this->dayRequests = [];
        $this->loadCalendar();
    }

    public function nextMonth(): void
    {
        $this->currentMonth = Carbon::parse($this->currentMonth . '-01')
            ->addMonth()
            ->format('Y-m');
        $this->selectedDate = null;
        $this->dayRequests = [];
        $this->loadCalendar();
    }

    public function getCalendarDays(): array
    {
        $start = Carbon::parse($this->currentMonth . '-01');
        $end   = $start->copy()->endOfMonth();

        $firstDayOfWeek = $start->dayOfWeek === 0 ? 6 : $start->dayOfWeek - 1;
        $days = [];

        for ($i = 0; $i < $firstDayOfWeek; $i++) {
            $days[] = null;
        }

        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $days[] = $d->format('Y-m-d');
        }

        return $days;
    }
}
