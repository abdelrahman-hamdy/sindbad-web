<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RatingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'request_id' => $this->request_id,
            'user_id' => $this->user_id,
            'product_rating' => $this->product_rating,
            'service_rating' => $this->service_rating,
            'how_found_us' => $this->how_found_us,
            'customer_notes' => $this->customer_notes,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
