<?php

namespace App\Http\Requests\Web\ProviderService;

use App\Constants\UserTypes;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class CreateProviderServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->hasPermission('providers:create');
    }
public function rules(): array
{
    return [
        'user_id'            => 'required|exists:users,id',
        'type_of_service_id' => 'required|exists:type_of_services,id',
        'name'               => 'required|string|max:255',
        'description'        => 'nullable|string',
        'cr_number'          => 'required|string|max:100|unique:provider_services,cr_number',
        'brand_photo'        => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        'born_country'       => 'nullable|string|max:100',
        'contact_phone'      => 'nullable|string|max:20',
        'contact_email'      => 'nullable|email|max:255',
        'website'            => 'nullable|url|max:255',
        'status'             => 'required|in:active,inactive,pending',
        'commission_type'    => 'required|in:service,booking',
        
        // طريقة الدفع
        'digital_pocket_id'  => 'required|exists:digital_pockets,id',
        'account_number'     => 'required|string|max:100',
        'account_name'       => 'required|string|max:255',
    ];
}

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $userId = $this->input('user_id');
            if (empty($userId)) return;

            $user = User::where('id', $userId)->first();
            if (!$user) return;

            // التحقق أن المستخدم نوعه مزود خدمة
            if ($user->type_user_code !== UserTypes::PROVIDER) {
                $validator->errors()->add('user_id', 'المستخدم المحدد ليس مزود خدمة');
                return;
            }

            // التحقق أن المستخدم ليس محذوفاً
            if ($user->trashed()) {
                $validator->errors()->add('user_id', 'المستخدم المحدد محذوف');
                return;
            }
        });
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'يرجى اختيار المزود',
            'user_id.exists' => 'المستخدم المحدد غير موجود',
            'type_of_service_id.required' => 'نوع الخدمة مطلوب',
            'type_of_service_id.exists'  => 'نوع الخدمة المحدد غير موجود',
            'name.required'              => 'اسم الخدمة مطلوب',
            'name.max'                   => 'اسم الخدمة يجب ألا يزيد عن 255 حرف',
            'cr_number.required'         => 'السجل التجاري مطلوب',
            'cr_number.unique'           => 'السجل التجاري مستخدم بالفعل',
            'contact_email.email'        => 'البريد الإلكتروني يجب أن يكون صحيحاً',
            'website.url'                => 'الموقع الإلكتروني يجب أن يكون رابطاً صحيحاً',
            'status.required'            => 'الحالة مطلوبة',
            'status.in'                   => 'الحالة يجب أن تكون active أو inactive أو pending',
            'commission_type.required'    => 'نوع العمولة مطلوب',
            'commission_type.in'           => 'نوع العمولة يجب أن يكون service أو booking',
            'brand_photo.image'           => 'صورة العلامة التجارية يجب أن تكون ملف صورة',
            'brand_photo.mimes'           => 'صورة العلامة التجارية يجب أن تكون من نوع jpeg أو png أو jpg أو webp',
            'brand_photo.max'              => 'صورة العلامة التجارية يجب أن لا تزيد عن 2 ميجابايت',
            'cr_document.file'            => 'وثيقة السجل التجاري يجب أن تكون ملفاً',
            'cr_document.mimes'           => 'وثيقة السجل التجاري يجب أن تكون من نوع pdf أو jpg أو jpeg أو png',
            'cr_document.max'              => 'وثيقة السجل التجاري يجب أن لا تزيد عن 2 ميجابايت',
        ];
    }
}
