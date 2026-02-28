<?php

namespace App\Livewire;

use App\Enums\RequestStatus;
use App\Models\TechnicianLocation;
use App\Models\User;
use App\Services\TechnicianLocationService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class LiveMapComponent extends Component
{
    public array $technicianLocations = [];

    public function mount(): void
    {
        $this->loadLocations();
    }

    /**
     * Called by the simulation panel — writes to DB and dispatches a lightweight
     * browser event so Alpine updates directly. Does NOT change any public
     * Livewire properties, so no DOM re-render occurs (no Leaflet crash).
     */
    public function simulateStep(int $technicianId, float $lat, float $lng, float $heading, float $speed): void
    {
        $tech = User::find($technicianId);
        if (! $tech) return;

        app(TechnicianLocationService::class)->updateLocation($tech, [
            'latitude'    => $lat,
            'longitude'   => $lng,
            'heading'     => $heading,
            'speed'       => $speed,
            'recorded_at' => now(),
        ]);

        $this->dispatch('techLocationUpdate', location: [
            'technician_id' => $technicianId,
            'name'          => $tech->name,
            'phone'         => $tech->phone,
            'latitude'      => $lat,
            'longitude'     => $lng,
            'heading'       => $heading,
            'speed'         => $speed,
            'is_online'     => true,
            'updated_at'    => now()->toISOString(),
        ]);
    }

    public function simulateOffline(int $technicianId): void
    {
        $tech = User::find($technicianId);
        if (! $tech) return;

        app(TechnicianLocationService::class)->markOffline($tech);

        $this->dispatch('techLocationUpdate', location: [
            'technician_id' => $technicianId,
            'name'          => $tech->name,
            'phone'         => $tech->phone,
            'latitude'      => null,
            'longitude'     => null,
            'is_online'     => false,
            'updated_at'    => now()->toISOString(),
        ]);
    }

    /**
     * Polling fallback when WebSocket is unavailable. Dispatches a browser
     * event instead of updating $technicianLocations, so no DOM re-render.
     */
    public function refreshLocations(): void
    {
        $this->dispatch('locationsRefreshed', locations: $this->buildLocationsArray());
    }

    /**
     * On-demand fetch of the active request for a single technician.
     * Dispatches a browser event with the result so Alpine can update
     * the map marker popup without a full page re-render.
     */
    public function loadTechnicianRequest(int $technicianId): void
    {
        $req = \App\Models\Request::with('user')
            ->where('technician_id', $technicianId)
            ->whereIn('status', [RequestStatus::OnWay->value, RequestStatus::InProgress->value])
            ->latest('updated_at')
            ->first();

        $this->dispatch('technicianRequestLoaded', [
            'technician_id'  => $technicianId,
            'active_request' => $req ? [
                'id'             => $req->id,
                'status'         => $req->status->value,
                'type'           => $req->type->value,
                'invoice_number' => $req->invoice_number,
                'address'        => $req->address,
                'customer_lat'   => $req->latitude,
                'customer_lng'   => $req->longitude,
                'scheduled_at'   => $req->scheduled_at?->format('Y-m-d'),
                'customer_name'  => $req->user?->name,
                'customer_phone' => $req->user?->phone,
                'admin_url'      => url('/admin/' . ($req->type->value === 'service' ? 'service' : 'installation') . '-requests/' . $req->id),
            ] : null,
        ]);
    }

    private function loadLocations(): void
    {
        $this->technicianLocations = $this->buildLocationsArray();
    }

    /**
     * Build the full locations array with active request data attached.
     * Used by both loadLocations() (initial mount) and refreshLocations() (polling).
     */
    private function buildLocationsArray(): array
    {
        $staleThreshold = now()->subMinutes(10);

        $locations = TechnicianLocation::with('technician')->get();

        // Collect all technician IDs to fetch their active requests in one query
        $technicianIds = $locations->pluck('technician_id')->filter()->values()->all();

        $activeRequests = \App\Models\Request::with('user')
            ->whereIn('technician_id', $technicianIds)
            ->whereIn('status', [RequestStatus::OnWay->value, RequestStatus::InProgress->value])
            ->get()
            ->keyBy('technician_id');

        return $locations
            ->map(function ($loc) use ($staleThreshold, $activeRequests) {
                $req = $activeRequests->get($loc->technician_id);

                return [
                    'technician_id' => $loc->technician_id,
                    'name'          => $loc->technician?->name ?? __('Unknown'),
                    'phone'         => $loc->technician?->phone ?? '',
                    'latitude'      => $loc->latitude,
                    'longitude'     => $loc->longitude,
                    'heading'       => $loc->heading,
                    'speed'         => $loc->speed,
                    'is_online'     => $loc->is_online && $loc->updated_at?->gt($staleThreshold),
                    'recorded_at'   => $loc->recorded_at?->toISOString(),
                    'updated_at'    => $loc->updated_at?->toISOString(),
                    'active_request' => $req ? [
                        'id'             => $req->id,
                        'status'         => $req->status->value,
                        'type'           => $req->type->value,
                        'invoice_number' => $req->invoice_number,
                        'address'        => $req->address,
                        'customer_lat'   => $req->latitude,
                        'customer_lng'   => $req->longitude,
                        'scheduled_at'   => $req->scheduled_at?->format('Y-m-d'),
                        'customer_name'  => $req->user?->name,
                        'customer_phone' => $req->user?->phone,
                        'admin_url'      => url('/admin/' . ($req->type->value === 'service' ? 'service' : 'installation') . '-requests/' . $req->id),
                    ] : null,
                ];
            })
            ->values()
            ->toArray();
    }

    public function render(): View
    {
        return view('livewire.live-map');
    }
}
