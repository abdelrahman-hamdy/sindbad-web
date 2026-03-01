<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'role' => $this->role,
            'is_active' => $this->is_active,
            'odoo_id' => $this->odoo_id,
            'fcm_token' => $this->when($request->user()?->isAdmin(), $this->fcm_token),
            'total_requests' => $this->when(isset($this->total_requests), $this->total_requests),
            'completed_requests' => $this->when(isset($this->completed_requests), $this->completed_requests),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
