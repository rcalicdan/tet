<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $user = $this->user();

        return [
            'first_name' => ['sometimes', 'required', 'string', 'max:255'],
            'last_name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'required',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id)
            ],
            'phone_number' => [
                'sometimes',
                'required',
                'string',
                'max:20',
                'regex:/^\+?[0-9]{9,20}$/',
                Rule::unique('users')->ignore($user->id)
            ],
            'bio' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'city' => ['sometimes', 'nullable', 'string', 'max:255'],
            'address' => ['sometimes', 'nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required' => 'Imię jest wymagane',
            'first_name.string' => 'Imię musi być tekstem',
            'first_name.max' => 'Imię nie może przekraczać 255 znaków',
            
            'last_name.required' => 'Nazwisko jest wymagane',
            'last_name.string' => 'Nazwisko musi być tekstem',
            'last_name.max' => 'Nazwisko nie może przekraczać 255 znaków',
            
            'email.required' => 'Email jest wymagany',
            'email.email' => 'Podaj prawidłowy adres email',
            'email.unique' => 'Ten adres email jest już używany',
            'email.max' => 'Email nie może przekraczać 255 znaków',
            
            'phone_number.required' => 'Numer telefonu jest wymagany',
            'phone_number.regex' => 'Podaj prawidłowy numer telefonu',
            'phone_number.unique' => 'Ten numer telefonu jest już używany',
            'phone_number.max' => 'Numer telefonu nie może przekraczać 20 znaków',
            
            'bio.string' => 'Bio musi być tekstem',
            'bio.max' => 'Bio nie może przekraczać 1000 znaków',
            
            'city.string' => 'Miasto musi być tekstem',
            'city.max' => 'Nazwa miasta nie może przekraczać 255 znaków',
            
            'address.string' => 'Adres musi być tekstem',
            'address.max' => 'Adres nie może przekraczać 500 znaków',
        ];
    }

    public function attributes(): array
    {
        return [
            'first_name' => 'imię',
            'last_name' => 'nazwisko',
            'email' => 'email',
            'phone_number' => 'numer telefonu',
            'bio' => 'bio',
            'city' => 'miasto',
            'address' => 'adres',
        ];
    }
}