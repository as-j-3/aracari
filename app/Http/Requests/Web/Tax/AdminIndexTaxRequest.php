<?php

namespace App\Http\Requests\Web\Tax;

use Illuminate\Foundation\Http\FormRequest;

class AdminIndexTaxRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'search' => 'sometimes|string|max:255',
            'type'   => 'sometimes|string|max:50',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'search.string'  => 'كلمة البحث يجب أن تكون نصاً',
            'search.max'     => 'كلمة البحث طويلة جداً',
            'type.string'    => 'نوع الضريبة يجب أن يكون نصاً',
            'per_page.integer' => 'عدد العناصر يجب أن يكون رقماً',
            'per_page.min'   => 'الحد الأدنى لعناصر الصفحة هو 1',
            'per_page.max'   => 'الحد الأقصى لعناصر الصفحة هو 100',
        ];
    }
}
