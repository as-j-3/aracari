<?php

namespace App\Http\Requests\Api\Auth;

use App\Http\Requests\Api\BaseIdentifierRequest;

class ActivatePasswordRequest extends BaseIdentifierRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'uuid' => 'sometimes|string|max:255',
            'phone_number' => 'sometimes|string|max:20|regex:/^\+[0-9]{7,15}$/',
            'email' => 'sometimes|email|max:255',
            'password' => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required|string|min:8',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $this->validateAtLeastOneIdentifier();
            $this->validateSingleIdentifier();
            $this->validateUserExists();
        });
    }
}
