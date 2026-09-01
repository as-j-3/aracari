<?php

namespace App\Http\Requests\Web\DigitalPocket;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDigitalPocketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $pocketId = $this->route('digitalPocket')->id;

        return [
            'pocket_name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('digital_pockets')
                    ->where(fn($q) => $q->where('currency_id', $this->currency_id))
                    ->ignore($pocketId),
            ],
            'currency_id' => ['nullable', 'integer', 'exists:currencies,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'pocket_name.required' => 'اسم المحفظة مطلوب.',
            'pocket_name.unique'   => 'يوجد محفظة بنفس الاسم والعملة بالفعل.',
            'currency_id.exists'   => 'العملة غير موجودة.',
        ];
    }
}