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
            'profile_photo.required' => 'Zdjęcie profilowe jest wymagane',
            'profile_photo.image' => 'Plik musi być zdjęciem',
            'profile_photo.mimes' => 'Zdjęcie musi być w formacie: jpeg, png, jpg, gif lub webp',
            'profile_photo.max' => 'Zdjęcie nie może być większe niż 2MB',
        ];
    }
}