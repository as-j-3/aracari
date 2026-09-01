<?php

namespace App\Http\Requests\Web\User;

use Illuminate\Foundation\Http\FormRequest;

class CreateUserRequest extends FormRequest
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
            'phone_number' => 'required_without:email|nullable|string|unique:users,phone_number|regex:/^\+[0-9]{7,15}$/',
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
            'email' => 'required_without:phone_number|nullable|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required|string|min:8',
            'status_id' => 'required|exists:user_statuses,id',
            'type_user_code' => 'required|exists:type_of_users,id',
            'role_id' => [
                'required',
                'exists:roles,id',
                function ($attribute, $value, $fail) {
                    if (!auth()->user()?->isAdmin()) {
                        $adminRoles = \App\Models\Role::whereIn('name', [\App\Constants\UserRoles::ADMIN, \App\Constants\UserRoles::FINANCE_MANAGER])->pluck('id')->toArray();
                        if (in_array((int) $value, $adminRoles)) {
                            $fail('لا يمكنك تعيين هذا الدور.');
                        }
                    }
                },
            ],
            'is_approved' => 'sometimes|boolean',
            'name' => 'required|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'phone_number.required_without' => 'رقم الجوال مطلوب عند عدم إدخال البريد الإلكتروني',
            'phone_number.unique' => 'رقم الجوال مستخدم بالفعل',
            'phone_number.regex' => 'رقم الجوال يجب أن يحتوي على أرقام فقط',
            'country_code.required_with' => 'يرجى اختيار رمز الدولة',
            'email.required_without' => 'البريد الإلكتروني مطلوب عند عدم إدخال رقم الجوال',
            'email.email' => 'البريد الإلكتروني غير صالح',
            'email.unique' => 'البريد الإلكتروني مستخدم بالفعل',
            'password.required' => 'كلمة المرور مطلوبة',
            'password.min' => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل',
            'password.confirmed' => 'تأكيد كلمة المرور غير متطابق',
            'password_confirmation.required' => 'تأكيد كلمة المرور مطلوب',
            'status_id.required' => 'حالة الحساب مطلوبة',
            'status_id.exists' => 'حالة الحساب غير موجودة',
            'type_user_code.required' => 'نوع المستخدم غير قابل للتحديد من الدور المختار',
            'type_user_code.exists' => 'نوع المستخدم غير موجود',
            'role_id.required' => 'الدور مطلوب',
            'role_id.exists' => 'الدور غير موجود',
            'name.required' => 'الاسم مطلوب',
            'name.string' => 'الاسم يجب أن يكون نصًا',
            'name.max' => 'الاسم يجب ألا يتجاوز 255 حرفًا',
        ];
    }
    public function attributes(): array
    {
        return [
            'phone_number' => 'رقم الجوال',
            'country_code' => 'رمز الدولة',
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
