<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInstallationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isCustomer() || $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'product_type'      => 'required|string|max:255',
            'quantity'          => 'nullable|integer|min:1',
            'invoice_number'    => 'nullable|string|max:100',
            'is_site_ready'     => 'nullable|boolean',
            'readiness_details' => 'nullable|array',
            'notes'             => 'nullable|string',
            'address'           => 'required|string',
            'latitude'          => 'required|numeric|between:-90,90',
            'longitude'         => 'required|numeric|between:-180,180',
            'scheduled_at'      => 'required|date',
            'end_date'          => 'nullable|date|after_or_equal:scheduled_at',
            'images'            => 'nullable|array|max:10',
            'images.*'          => 'image|mimes:jpeg,png,jpg,webp|max:5120',
        ];
    }
}
