<?php

namespace App\Http\Requests\Api\User;

use App\Constants\CountryCodes;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
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
            'name'          => ['required', 'string', 'min:2', 'max:255'],
            'password'      => ['required', 'string'], 
            'phone_number'  => ['required', 'string', 'regex:/^\+[0-9]{7,15}$/'], 
            'country_code'  => [
                'required',
                'string',
                'max:5',
                function ($attr, $value, $fail) {
                    if ($value && !array_key_exists($value, CountryCodes::all())) {
                        $fail('رمز الدولة غير صالح.');
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'             => 'يرجى إدخال الاسم.',
            'name.string'               => 'الاسم يجب أن يكون نصاً.',
            'name.min'                  => 'الاسم يجب أن يكون على الأقل حرفين.',
            'name.max'                  => 'الاسم يجب ألا يتجاوز 255 حرفاً.',

            'password.required'         => 'يرجى إدخال كلمة المرور للتأكيد.',

            'phone_number.required'     => 'يرجى إرسال رقم الهاتف للتأكيد.',
            'phone_number.regex'        => 'صيغة رقم الهاتف غير صحيحة بعد الدمج.',

            'country_code.required'     => 'يرجى إرسال رمز الدولة.',
            'country_code.string'       => 'رمز الدولة يجب أن يكون نصاً.',
            'country_code.max'          => 'رمز الدولة طويل جداً.',
        ];
    }
}
