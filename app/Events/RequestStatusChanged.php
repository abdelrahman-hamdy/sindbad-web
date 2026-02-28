<?php

namespace App\Events;

use App\Models\Request;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RequestStatusChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $payload;

    public function __construct(Request $request)
    {
        $this->payload = [
            'technician_id'  => $request->technician_id,
            'request_id'     => $request->id,
            'status'         => $request->status->value,
            'type'           => $request->type->value,
            'invoice_number' => $request->invoice_number,
            'address'        => $request->address,
            'customer_lat'   => $request->latitude,
            'customer_lng'   => $request->longitude,
            'scheduled_at'   => $request->scheduled_at?->format('Y-m-d'),
            'customer_name'  => $request->user?->name,
            'customer_phone' => $request->user?->phone,
        ];
    }

    public function broadcastOn(): array
    {
        return [new Channel('technician-locations')];
    }

    public function broadcastAs(): string
    {
        return 'RequestStatusChanged';
    }

    public function broadcastWith(): array
    {
        return $this->payload;
    }
}
