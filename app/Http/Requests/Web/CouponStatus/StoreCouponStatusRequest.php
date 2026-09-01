<?php

namespace App\Http\Requests\Web\CouponStatus;

use Illuminate\Foundation\Http\FormRequest;

class StoreCouponStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status_code' => 'required|string|max:50|unique:coupon_statuses,status_code',
            'name' => 'required|string|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'status_code.required' => 'رمز الحالة مطلوب',
            'status_code.string' => 'رمز الحالة يجب أن يكون نصاً',
            'status_code.max' => 'رمز الحالة يجب ألا يتجاوز 50 حرفاً',
            'status_code.unique' => 'هذا الرمز موجود بالفعل',
            'name.required' => 'اسم الحالة مطلوب',
            'name.string' => 'اسم الحالة يجب أن يكون نصاً',
            'name.max' => 'اسم الحالة يجب ألا يتجاوز 100 حرفاً',
        ];
    }
}
