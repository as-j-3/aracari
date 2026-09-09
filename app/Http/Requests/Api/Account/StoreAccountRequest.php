<?php

namespace App\Http\Requests\Api\Account;

use Illuminate\Foundation\Http\FormRequest;

class StoreAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // اسم الحساب
            'account_name'        => 'required|string|max:255',

            // كود الحساب الأب (اختياري — بدونه يُعتبر حساباً جذرياً)
            'parent_account_code' => 'nullable|string|exists:accounts,account_code',

            // نوع الحساب: 0 = رئيسي (Main)، 1 = فرعي (Sub)
            'type_account'        => 'required|in:0,1',

            // طبيعة الرصيد: مدين أم دائن
            'account_nature'      => 'required|in:debit,credit',

            // الحساب الختامي المرتبط (اختياري)
            'final_account_id'    => 'nullable|integer|exists:final_accounts,final_account_id',
        ];
    }

    public function messages(): array
    {
        return [
            'account_name.required'      => 'اسم الحساب مطلوب.',
            'account_name.max'           => 'اسم الحساب لا يتجاوز 255 حرفاً.',
            'parent_account_code.exists' => 'كود الحساب الأب غير موجود في النظام.',
            'type_account.required'      => 'يرجى تحديد نوع الحساب (رئيسي أو فرعي).',
            'type_account.in'            => 'نوع الحساب يجب أن يكون: 0 (رئيسي) أو 1 (فرعي).',
            'account_nature.required'    => 'يرجى تحديد طبيعة الحساب (مدين أو دائن).',
            'account_nature.in'          => 'طبيعة الحساب يجب أن تكون: debit أو credit.',
            'final_account_id.exists'    => 'الحساب الختامي المختار غير موجود.',
        ];
    }
}