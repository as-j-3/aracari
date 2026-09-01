<?php

namespace App\Http\Requests\Web\Ads;

use Illuminate\Foundation\Http\FormRequest;

class AdminIndexAdRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'search' => 'sometimes|string|max:255',
            'status' => 'sometimes|string|max:50',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'search.string' => 'كلمة البحث يجب أن تكون نصاً',
            'search.max' => 'كلمة البحث طويلة جداً',
            'status.string' => 'الحالة يجب أن تكون نصاً',
            'per_page.integer' => 'عدد العناصر يجب أن يكون رقماً',
            'per_page.min' => 'الحد الأدنى لعناصر الصفحة هو 1',
            'per_page.max' => 'الحد الأقصى لعناصر الصفحة هو 100',
        ];
    }
}
