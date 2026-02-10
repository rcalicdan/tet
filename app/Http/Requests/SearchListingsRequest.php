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
            'query' => 'nullable|string|max:255',
            'search_mode' => 'nullable|in:websearch,plainto,phrase',
            'service_type' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'min_price' => 'nullable|numeric|min:0',
            'max_price' => 'nullable|numeric|min:0|gte:min_price',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'radius_km' => 'nullable|integer|min:1|max:500',
            'sort_by' => 'nullable|in:relevance,price_asc,price_desc,distance,newest',
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
