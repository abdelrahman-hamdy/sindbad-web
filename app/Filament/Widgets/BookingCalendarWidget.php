<?php

namespace App\Filament\Widgets;

use App\Enums\RequestStatus;
use App\Enums\RequestType;
use App\Models\Request;
use App\Models\User;
use App\Services\RequestService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Forms\Components\Placeholder;
use Livewire\Attributes\On;
use Saade\FilamentFullCalendar\Data\EventData;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;

class BookingCalendarWidget extends FullCalendarWidget
{
    // model = null → disables the auto-modal on event click
    public \Illuminate\Database\Eloquent\Model|string|null $model = null;

    public ?string $filterTechnician = null;
    public ?string $filterType = null;
    public ?int $selectedRequestId = null;

    public function config(): array
    {
        return [
            'initialView'  => 'dayGridMonth',
            'editable'     => true,
            'nowIndicator' => true,
            'headerToolbar' => [
                'left'   => 'prev,next today',
                'center' => 'title',
                'right'  => '',
            ],
            'eventDisplay' => 'block',
        ];
    }

    #[On('booking-filters-updated')]
    public function applyFilters(?string $technician, ?string $type): void
    {
        $this->filterTechnician = $technician ?: null;
        $this->filterType       = $type ?: null;
        $this->dispatch('filament-fullcalendar--refresh');
    }

    public function fetchEvents(array $info): array
    {
        $rangeStart = Carbon::parse($info['start'])->toDateString();
        $rangeEnd   = Carbon::parse($info['end'])->toDateString();

        $query = Request::with(['user:id,name', 'technician:id,name'])
            ->where(function ($q) use ($rangeStart, $rangeEnd) {
                // Service requests: scheduled_at falls in range
                $q->where(function ($q2) use ($rangeStart, $rangeEnd) {
                    $q2->where('type', RequestType::Service->value)
                        ->whereBetween('scheduled_at', [$rangeStart, $rangeEnd]);
                })
                // Installation requests: date range overlaps visible range
                ->orWhere(function ($q2) use ($rangeStart, $rangeEnd) {
                    $q2->where('type', RequestType::Installation->value)
                        ->where('scheduled_at', '<=', $rangeEnd)
                        ->where(function ($q3) use ($rangeStart) {
                            $q3->whereNull('end_date')
                                ->orWhere('end_date', '>=', $rangeStart);
                        });
                });
            })
            ->whereNotIn('status', [RequestStatus::Canceled->value, RequestStatus::Completed->value]);

        if ($this->filterTechnician) {
            $query->where('technician_id', $this->filterTechnician);
        }

        if ($this->filterType) {
            $query->where('type', $this->filterType);
        }

        return $query->get()->map(function (Request $r) {
            $isInstallation = $r->type === RequestType::Installation;
            $startDate      = $r->scheduled_at?->toDateString();
            // FullCalendar end date is exclusive, so add 1 day for multi-day installations
            $endDate = $isInstallation
                ? ($r->end_date?->copy()->addDay()->toDateString() ?? $startDate)
                : null;

            $typeIcon     = $isInstallation ? '🏠' : '🔧';
            $techName     = $r->technician?->name ?? __('Unassigned');
            $customerName = $r->user?->name ?? '—';
            $title        = "{$typeIcon} #{$r->id} · {$customerName}";
            if ($r->technician_id) {
                $title .= "\n📍 {$techName}";
            }

            $event = EventData::make()
                ->id($r->id)
                ->title($title)
                ->start($startDate)
                ->backgroundColor($this->statusToColor($r->status))
                ->borderColor($this->statusToColor($r->status))
                ->extendedProps([
                    'status'        => $r->status->value,
                    'type'          => $r->type->value,
                    'technician'    => $techName,
                    'customer'      => $customerName,
                    'is_unassigned' => is_null($r->technician_id),
                ]);

            if ($endDate) {
                $event->end($endDate);
            }

            return $event->toArray();
        })->toArray();
    }

    public function onEventClick(array $event): void
    {
        $this->selectedRequestId = (int) ($event['id'] ?? 0);
        if ($this->selectedRequestId) {
            $this->mountAction('viewRequest');
        }
    }

