<?php

namespace App\Livewire;

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
        $staleThreshold = now()->subMinutes(10);

        $locations = TechnicianLocation::with('technician')
            ->get()
            ->map(fn($loc) => [
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
            ])
            ->values()
            ->toArray();

        $this->dispatch('locationsRefreshed', locations: $locations);
    }

    private function loadLocations(): void
    {
        $staleThreshold = now()->subMinutes(10);

        $this->technicianLocations = TechnicianLocation::with('technician')
            ->get()
            ->map(fn($loc) => [
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
            ])
            ->values()
            ->toArray();
    }

    public function render(): View
    {
        return view('livewire.live-map');
    }
}
