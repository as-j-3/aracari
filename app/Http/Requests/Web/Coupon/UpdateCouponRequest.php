<?php

namespace App\Http\Requests\Web\Coupon;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $couponId = $this->route('coupon')?->id ?? $this->route('id');
        return [
            'code' => 'sometimes|string|max:50|unique:coupons,code,' . $couponId,
            'percentage' => 'sometimes|numeric|min:0|max:100',
            'status_id' => 'sometimes|exists:coupon_statuses,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'usage_limit' => 'nullable|integer|min:1',
            'minimum_amount' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'code.string' => 'كود الكوبون يجب أن يكون نصاً',
            'code.max' => 'كود الكوبون يجب ألا يتجاوز 50 حرفاً',
            'code.unique' => 'كود الكوبون مستخدم من قبل في كوبون آخر',
            'percentage.numeric' => 'نسبة الخصم يجب أن تكون رقماً',
            'percentage.min' => 'نسبة الخصم لا يمكن أن تكون أقل من 0',
            'percentage.max' => 'نسبة الخصم لا يمكن أن تتجاوز 100%',
            'status_id.exists' => 'الحالة المحددة غير موجودة',
            'start_date.date' => 'تاريخ البدء يجب أن يكون تاريخاً صحيحاً',
            'end_date.date' => 'تاريخ الانتهاء يجب أن يكون تاريخاً صحيحاً',
            'end_date.after' => 'تاريخ الانتهاء يجب أن يكون بعد تاريخ البدء',
            'usage_limit.integer' => 'حد الاستخدام يجب أن يكون رقماً صحيحاً',
            'usage_limit.min' => 'حد الاستخدام يجب أن يكون 1 على الأقل',
            'minimum_amount.numeric' => 'الحد الأدنى للمبلغ يجب أن يكون رقماً',
            'minimum_amount.min' => 'الحد الأدنى للمبلغ لا يمكن أن يكون أقل من 0',
            'description.string' => 'الوصف يجب أن يكون نصاً',
        ];
    }
}
