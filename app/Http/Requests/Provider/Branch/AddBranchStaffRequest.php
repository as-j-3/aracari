<?php

namespace App\Http\Requests\Provider\Branch;

use Illuminate\Foundation\Http\FormRequest;

class AddBranchStaffRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'يجب اختيار مستخدم.',
            'user_id.exists'   => 'المستخدم المختار غير موجود.',
        ];
    }
}