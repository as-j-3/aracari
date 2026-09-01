<?php

namespace App\Http\Requests\Api\User;

use Illuminate\Foundation\Http\FormRequest;


class UpdateUserRequest extends FormRequest
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
        $user = \App\Models\User::where('uuid', $this->route('uuid'))->first();
        $userId = $user ? $user->id : null;
        
        return [
            'phone_number' => 'sometimes|string|regex:/^\+[0-9]{7,15}$/|unique:users,phone_number,' . $userId,
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
            'email' => 'nullable|email|unique:users,email,' . $userId,
            'password' => 'sometimes|string|min:8|confirmed',
            'status_id' => 'sometimes|exists:users_statuses,id',
            'type_user_code' => 'sometimes|exists:type_of_users,type_user_code',
            'is_approved' => 'sometimes|boolean',
        ];
    }


    public function messages(): array
    {
        return [
            'phone_number.regex' => 'رقم الجوال يجب أن يحتوي على أرقام فقط',
            'phone_number.unique' => 'رقم الجوال مسجل بالفعل',
            'email.email' => 'البريد الإلكتروني يجب أن يكون صالحاً',
            'email.unique' => 'البريد الإلكتروني مسجل بالفعل',
            'password.min' => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل',
            'password.confirmed' => 'تأكيد كلمة المرور غير متطابق',
            'status_id.exists' => 'حالة الحساب غير موجودة',
            'type_user_code.exists' => 'نوع المستخدم غير موجود',
        ];
    }


    public function attributes(): array
    {
        return [
            'phone_number' => 'رقم الجوال',
            'email' => 'البريد الإلكتروني',
            'password' => 'كلمة المرور',
            'password_confirmation' => 'تأكيد كلمة المرور',
            'status_id' => 'حالة الحساب',
            'type_user_code' => 'نوع المستخدم',
            'is_approved' => 'موافقة الحساب',
        ];
    }
}
