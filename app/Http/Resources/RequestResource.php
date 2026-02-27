<?php

namespace App\Http\Resources;

use App\Enums\RequestType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $base = [
            'id' => $this->id,
            'type' => $this->type->value,
            'status' => $this->status->value,
            'invoice_number' => $this->invoice_number,
            'address' => $this->address,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'scheduled_at' => $this->scheduled_at?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'task_start_time' => $this->task_start_time?->toISOString(),
            'task_end_time' => $this->task_end_time?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),
            'technician_accepted_at' => $this->technician_accepted_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'user' => $this->whenLoaded('user', fn() => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'phone' => $this->user->phone,
            ]),
            'technician' => $this->whenLoaded('technician', fn() => $this->technician ? [
                'id' => $this->technician->id,
                'name' => $this->technician->name,
                'phone' => $this->technician->phone,
            ] : null),
            'rating' => $this->whenLoaded('rating', fn() => $this->rating
                ? new RatingResource($this->rating)
                : null
            ),
        ];

        // Type-specific fields
        if ($this->type === RequestType::Service) {
            $base['service_type'] = $this->service_type?->value;
            $base['description'] = $this->description;
            $base['details'] = $this->details;
        }

        if ($this->type === RequestType::Installation) {
            $base['product_type'] = $this->product_type;
            $base['quantity'] = $this->quantity;
            $base['is_site_ready'] = $this->is_site_ready;
            $base['readiness_details'] = $this->readiness_details;
            $base['description'] = $this->description;
        }

        return $base;
    }
}
