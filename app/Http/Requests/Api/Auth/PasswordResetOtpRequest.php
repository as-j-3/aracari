<?php

namespace App\Http\Requests\Api\Auth;

use App\Http\Requests\Api\BaseIdentifierRequest;

class PasswordResetOtpRequest extends BaseIdentifierRequest
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
            'code' => 'required|string|digits:6',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function () {
            $this->validateAtLeastOneIdentifier();
            $this->validateSingleIdentifier();
        });
    }

    public function resolvedIdentifier(): string
    {
        return (string) ($this->uuid ?? $this->phone_number ?? $this->email ?? '');
    }
}
