<?php

namespace App\Http\Requests\Provider;

use Illuminate\Foundation\Http\FormRequest;

class StoreProviderCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check()
            && auth()->user()->hasPermission('provider:coupons:create');
    }

    public function rules(): array
    {
        return [
            'code'              => 'required|string|unique:coupons,code|max:50',
            'percentage'        => 'required|numeric|min:0|max:100',
            'description'       => 'nullable|string',
            'start_date'        => 'required|date',
            'end_date'          => 'required|date|after:start_date',
            'usage_limit'       => 'nullable|integer|min:1',
            'max_usage_per_user'=> 'nullable|integer|min:1',
            'minimum_amount'    => 'nullable|numeric|min:0',
            'status_id'         => 'required|exists:coupon_statuses,id',
        ];
    }

    public function messages(): array
    {
        return [
            'code.required'       => 'كود الكوبون مطلوب',
            'code.unique'         => 'هذا الكود مستخدم بالفعل',
            'code.max'            => 'الكود يجب ألا يتجاوز 50 حرفاً',
            'percentage.required' => 'نسبة الخصم مطلوبة',
            'percentage.numeric'  => 'نسبة الخصم يجب أن تكون رقماً',
            'percentage.min'      => 'نسبة الخصم لا يمكن أن تكون أقل من 0',
            'percentage.max'      => 'نسبة الخصم لا يمكن أن تتجاوز 100',
            'start_date.required' => 'تاريخ البداية مطلوب',
            'end_date.required'   => 'تاريخ النهاية مطلوب',
            'end_date.after'      => 'تاريخ النهاية يجب أن يكون بعد تاريخ البداية',
            'usage_limit.integer' => 'حد الاستخدام يجب أن يكون رقماً صحيحاً',
            'usage_limit.min'     => 'حد الاستخدام يجب أن يكون 1 على الأقل',
            'max_usage_per_user.integer' => 'حد الاستخدام للمستخدم يجب أن يكون رقماً',
            'max_usage_per_user.min'     => 'حد الاستخدام للمستخدم يجب أن يكون 1 على الأقل',
            'minimum_amount.numeric' => 'الحد الأدنى للمبلغ يجب أن يكون رقماً',
            'minimum_amount.min'   => 'الحد الأدنى للمبلغ لا يمكن أن يكون سالباً',
            'status_id.required'   => 'حالة الكوبون مطلوبة',
            'status_id.exists'     => 'حالة الكوبون غير موجودة',
        ];
    }
}