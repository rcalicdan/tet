<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class UploadPhotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'profile_photo' => [
                'required',
                'image',
                'mimes:jpeg,png,jpg,gif,webp',
                'max:10240', 
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'profile_photo.required' => 'Profile photo is required',
            'profile_photo.image' => 'File must be an image',
            'profile_photo.mimes' => 'Image must be in format: jpeg, png, jpg, gif or webp',
            'profile_photo.max' => 'Image cannot be larger than 10MB',
        ];
    }
}