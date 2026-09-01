<?php

namespace App\Http\Requests\Web\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ResendOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'uuid' => 'required|string|exists:users,uuid',
        ];
    }

    public function messages(): array
    {
        return [
            'uuid.required' => 'المعرف مطلوب',
            'uuid.exists' => 'رمز التحقق غير صحيح أو منتهي الصلاحية',
        ];
    }
}
