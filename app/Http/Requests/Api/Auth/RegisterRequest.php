<?php

namespace App\Http\Requests\Api\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
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
                'required_with:phone_number',
                'string',
                'max:5',
                function ($attr, $value, $fail) {
                    if ($value && !array_key_exists($value, \App\Constants\CountryCodes::all())) {
                        $fail('رمز الدولة غير صالح.');
                    }
                },
            ],
            'email' => 'required_without:phone_number|nullable|email',
            'name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'cr_number' => 'nullable|string|max:255',
            'type_of_service_id' => 'nullable|integer|exists:type_of_services,id',
        ];
    }


    public function messages(): array
    {
        return [
            'phone_number.required_without' => 'يجب إدخال رقم الجوال في حال عدم إدخال البريد الإلكتروني',
            'phone_number.regex' => 'رقم الجوال يجب أن يحتوي على أرقام فقط',
            'country_code.required_with' => 'يرجى اختيار رمز الدولة ',
            'email.required_without' => 'يجب إدخال البريد الإلكتروني في حال عدم إدخال رقم الجوال',
            'email.email' => 'البريد الإلكتروني يجب أن يكون صالحاً',
            'name.required' => 'الاسم الكامل مطلوب',
        ];
    }

}
