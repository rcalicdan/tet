<?php

namespace App\Http\Requests\ServiceListing;

use Illuminate\Foundation\Http\FormRequest;

class StoreServiceListingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isContractor();
    }

    public function rules(): array
    {
        return [
            'service_type' => 'required|string|max:100',
            'description' => 'required|string|max:5000',
            'price' => 'nullable|numeric|min:0|max:99999999.99',
            'service_city' => 'required|string|max:100',
            'service_radius_km' => 'required|integer|min:1|max:500',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'contact_phone' => 'required|string|max:20',
            'photos' => 'nullable|array|max:10',
            'photos.*' => 'image|mimes:jpeg,jpg,png,webp|max:5120', 
        ];
    }

    public function messages(): array
    {
        return [
            'service_type.required' => 'Service type is required',
            'description.required' => 'Description is required',
            'service_city.required' => 'Service city is required',
            'service_radius_km.required' => 'Service radius is required',
            'contact_phone.required' => 'Contact phone is required',
            'photos.*.image' => 'Each file must be an image',
            'photos.*.mimes' => 'Images must be jpeg, jpg, png, or webp',
            'photos.*.max' => 'Each image must not exceed 5MB',
        ];
    }
}