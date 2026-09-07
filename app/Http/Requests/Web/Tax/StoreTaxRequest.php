<?php

namespace App\Http\Requests\Web\Tax;

use Illuminate\Foundation\Http\FormRequest;

class StoreTaxRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'is_visible_for_customer' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'      => 'اسم الضريبة أو الرسم مطلوب',
            'name.string'        => 'اسم الضريبة يجب أن يكون نصاً',
            'name.max'           => 'اسم الضريبة يجب ألا يتجاوز 255 حرفاً',
            'is_visible_for_customer.boolean' => 'هل الضريبة ظاهرة للعميل يجب أن يكون قيمة صحيحة',
        ];
    }
}
