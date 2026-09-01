<?php

namespace App\Http\Requests\Web\ProviderBranch;

use Illuminate\Foundation\Http\FormRequest;

class IndexProviderBranchRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'search' => 'nullable|string|max:255',
            'status' => 'nullable|string|in:active,inactive,maintenance',
            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'search.max' => 'نص البحث طويل جداً',
            'status.in' => 'الحالة المحددة غير صالحة',
            'per_page.min' => 'عدد العناصر في الصفحة يجب أن يكون 1 على الأقل',
            'per_page.max' => 'عدد العناصر في الصفحة لا يمكن أن يتجاوز 100',
        ];
    }
}
