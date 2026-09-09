<?php

namespace App\Http\Requests\Api\LoginActivity;

use Illuminate\Foundation\Http\FormRequest;

class FilterLoginActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status'     => 'nullable|in:success,failed',
            'date_from'  => 'nullable|date',
            'date_to'    => 'nullable|date|after_or_equal:date_from',
            'user_id'    => 'nullable|integer|exists:users,id',
            'identifier' => 'nullable|string|max:255',
            'per_page'   => 'nullable|integer|min:1|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'status.in'          => 'الحالة يجب أن تكون: success أو failed.',
            'date_from.date'     => 'تاريخ البداية غير صحيح.',
            'date_to.date'       => 'تاريخ النهاية غير صحيح.',
            'date_to.after_or_equal' => 'تاريخ النهاية يجب أن يساوي أو يسبق تاريخ البداية.',
            'user_id.exists'     => 'المستخدم غير موجود.',
            'per_page.min'       => 'عدد العناصر في الصفحة يجب أن يكون على الأقل 1.',
            'per_page.max'       => 'عدد العناصر في الصفحة لا يتجاوز 100.',
        ];
    }
}
