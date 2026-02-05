<?php

namespace App\Http\Requests\ServiceListing;

use Illuminate\Foundation\Http\FormRequest;

class UpdateServiceListingRequest extends FormRequest
{
    public function authorize(): bool
    {
        $listing = $this->route('listing');
        return $this->user()->isContractor() && 
               $listing->contractor_id === $this->user()->id;
    }

    public function rules(): array
    {
        return [
            'service_type' => 'sometimes|required|string|max:100',
            'description' => 'sometimes|required|string|max:5000',
            'price' => 'nullable|numeric|min:0|max:99999999.99',
            'service_city' => 'sometimes|required|string|max:100',
            'service_radius_km' => 'sometimes|required|integer|min:1|max:500',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'contact_phone' => 'sometimes|required|string|max:20',
            'status' => 'sometimes|in:active,inactive',
        ];
    }
}