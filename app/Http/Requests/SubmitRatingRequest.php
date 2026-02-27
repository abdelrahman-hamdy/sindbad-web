<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitRatingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isCustomer();
    }

    public function rules(): array
    {
        return [
            'product_rating' => 'nullable|integer|min:1|max:5',
            'service_rating' => 'nullable|integer|min:1|max:5',
            'how_found_us'   => 'nullable|string|max:255',
            'customer_notes' => 'nullable|string',
            'image'          => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ];
    }
}
