<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class EmailVerificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! hash_equals(
            (string) $this->route('id'),
            (string) $this->user()->getKey()
        )) {
            return false;
        }

        if (! hash_equals(
            (string) $this->route('hash'),
            sha1($this->user()->getEmailForVerification())
        )) {
            return false;
        }

        return true;
    }

    public function rules(): array
    {
        return [];
    }

    public function fulfill(): void
    {
        if (! $this->user()->hasVerifiedEmail()) {
            $this->user()->markEmailAsVerified();

            event(new Verified($this->user()));
        }
    }

    protected function withValidator(Validator $validator): void
    {
        return;
    }
}
