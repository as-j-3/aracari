<?php

namespace App\Http\Requests\Web\Report;

use Illuminate\Foundation\Http\FormRequest;

class ReportFilterRequest extends FormRequest
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
            'user_id' => 'nullable|exists:users,id',
            'action' => 'nullable|string|max:255',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'currency_id' => 'nullable|exists:currencies,id',
            'year' => 'nullable|integer|min:2020|max:' . date('Y'),
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'user_id.exists' => __('messages.user_not_found'),
            'date_to.after_or_equal' => __('messages.end_date_must_be_after_start'),
            'end_date.after_or_equal' => __('messages.end_date_must_be_after_start'),
            'year.min' => __('messages.invalid_year'),
            'year.max' => __('messages.invalid_year'),
            'currency_id.exists' => 'العملة المحددة غير موجودة',
        ];
    }
}
