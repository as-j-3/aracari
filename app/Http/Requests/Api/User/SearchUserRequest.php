<?php

namespace App\Http\Requests\Api\User;

use Illuminate\Foundation\Http\FormRequest;


class SearchUserRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true; 
    }


    public function rules(): array
    {
        return [
            'search' => 'sometimes|nullable|string|min:2|max:100',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'search.required' => 'كلمة البحث مطلوبة',
            'search.min' => 'كلمة البحث يجب أن تكون حرفين على الأقل',
            'search.max' => 'كلمة البحث يجب أن لا تزيد عن 100 حرف',
            'per_page.integer' => 'عدد الصفحات يجب أن يكون رقماً',
            'per_page.min' => 'عدد الصفحات يجب أن يكون 1 على الأقل',
            'per_page.max' => 'عدد الصفحات يجب أن لا يزيد عن 100',
        ];
    }


    public function attributes(): array
    {
        return [
            'search' => 'كلمة البحث',
            'per_page' => 'عدد الصفحات',
        ];
    }
}
