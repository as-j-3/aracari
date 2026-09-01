<?php

namespace App\Http\Requests\Api\Auth;

use App\Http\Requests\Api\BaseIdentifierRequest;

class ResetPasswordRequest extends BaseIdentifierRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'uuid' => 'sometimes|string|max:255',
            'phone_number' => 'sometimes|string|max:20|regex:/^\+[0-9]{7,15}$/',
            'email' => 'sometimes|email|max:255',
            'code' => 'required|string|digits:6',
            'password' => 'required|string|min:8|confirmed',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $this->validateAtLeastOneIdentifier();
            
            $this->validateSingleIdentifier();
            
            $this->validateUserExists();
        });
    }

    public function messages(): array
    {
        return [
            'code.required' => 'كود التحقق مطلوب',
            'code.digits' => 'كود التحقق يجب أن يتكون من 6 أرقام',
            'password.required' => 'كلمة المرور الجديدة مطلوبة',
            'password.min' => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل',
            'password.confirmed' => 'تأكيد كلمة المرور غير متطابق',
            'identifier.required' => 'يجب على الاقل ادخال قيمه واحده',
            'identifier.exists' => 'هناك بيانات ناقصه او غير صحيحه الرجاء التاكد',
        ];
    }
}
