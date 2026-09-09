<?php

namespace App\Http\Requests\Api\SecurityIncident;

use Illuminate\Foundation\Http\FormRequest;

class FilterSecurityIncidentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search'   => 'nullable|string|max:255',
            'severity' => 'nullable|in:low,medium,high',
            'blocked'  => 'nullable|boolean',
            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'severity.in' => 'الخطورة يجب أن تكون: low أو medium أو high.',
            'per_page.min' => 'عدد العناصر في الصفحة يجب أن يكون على الأقل 1.',
            'per_page.max' => 'عدد العناصر في الصفحة لا يتجاوز 100.',
        ];
    }
}
