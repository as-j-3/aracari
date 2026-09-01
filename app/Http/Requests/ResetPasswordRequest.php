<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'uuid' => 'required|string|uuid',
            'reset_token' => 'required|string|min:60',
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
        ];
    }

    public function messages(): array
    {
        return [
            'uuid.required' => 'معرف الطلب مطلوب',
            'uuid.uuid' => 'معرف الطلب غير صالح',
            'reset_token.required' => 'رمز إعادة التعيين مطلوب',
            'reset_token.min' => 'رمز إعادة التعيين غير صالح',
            'password.required' => 'كلمة المرور مطلوبة',
            'password.confirmed' => 'تأكيد كلمة المرور غير متطابق',
            'password.min' => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل',
            'password.mixed_case' => 'كلمة المرور يجب أن تحتوي على أحرف كبيرة وصغيرة',
            'password.numbers' => 'كلمة المرور يجب أن تحتوي على أرقام',
        ];
    }
}
