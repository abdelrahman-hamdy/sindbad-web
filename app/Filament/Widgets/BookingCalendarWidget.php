<?php

namespace App\Filament\Widgets;

use App\Enums\RequestStatus;
use App\Enums\RequestType;
use App\Models\Request;
use App\Services\RequestService;
use Filament\Notifications\Notification;
use Saade\FilamentFullCalendar\Data\EventData;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;

class BookingCalendarWidget extends FullCalendarWidget
{
    // model = null → disables the auto-modal on event click
    public \Illuminate\Database\Eloquent\Model|string|null $model = null;

    public function config(): array
    {
        return [
            'initialView'    => 'timeGridDay',
            'editable'       => true,
            'slotMinTime'    => '07:00:00',
            'slotMaxTime'    => '20:00:00',
            'allDaySlot'     => false,
            'nowIndicator'   => true,
            'slotDuration'   => '00:30:00',
            'headerToolbar'  => [
                'left'   => 'prev,next today',
                'center' => 'title',
                'right'  => 'timeGridDay,timeGridWeek,dayGridMonth',
            ],
            'eventTimeFormat' => [
                'hour'   => '2-digit',
                'minute' => '2-digit',
                'hour12' => false,
            ],
        ];
    }

    public function fetchEvents(array $info): array
    {
        $requests = Request::with(['user:id,name', 'technician:id,name'])
            ->where(function ($q) use ($info) {
                $q->whereBetween('scheduled_start_at', [$info['start'], $info['end']])
                    ->orWhere(function ($q2) use ($info) {
                        $q2->whereNull('scheduled_start_at')
                            ->whereBetween('scheduled_at', [$info['start'], $info['end']]);
                    });
            })
            ->whereNotIn('status', [RequestStatus::Canceled->value, RequestStatus::Completed->value])
            ->get();

        return $requests->map(function (Request $request) {
            $start = $request->scheduled_start_at ?? $request->scheduled_at;
            $end   = $request->scheduled_end_at
                ?? ($request->scheduled_start_at?->copy()->addHours(2))
                ?? $request->scheduled_at?->copy()->addHours(2);

            $typeIcon    = $request->type === RequestType::Installation ? '🏠' : '🔧';
            $techName    = $request->technician?->name ?? __('Unassigned');
            $customerName = $request->user?->name ?? '—';

            $title = "{$typeIcon} #{$request->id} · {$customerName}\n📍 {$techName}";

            return EventData::make()
                ->id($request->id)
                ->title($title)
                ->start($start)
                ->end($end)
                ->backgroundColor($this->statusToColor($request->status))
                ->borderColor($this->statusToColor($request->status))
                ->extendedProps([
                    'status'       => $request->status->value,
                    'type'         => $request->type->value,
                    'technician'   => $techName,
                    'customer'     => $customerName,
                    'request_id'   => $request->id,
                    'is_unassigned' => is_null($request->technician_id),
                ])
                ->toArray();
        })->toArray();
    }

    /**
     * Called when an event is clicked — redirect to the request view page.
     */
    public function onEventClick(array $event): void
    {
        $requestId = $event['id'] ?? null;
        if (! $requestId) {
            return;
        }

        $request = Request::find($requestId);
        if (! $request) {
            return;
        }

        $url = $request->isService()
            ? \App\Filament\Resources\ServiceRequestResource::getUrl('view', ['record' => $requestId])
            : \App\Filament\Resources\InstallationRequestResource::getUrl('view', ['record' => $requestId]);

        $this->redirect($url);
    }

    /**
     * Called when an event is dragged to a new time slot.
     * Returns true to revert the drag, false to keep the new position.
     */
    public function onEventDrop(array $event, array $oldEvent, array $relatedEvents, array $delta, ?array $oldResource, ?array $newResource): bool
    {
        try {
            $request = Request::findOrFail($event['id']);

            $newStart = \Carbon\Carbon::parse($event['start']);
            $newEnd   = isset($event['end'])
                ? \Carbon\Carbon::parse($event['end'])
                : $newStart->copy()->addHours(2);

            $timing = [
                'scheduled_start_at' => $newStart,
                'scheduled_end_at'   => $newEnd,
                'scheduled_at'       => $newStart->toDateString(),
            ];

            if ($request->technician_id) {
                app(RequestService::class)->assignTechnician($request, $request->technician_id, $timing);
            } else {
                $request->update($timing);
            }

            $this->dispatch('calendar-updated');

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

            return true;
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
            RequestStatus::Pending    => '#f59e0b',
            RequestStatus::Assigned   => '#3b82f6',
            RequestStatus::OnWay      => '#8b5cf6',
            RequestStatus::InProgress => '#06b6d4',
            RequestStatus::Completed  => '#22c55e',
            RequestStatus::Canceled   => '#ef4444',
        };
    }
}
