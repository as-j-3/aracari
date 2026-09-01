<?php

namespace App\Http\Requests\Web\Booking;

use Illuminate\Foundation\Http\FormRequest;

class AdminIndexBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search'  => ['nullable', 'string', 'max:100'],
            'status'  => ['nullable', 'string', 'in:confirmed,pending,cancelled,completed'],
            'per_page'=> ['nullable', 'integer', 'min:5', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'search.string'   => 'معامل البحث يجب أن يكون نصياً.',
            'search.max'      => 'لا يزيد نص البحث عن 100 حرف.',
            'status.in'       => 'الحالة غير صالحة.',
            'per_page.integer'=> 'عدد العناصر لكل صفحة يجب أن يكون رقماً صحيحاً.',
            'per_page.min'    => 'الحد الأدنى لعرض العناصر لكل صفحة هو 5.',
            'per_page.max'    => 'الحد الأقصى لعرض العناصر لكل صفحة هو 100.',
        ];
    }
}
