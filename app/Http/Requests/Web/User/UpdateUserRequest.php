<?php

namespace App\Http\Requests\Web\User;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $uuid = $this->route('uuid');
        $user = User::withTrashed()->where('uuid', $uuid)->first();

        if (!$this->filled('password')) {
            $this->request->remove('password');
            $this->request->remove('password_confirmation');
        }

        if ($this->filled('phone_number') && $this->filled('country_code')) {
            if (!str_starts_with($this->phone_number, '+')) {
                $fullPhone = '+' . $this->country_code . ltrim($this->phone_number, '0');
                $this->merge(['phone_number' => $fullPhone]);
            }
        }

        if ($this->filled('phone_number') && $user && $this->phone_number === $user->phone_number) {
            $this->request->remove('phone_number');
            $this->request->remove('country_code');
        }

    }

    public function rules(): array
    {
        $uuid = $this->route('uuid');
        $user = User::withTrashed()->where('uuid', $uuid)->first();
        $userId = $user?->id;

        return [
            'phone_number' => 'nullable|string|unique:users,phone_number,' . $userId . '|regex:/^\+[0-9]{7,15}$/',
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
            'password' => 'nullable|string|min:8|confirmed',
            'password_confirmation' => 'nullable|required_with:password|string|min:8',
            'status_id' => 'sometimes|exists:user_statuses,id',
            'type_user_code' => 'required|exists:type_of_users,id',
            'role_id' => 'required|exists:roles,id',
            'is_approved' => 'sometimes|boolean',
            'name' => 'sometimes|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'phone_number.unique' => 'رقم الجوال مستخدم بالفعل',
            'phone_number.regex' => 'رقم الجوال يجب أن يحتوي على أرقام فقط',  
             'country_code.required_with' => 'يرجى اختيار رمز الدولة', 
            'email.email' => 'البريد الإلكتروني غير صالح',
            'email.unique' => 'البريد الإلكتروني مستخدم بالفعل',
            'password.min' => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل',
            'password.confirmed' => 'تأكيد كلمة المرور غير متطابق',
            'password_confirmation.required_with' => 'تأكيد كلمة المرور مطلوب عند تغيير كلمة المرور',
            'status_id.exists' => 'حالة الحساب غير موجودة',
            'type_user_code.required' => 'نوع المستخدم غير قابل للتحديد من الدور المختار',
            'type_user_code.exists' => 'نوع المستخدم غير موجود',
            'role_id.required' => 'الدور مطلوب',
            'role_id.exists' => 'الدور غير موجود',
            'name.string' => 'الاسم يجب أن يكون نصًا',
            'name.max' => 'الاسم يجب ألا يتجاوز 255 حرفًا',
        ];
    }

    public function attributes(): array
    {
        return [
            'phone_number' => 'رقم الجوال',
            'country_code' => 'رمز الدوله',
            'email' => 'البريد الإلكتروني',
            'password' => 'كلمة المرور',
            'password_confirmation' => 'تأكيد كلمة المرور',
            'status_id' => 'حالة الحساب',
            'type_user_code' => 'نوع المستخدم',
            'role_id' => 'الدور',
            'is_approved' => 'اعتماد الحساب',
            'name' => 'الاسم',
        ];
    }

}
