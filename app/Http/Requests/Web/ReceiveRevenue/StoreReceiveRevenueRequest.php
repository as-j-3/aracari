<?php

namespace App\Http\Requests\Web\ReceiveRevenue;

use Illuminate\Foundation\Http\FormRequest;

class StoreReceiveRevenueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() 
            && auth()->user()->hasPermission('receive-revenue:create');
    }

    public function rules(): array
    {
        return [
            'digital_pocket_id' => 'required|exists:digital_pockets,id',
            'provider_id' => 'required|exists:provider_services,id',
            'account_number' => 'required|string|max:50|confirmed',
            'account_name' => 'required|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'digital_pocket_id.required' => 'المحفظة الرقمية مطلوبة',
            'digital_pocket_id.exists' => 'المحفظة الرقمية غير موجودة',
            'provider_id.required' => 'مزود الخدمة مطلوب',
            'provider_id.exists' => 'مزود الخدمة غير موجود',
            'account_number.required' => 'رقم الحساب مطلوب',
            'account_number.max' => 'رقم الحساب يجب أن لا يتجاوز 50 حرف',
            'account_number.confirmed' => 'تأكيد رقم الحساب غير متطابق',
            'account_name.required' => 'اسم صاحب الحساب مطلوب',
            'account_name.max' => 'اسم صاحب الحساب يجب أن لا يتجاوز 255 حرف',
        ];
    }
}