<?php

namespace App\Http\Requests\Api\Auth;

use App\Http\Requests\Api\BaseIdentifierRequest;

class ResendOtpRequest extends BaseIdentifierRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            'uuid' => 'sometimes|string|max:255',
            'phone_number' => 'sometimes|string|regex:/^\+[0-9]{7,15}$/',
            'country_code' => [
                'required_with:phone_number',
                'string',
                'max:5',
                function ($attr, $value, $fail) {
                    if ($value && !array_key_exists($value, \App\Constants\CountryCodes::all())) {
                        $fail('رمز الدولة غير صالح.');
                    }
                },
            ],
            'email' => 'sometimes|email|max:255',
            'purpose' => 'nullable|string|in:verify_account,reset_password',
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