    public function viewRequestAction(): Action
    {
        return Action::make('viewRequest')
            ->modalHeading(fn () => __('Request') . ' #' . $this->selectedRequestId)
            ->fillForm(function () {
                $r = Request::with(['user', 'technician'])->find($this->selectedRequestId);

                return [
                    'info_customer'  => $r?->user?->name ?? '—',
                    'info_type'      => $r?->type?->label() ?? '—',
                    'info_status'    => $r?->status?->label() ?? '—',
                    'info_address'   => $r?->address ?? '—',
                    'info_date'      => $r?->scheduled_at?->format('M d, Y') ?? '—',
                    'technician_id'  => $r?->technician_id,
                    'scheduled_at'   => $r?->scheduled_at?->toDateString(),
                    'end_date'       => $r?->end_date?->toDateString(),
                    'status'         => $r?->status?->value,
                    'is_installation' => $r?->type === RequestType::Installation,
                ];
            })
            ->schema([
                Section::make(__('Request Info'))->schema([
                    Placeholder::make('info_customer')
                        ->label(__('Customer'))
                        ->content(fn (Get $get) => $get('info_customer')),
                    Placeholder::make('info_type')
                        ->label(__('Type'))
                        ->content(fn (Get $get) => $get('info_type')),
                    Placeholder::make('info_status')
                        ->label(__('Current Status'))
                        ->content(fn (Get $get) => $get('info_status')),
                    Placeholder::make('info_address')
                        ->label(__('Address'))
                        ->content(fn (Get $get) => $get('info_address'))
                        ->columnSpanFull(),
                ])->columns(3),

                Section::make(__('Edit Assignment'))->schema([
                    Select::make('technician_id')
                        ->label(__('Technician'))
                        ->options(User::technicians()->active()->pluck('name', 'id'))
                        ->searchable(),
                    Select::make('status')
                        ->label(__('Status'))
                        ->options(
                            collect(RequestStatus::cases())
                                ->mapWithKeys(fn ($s) => [$s->value => $s->label()])
                        )
                        ->required(),
                    DatePicker::make('scheduled_at')
                        ->label(__('Scheduled Date'))
                        ->required(),
                    DatePicker::make('end_date')
                        ->label(__('End Date'))
                        // Use form state instead of a separate DB query
                        ->visible(fn (Get $get) => (bool) $get('is_installation'))
                        ->afterOrEqual('scheduled_at'),
                ])->columns(2),
            ])
            ->action(function (array $data) {
                $request = Request::findOrFail($this->selectedRequestId);

                $timing = array_filter([
                    'scheduled_at' => $data['scheduled_at'] ?? null,
                    'end_date'     => $data['end_date'] ?? null,
                ]);

                if (! empty($data['technician_id']) && $data['technician_id'] != $request->technician_id) {
                    app(RequestService::class)->assignTechnician(
                        $request,
                        (int) $data['technician_id'],
                        array_merge($timing, ['status' => $data['status']])
                    );
                } else {
                    $request->update(array_merge($timing, ['status' => $data['status']]));
                }

                Notification::make()->title(__('Request updated'))->success()->send();
                $this->dispatch('filament-fullcalendar--refresh');
            });
    }

    public function onEventDrop(array $event, array $oldEvent, array $relatedEvents, array $delta, ?array $oldResource, ?array $newResource): bool
    {
        try {
            $request  = Request::findOrFail($event['id']);
            $newStart = Carbon::parse($event['start'])->startOfDay();
            $oldStart = Carbon::parse($oldEvent['start'])->startOfDay();
            $deltaDays = (int) $oldStart->diffInDays($newStart, false);

            $update = ['scheduled_at' => $newStart->toDateString()];

            // For installations: shift end_date by the same delta
            if ($request->type === RequestType::Installation && $request->end_date) {
                $update['end_date'] = $request->end_date->copy()->addDays($deltaDays)->toDateString();
            }

            if ($request->technician_id) {
                app(RequestService::class)->assignTechnician($request, $request->technician_id, $update);
            } else {
                $request->update($update);
            }

            Notification::make()->title(__('Request rescheduled'))->success()->send();

            return false; // keep new position
        } catch (\Exception $e) {
            Notification::make()
                ->title(__('Cannot reschedule'))
                ->body($e->getMessage())
                ->danger()
                ->send();

            return true; // revert drag
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
