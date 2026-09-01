<?php

namespace App\Http\Requests\Web\Coupon;

use Illuminate\Foundation\Http\FormRequest;

class IndexCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search'          => ['nullable', 'string', 'max:100'],
            'status_id'       => ['nullable', 'integer', 'exists:coupon_statuses,id'],
            'discount_type'   => ['nullable', 'string', 'in:percentage,fixed'],
            'is_active'       => ['nullable', 'boolean'],
            'date_from'       => ['nullable', 'date'],
            'date_to'         => ['nullable', 'date', 'after_or_equal:date_from'],
            'per_page'        => ['nullable', 'integer', 'min:5', 'max:100'],
        ];
    }
}