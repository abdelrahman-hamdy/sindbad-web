<?php

namespace App\Events;

use App\Models\TechnicianLocation;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TechnicianLocationUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public TechnicianLocation $location) {}

    public function broadcastOn(): array
    {
        return [new Channel('technician-locations')];
    }

    public function broadcastAs(): string
    {
        return 'TechnicianLocationUpdated';
    }

    public function broadcastWith(): array
    {
        return [
            'technician_id' => $this->location->technician_id,
            'name' => $this->location->technician?->name,
            'phone' => $this->location->technician?->phone,
            'latitude' => $this->location->latitude,
            'longitude' => $this->location->longitude,
            'heading' => $this->location->heading,
            'speed' => $this->location->speed,
            'is_online' => $this->location->is_online,
            'recorded_at' => $this->location->recorded_at?->toISOString(),
        ];
    }
}
