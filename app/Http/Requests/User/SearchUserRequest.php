<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class SearchUsersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'query' => 'nullable|string|max:255',
            'user_type' => 'nullable|in:client,contractor',
            'city' => 'nullable|string|max:100',
            'is_active' => 'nullable|boolean',
            'has_listings' => 'nullable|boolean',
            'search_mode' => 'nullable|in:websearch,phrase,plainto',
            'sort_by' => 'nullable|in:relevance,name,newest,city',
            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }
}