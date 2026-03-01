<?php

namespace App\Filament\Widgets;

use App\Enums\RequestStatus;
use App\Models\Request;
use App\Models\User;
use App\Services\RequestService;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Saade\FilamentFullCalendar\Data\EventData;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;

class BookingCalendarWidget extends FullCalendarWidget
{
    public Model|string|null $model = Request::class;

    public function config(): array
    {
        return [
            'initialView' => 'timeGridDay',
            'editable' => true,
            'eventResourceEditable' => true,
            'slotMinTime' => '07:00:00',
            'slotMaxTime' => '20:00:00',
            'allDaySlot' => false,
            'nowIndicator' => true,
            'slotDuration' => '00:30:00',
            'headerToolbar' => [
                'left' => 'prev,next today',
                'center' => 'title',
                'right' => 'timeGridDay,timeGridWeek,dayGridMonth',
            ],
        ];
    }

    public function fetchEvents(array $info): array
    {
        $requests = Request::with(['user:id,name', 'technician:id,name'])
            ->where(function ($q) use ($info) {
                $q->whereBetween('scheduled_start_at', [$info['start'], $info['end']])
                    ->orWhereBetween('scheduled_at', [$info['start'], $info['end']]);
            })
            ->whereNotIn('status', [RequestStatus::Canceled->value, RequestStatus::Completed->value])
            ->get();

        return $requests->map(function (Request $request) {
            $start = $request->scheduled_start_at ?? $request->scheduled_at;
            $end = $request->scheduled_end_at
                ?? ($request->scheduled_start_at?->copy()->addHours(2))
                ?? $request->scheduled_at?->copy()->endOfDay();

            $customerName = $request->user?->name ?? '-';
            $title = "#{$request->id} - {$customerName}";

            return EventData::make()
                ->id($request->id)
                ->title($title)
                ->start($start)
                ->end($end)
                ->backgroundColor($this->statusToColor($request->status))
                ->borderColor($this->statusToColor($request->status))
                ->extendedProps([
                    'status' => $request->status->value,
                    'type' => $request->type->value,
                    'technician' => $request->technician?->name ?? __('Unassigned'),
                    'technician_id' => $request->technician_id,
                ])
                ->toArray();
        })->toArray();
    }

    public function onEventDrop(array $event, array $oldEvent, array $relatedEvents, array $delta, ?array $oldResource, ?array $newResource): bool
    {
        try {
            $request = Request::findOrFail($event['id']);

            $newStart = \Carbon\Carbon::parse($event['start']);
            $newEnd = isset($event['end']) ? \Carbon\Carbon::parse($event['end']) : $newStart->copy()->addHours(2);

            $timing = [
                'scheduled_start_at' => $newStart,
                'scheduled_end_at' => $newEnd,
                'scheduled_at' => $newStart->toDateString(),
            ];

            if ($request->technician_id) {
                app(RequestService::class)->assignTechnician($request, $request->technician_id, $timing);
            } else {
                $request->update($timing);
            }

            Notification::make()
                ->title(__('Request rescheduled'))
                ->success()
                ->send();

            return false;
        } catch (\Exception $e) {
            Notification::make()
                ->title(__('Cannot reschedule'))
                ->body($e->getMessage())
                ->danger()
                ->send();

            return true; // revert the drop
        }
    }

    protected function headerActions(): array
    {
        return [];
    }

    protected function modalActions(): array
    {
        return [];
    }

    private function statusToColor(RequestStatus $status): string
    {
        return match ($status) {
            RequestStatus::Pending => '#f59e0b',     // amber
            RequestStatus::Assigned => '#3b82f6',    // blue
            RequestStatus::OnWay => '#8b5cf6',       // violet
            RequestStatus::InProgress => '#06b6d4',  // cyan
            RequestStatus::Completed => '#22c55e',   // green
            RequestStatus::Canceled => '#ef4444',    // red
        };
    }
}
