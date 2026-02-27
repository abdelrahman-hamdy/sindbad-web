<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isCustomer() || $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'service_type'   => 'required|string',
            'description'    => 'nullable|string',
            'invoice_number' => 'nullable|string|max:100',
            'address'        => 'required|string',
            'latitude'       => 'required|numeric|between:-90,90',
            'longitude'      => 'required|numeric|between:-180,180',
            'scheduled_at'   => 'required|date',
            'end_date'       => 'nullable|date|after_or_equal:scheduled_at',
            'images'         => 'nullable|array|max:10',
            'images.*'       => 'image|mimes:jpeg,png,jpg,webp|max:5120',
        ];
    }
}
