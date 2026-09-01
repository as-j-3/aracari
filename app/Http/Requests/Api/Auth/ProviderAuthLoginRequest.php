<?php

namespace App\Http\Requests\Api\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ProviderAuthLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('phone_number') && $this->filled('country_code')) {
            if (!str_starts_with($this->phone_number, '+')) {
                $fullPhone = '+' . $this->country_code . ltrim($this->phone_number, '0');
                $this->merge(['phone_number' => $fullPhone]);
            }
        }
    }
    public function rules(): array
    {
        return [
            'phone_number' => 'required_without:email|string|regex:/^\+[0-9]{7,15}$/',
            'country_code' => [
                'required_without:email',
                'string',
                'max:5',
                function ($attr, $value, $fail) {
                    if ($value && !array_key_exists($value, \App\Constants\CountryCodes::all())) {
                        $fail('رمز الدولة غير صالح.');
                    }
                },
            ],
            'email' => 'required_without:phone_number|email',
            'password' => 'required|string',
            'device_id' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'phone_number.required_without' => 'يجب إدخال رقم الجوال أو البريد الإلكتروني',
            'phone_number.regex' => 'رقم الجوال يجب أن يتكون من أرقام فقط وبطول 7-15 رقم',
            'country_code.required_without' => 'يرجى اختيار رمز الدولة',
            'email.required_without' => 'يجب إدخال البريد الإلكتروني أو رقم الجوال',
            'password.required' => 'كلمة المرور مطلوبة',
        ];
    }
}