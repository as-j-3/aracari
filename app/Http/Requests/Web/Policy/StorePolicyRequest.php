<?php

namespace App\Http\Requests\Web\Policy;

use Illuminate\Foundation\Http\FormRequest;

class StorePolicyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'name'        => 'required|string|min:2|max:255',
            'description' => 'nullable|string|max:1000',
            'type'        => 'required|in:cancellation,booking,other',
            'rules'       => 'required_if:type,cancellation|array|min:1',
            'rules.*.from_hours'    => 'required_with:rules|integer|min:0',
            'rules.*.to_hours'      => 'required_with:rules|integer|min:0|gt:rules.*.from_hours',
            'rules.*.fee_percentage' => 'required_with:rules|numeric|min:0|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'       => 'اسم السياسة مطلوب',
            'name.min'            => 'اسم السياسة يجب أن يكون حرفين على الأقل',
            'type.required'       => 'نوع السياسة مطلوب',
            'rules.required_if'    => 'يجب إضافة قاعدة إلغاء واحدة على الأقل',
            'rules.min'            => 'يجب إضافة قاعدة إلغاء واحدة على الأقل',
            'rules.*.from_hours.min'      => 'قيمة "من" يجب أن تكون 0 أو أكبر',
            'rules.*.to_hours.gt'         => 'قيمة "إلى" يجب أن تكون أكبر من قيمة "من"',
            'rules.*.fee_percentage.max'  => 'نسبة الرسوم يجب ألا تتجاوز 100%',
        ];
    }
}
