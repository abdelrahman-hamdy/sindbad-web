<?php

namespace App\Services;

use App\Events\TechnicianLocationUpdated;
use App\Models\TechnicianLocation;
use App\Models\User;
use Illuminate\Support\Collection;

class TechnicianLocationService
{
    public function updateLocation(User $technician, array $data): TechnicianLocation
    {
        $location = TechnicianLocation::updateOrCreate(
            ['technician_id' => $technician->id],
            [
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'heading' => $data['heading'] ?? null,
                'speed' => $data['speed'] ?? null,
                'is_online' => true,
                'recorded_at' => $data['recorded_at'] ?? now(),
                'updated_at' => now(),
            ]
        );

        try {
            broadcast(new TechnicianLocationUpdated($location->load('technician')));
        } catch (\Throwable) {
            // Reverb may not be running in dev — DB write already succeeded
        }

        return $location;
    }

    public function markOffline(User $technician): void
    {
        $location = TechnicianLocation::where('technician_id', $technician->id)->first();
        if ($location) {
            $location->update(['is_online' => false, 'updated_at' => now()]);
            try {
                broadcast(new TechnicianLocationUpdated($location->load('technician')));
            } catch (\Throwable) {
                // Reverb may not be running in dev — DB write already succeeded
            }
        }
    }

    public function getAllOnlineLocations(): Collection
    {
        return TechnicianLocation::with('technician')
            ->where('is_online', true)
            ->get();
    }

    /**
     * Mark as offline any technician whose last location update is older than
     * $minutes minutes. Returns the number of records updated.
     */
    public function expireStale(int $minutes = 10): int
    {
        return TechnicianLocation::where('is_online', true)
            ->where('updated_at', '<', now()->subMinutes($minutes))
            ->update(['is_online' => false]);
    }
}
