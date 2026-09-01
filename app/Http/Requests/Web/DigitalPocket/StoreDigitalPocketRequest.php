<?php

namespace App\Http\Requests\Web\DigitalPocket;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDigitalPocketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pocket_name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('digital_pockets')
                    ->where(fn($q) => $q->where('currency_id', $this->currency_id)),
            ],
            'currency_id' => ['nullable', 'integer', 'exists:currencies,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'pocket_name.required' => 'اسم المحفظة مطلوب.',
            'pocket_name.max'      => 'اسم المحفظة يجب ألا يتجاوز 100 حرف.',
            'pocket_name.unique'   => 'يوجد محفظة بنفس الاسم والعملة بالفعل.',
            'currency_id.exists'   => 'العملة غير موجودة.',
        ];
    }
}