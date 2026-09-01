<?php

namespace App\Http\Requests\Web\Auth;

use Illuminate\Foundation\Http\FormRequest;

class VerifyOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'uuid' => 'required|string|exists:users,uuid',
            'code' => 'required|string|min:4|max:6',
        ];
    }

    public function messages(): array
    {
        return [
            'uuid.required' => 'المعرف مطلوب',
            'uuid.exists' => 'رمز التحقق غير صحيح أو منتهي الصلاحية',
            'code.required' => 'رمز التحقق مطلوب',
            'code.min' => 'رمز التحقق يجب أن يكون 4 أرقام على الأقل',
            'code.max' => 'رمز التحقق يجب أن يكون 6 أرقام كحد أقصى',
        ];
    }
}
