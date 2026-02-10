<?php

namespace App\Http\Requests\ServiceListing;

use Illuminate\Foundation\Http\FormRequest;

class IndexServiceListingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => 'nullable|string|max:255',
            'service_type' => 'nullable|string|max:100',
            'status' => 'nullable|in:active,inactive,pending',
            'city' => 'nullable|string|max:100',
            'contractor_id' => 'nullable|uuid|exists:users,id',
            'min_price' => 'nullable|numeric|min:0',
            'max_price' => 'nullable|numeric|min:0|gte:min_price',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'radius_km' => 'nullable|integer|min:1|max:500',
            'sort_by' => 'nullable|in:newest,oldest,price_asc,price_desc,service_type,city,distance',
            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'max_price.gte' => 'Maximum price must be greater than or equal to minimum price',
            'status.in' => 'Status must be active, inactive, or pending',
        ];
    }
}
