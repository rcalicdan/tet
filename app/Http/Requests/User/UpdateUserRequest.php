<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->route('user');
        return $this->user()->id === $user->id;
    }

    public function rules(): array
    {
        $userId = $this->route('user')->id;

        return [
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'email' => [
                'sometimes',
                'required',
                'email',
                'max:255',
                Rule::unique('users')->ignore($userId),
            ],
            'phone_number' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('users')->ignore($userId),
            ],
            'bio' => 'nullable|string|max:1000',
            'city' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:500',
        ];
    }
}