<?php

namespace App\Http\Requests\Api\Report;

use Illuminate\Foundation\Http\FormRequest;

class AccountStatementRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'account_code' => 'required|exists:accounts,account_code',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'account_code.required' => 'كود الحساب مطلوب',
            'account_code.exists' => 'الحساب المحدد غير موجود',
            'start_date.date' => 'تاريخ البداية يجب أن يكون تاريخاً صحيحاً',
            'end_date.date' => 'تاريخ النهاية يجب أن يكون تاريخاً صحيحاً',
            'end_date.after_or_equal' => __('messages.end_date_must_be_after_start'),
        ];
    }
}
