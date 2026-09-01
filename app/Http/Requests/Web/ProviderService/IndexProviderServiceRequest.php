<?php

namespace App\Http\Requests\Web\ProviderService;

use Illuminate\Foundation\Http\FormRequest;

class IndexProviderServiceRequest extends FormRequest
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
            'search' => 'nullable|string|max:255',
            'status' => 'nullable|string|in:active,inactive,suspended',
            'type_of_service_id' => 'nullable|integer|exists:type_of_services,id',
            'commission_type' => 'nullable|string|in:fixed,percentage',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'search.max' => 'نص البحث طويل جداً',
            'status.in' => 'الحالة المحددة غير صالحة',
            'type_of_service_id.exists' => 'نوع الخدمة المحدد غير موجود',
            'commission_type.in' => 'نوع العمولة المحدد غير صالح',
        ];
    }
}
