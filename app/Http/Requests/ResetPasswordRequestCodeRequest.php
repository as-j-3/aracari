<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordRequestCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'uuid' => 'required|string|uuid',
            'code' => 'required|string|digits:6',
        ];
    }

    public function messages(): array
    {
        return [
            'uuid.required' => 'معرف الطلب مطلوب',
            'uuid.uuid' => 'معرف الطلب غير صالح',
            'code.required' => 'كود التحقق مطلوب',
            'code.digits' => 'كود التحقق يجب أن يكون 6 أرقام',
        ];
    }
}
