<?php

namespace App\Http\Requests\Api\User;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request class for user creation
 * 
 * Handles validation for user creation
 * Follows SOLID principles with clean, maintainable code
 */
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
        return array_merge([
        'phone_number' => 'required_without:email|nullable|string|regex:/^\+[0-9]{7,15}$/|unique:users,phone_number',
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
            'status_id' => 'required|exists:users_statuses,id',
            'type_user_code' => 'required|in:customer,provider',
            'is_approved' => 'sometimes|boolean',
            'name' => 'required_if:type_user_code,customer|string|max:255',
        ], $this->getTypeSpecificRules());
    }


    private function getTypeSpecificRules(): array
    {
        return match($this->input('type_user_code')) {
            'provider' => $this->getProviderRules(),
            default => []
        };
    }



    private function getProviderRules(): array
    {
        return [
            'company_name' => 'required|string|max:255',
            'cr_number' => 'nullable|string|max:50|unique:providers_services,cr_number',
            'type_of_service_id' => 'required|exists:types_of_service,id',
            'main_center_location' => 'nullable|string|max:500',
            'cr_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ];
    }


    public function messages(): array
    {
        return [
            'phone_number.required_without' => 'يجب إدخال رقم الجوال في حال عدم إدخال البريد الإلكتروني',
            'phone_number.unique' => 'رقم الجوال مسجل بالفعل',
            'email.required_without' => 'يجب إدخال البريد الإلكتروني في حال عدم إدخال رقم الجوال',
            'email.email' => 'البريد الإلكتروني يجب أن يكون صالحاً',
            'email.unique' => 'البريد الإلكتروني مسجل بالفعل',
            'password.required' => 'كلمة المرور مطلوبة',
            'password.min' => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل',
            'password.confirmed' => 'تأكيد كلمة المرور غير متطابق',
            'status_id.required' => 'حالة الحساب مطلوبة',
            'status_id.exists' => 'حالة الحساب غير موجودة',
            'type_user_code.required' => 'نوع المستخدم مطلوب',
            'type_user_code.in' => 'نوع المستخدم يجب أن يكون customer أو provider',
            'name.required_if' => 'الاسم مطلوب للمستخدمين من نوع customer',
            'name.max' => 'الاسم يجب ألا يزيد عن 255 حرف',
            
            'company_name.required' => 'اسم الشركة مطلوب',
            'company_name.max' => 'اسم الشركة يجب ألا يزيد عن 255 حرف',
            'cr_number.max' => 'رقم السجل التجاري يجب ألا يزيد عن 50 حرف',
            'cr_number.unique' => 'رقم السجل التجاري مسجل بالفعل',
            'cr_document.file' => 'يجب أن يكون الملف صورة أو PDF',
            'cr_document.mimes' => 'الملف يجب أن يكون من نوع: pdf, jpg, jpeg, png',
            'cr_document.max' => 'حجم الملف يجب ألا يتجاوز 2 ميجابايت',
            'type_of_service_id.required' => 'نوع الخدمة مطلوب',
            'type_of_service_id.exists' => 'نوع الخدمة المحدد غير موجود',
            'main_center_location.max' => 'الموقع الرئيسي يجب ألا يزيد عن 500 حرف',
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
