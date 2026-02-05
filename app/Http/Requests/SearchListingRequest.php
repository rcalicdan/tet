<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SearchListingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'service_type' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'latitude' => 'nullable|required_with:longitude|numeric|between:-90,90',
            'longitude' => 'nullable|required_with:latitude|numeric|between:-180,180',
            'radius_km' => 'nullable|integer|min:1|max:500',
            'min_price' => 'nullable|numeric|min:0',
            'max_price' => 'nullable|numeric|min:0|gte:min_price',
            'sort_by' => 'nullable|in:price_asc,price_desc,distance,newest',
            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'latitude.required_with' => 'Latitude is required when longitude is provided',
            'longitude.required_with' => 'Longitude is required when latitude is provided',
            'max_price.gte' => 'Maximum price must be greater than or equal to minimum price',
        ];
    }
}