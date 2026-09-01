<?php

namespace App\Http\Requests\Web\Role;

use Illuminate\Foundation\Http\FormRequest;

class AssignPermissionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'permissions'       => ['required', 'array'],
            'permissions.*'     => ['exists:permissions,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'permissions.required'  => 'يرجى اختيار صلاحية واحدة على الأقل.',
            'permissions.array'     => 'الصلاحيات يجب أن تكون في شكل مصفوفة.',
            'permissions.*.exists'  => 'إحدى الصلاحيات المختارة غير موجودة.',
        ];
    }
}
