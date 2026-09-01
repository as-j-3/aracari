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
            'type' => 'sometimes|in:fixed,percentage',
            'rate'   => ['sometimes', 'required_if:type,percentage', 'nullable', 'numeric', 'min:0', 'max:100'],
            'amount' => ['sometimes', 'required_if:type,fixed', 'nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.string'        => 'اسم الضريبة يجب أن يكون نصاً',
            'name.max'           => 'اسم الضريبة يجب ألا يتجاوز 255 حرفاً',
            'type.in'            => 'نوع الضريبة غير صالح',
            'rate.required_if'   => 'نسبة الضريبة مطلوبة',
            'rate.numeric'       => 'نسبة الضريبة يجب أن تكون رقماً',
            'rate.min'           => 'نسبة الضريبة لا يمكن أن تكون أقل من 0',
            'rate.max'           => 'نسبة الضريبة لا يمكن أن تتجاوز 100%',
            'amount.required_if' => 'قيمة الضريبة مطلوبة',
            'amount.numeric'     => 'قيمة الضريبة يجب أن تكون رقماً',
            'amount.min'         => 'قيمة الضريبة لا يمكن أن تكون أقل من 0',
        ];
    }
}
