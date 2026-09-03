<?php

namespace App\Http\Requests\Web\ProviderService;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\ProviderService;
class UpdateProviderServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
            $providerService = $this->route('uuid') 
        ? ProviderService::where('uuid', $this->route('uuid'))->first() 
        : null;
        $providerServiceId = $providerService?->id ?? 0;

        return [
            'type_of_service_id' => 'required|exists:type_of_services,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'cr_number' => 'required|string|max:100|unique:provider_services,cr_number,' . $providerServiceId,
            'brand_photo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'born_country' => 'nullable|string|max:100',
            'contact_phone' => 'nullable|string|max:20',
            'contact_email' => 'nullable|email|max:255',
            'website' => 'nullable|url|max:255',
            'status' => 'required|in:active,inactive,pending',
            'commission_type' => 'required|in:service,booking',
    
        ];
    }

    public function messages(): array
    {
        return [
            'type_of_service_id.required' => 'نوع الخدمة مطلوب',
            'type_of_service_id.exists' => 'نوع الخدمة المحدد غير موجود',
            'name.required' => 'اسم الخدمة مطلوب',
            'name.max' => 'اسم الخدمة يجب ألا يزيد عن 255 حرف',
            'cr_number.required' => 'السجل التجاري مطلوب',
            'cr_number.unique' => 'السجل التجاري مستخدم بالفعل',
            'contact_email.email' => 'البريد الإلكتروني يجب أن يكون صحيحاً',
            'website.url' => 'الموقع الإلكتروني يجب أن يكون رابطاً صحيحاً',
            'status.required' => 'الحالة مطلوبة',
            'status.in' => 'الحالة يجب أن تكون active أو inactive أو pending',
            'commission_type.required' => 'نوع العمولة مطلوب',
            'commission_type.in' => 'نوع العمولة يجب أن يكون service أو booking',
            'brand_photo.image' => 'صورة العلامة التجارية يجب أن تكون ملف صورة',
            'brand_photo.mimes' => 'صورة العلامة التجارية يجب أن تكون من نوع jpeg أو png أو jpg أو webp',
            'brand_photo.max' => 'صورة العلامة التجارية يجب ألا تزيد عن 2 ميجابايت',
        ];
    }
}
