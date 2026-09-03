<?php

namespace App\Http\Requests\Api\Auth;

use Illuminate\Foundation\Http\FormRequest;
use App\Constants\CountryCodes;

class DirectRegisterRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'phone_number' => 'required_without:email|nullable|string|unique:users,phone_number',
            'country_code' => [
                'nullable',
                'string',
                'max:5',
                function ($attr, $value, $fail) {
                    if ($value && !array_key_exists($value, CountryCodes::all())) {
                        $fail('رمز الدولة غير صالح.');
                    }
                },
            ],
            'email' => 'required_without:phone_number|nullable|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'device_id' => 'nullable|string',
            'type_user_code' => 'nullable',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'الاسم مطلوب',
            'phone_number.required_without' => 'يرجى إدخال رقم الهاتف أو البريد الإلكتروني',
            'phone_number.unique' => 'رقم الهاتف مستخدم بالفعل',
            'email.required_without' => 'يرجى إدخال البريد الإلكتروني أو رقم الهاتف',
            'email.unique' => 'البريد الإلكتروني مستخدم بالفعل',
            'password.required' => 'كلمة المرور مطلوبة',
            'password.min' => 'كلمة المرور يجب أن لا تقل عن 8 أحرف',
            'password.confirmed' => 'تأكيد كلمة المرور غير مطابق',
        ];
    }
}
