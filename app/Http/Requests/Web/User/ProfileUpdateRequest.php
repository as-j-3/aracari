<?php

namespace App\Http\Requests\Web\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use App\Constants\CountryCodes;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * دمج رمز الدولة مع رقم الهاتف قبل التحقق
     */
    protected function prepareForValidation(): void
    {
        $rawPhone = $this->filled('phone_number') ? $this->phone_number : null;
        $countryCode = $this->filled('country_code') ? $this->country_code : null;

        if ($rawPhone && $countryCode) {
            $clean = ltrim(preg_replace('/[^0-9]/', '', $rawPhone), '0');
            $fullPhone = '+' . $countryCode . $clean;
            $this->merge(['phone_number' => $fullPhone]);
        }
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $userId = Auth::id();

        return [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $userId,
            'phone_number' => 'nullable|string|regex:/^\+[0-9]{7,15}$/|unique:users,phone_number,' . $userId,
            'country_code' => 'nullable|string|max:5',
            'password' => 'nullable|string|min:8|confirmed|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'الاسم الكامل مطلوب',
            'name.max' => 'الاسم الكامل يجب أن لا يتجاوز 255 حرف',
            'email.required' => 'البريد الإلكتروني مطلوب',
            'email.email' => 'البريد الإلكتروني يجب أن يكون عنوان بريد إلكتروني صحيح',
            'email.unique' => 'البريد الإلكتروني مستخدم بالفعل',
            'phone_number.regex' => 'صيغة رقم الهاتف غير صحيحة',
            'phone_number.unique' => 'رقم الهاتف مستخدم بالفعل',
            'password.min' => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل',
            'password.confirmed' => 'تأكيد كلمة المرور لا يطابق كلمة المرور',
            'password.regex' => 'كلمة المرور يجب أن تحتوي على حرف كبير، حرف صغير، ورقم.',
        ];
    }
}
