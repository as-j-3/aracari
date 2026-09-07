<?php

namespace App\Http\Requests\Web\Tax;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTaxRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'is_visible_for_customer' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.string'        => 'اسم الضريبة يجب أن يكون نصاً',
            'name.max'           => 'اسم الضريبة يجب ألا يتجاوز 255 حرفاً',
            'is_visible_for_customer.boolean' => 'الحالة يجب أن تكون قيمة منطقية',
        ];
    }
}
