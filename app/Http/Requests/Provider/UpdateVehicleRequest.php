<?php

namespace App\Http\Requests\Provider;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'vehicle_type_id' => 'sometimes|exists:vehicle_types,id',
            'model' => 'nullable|string|max:255',
            'outside_photo' => 'nullable|image|max:5120',
            'inside_photo' => 'nullable|image|max:5120',
            'seats_count' => 'sometimes|integer|min:1|max:80',
            'status' => 'nullable|in:active,maintenance,inactive',
            'seat_option' => 'required|in:keep,regenerate,manual',
            'seat_names' => 'required_if:seat_option,manual|array',
            'seat_prefix' => 'nullable|string|max:10',
        ];
    }

    public function messages(): array
    {
        return [
            'name.max' => 'اسم المركبة يجب ألا يتجاوز 255 حرفاً',
            'vehicle_type_id.exists' => 'نوع المركبة المحدد غير موجود',
            'model.max' => 'الموديل يجب ألا يتجاوز 255 حرفاً',
            'outside_photo.image' => 'يجب أن تكون الصورة الخارجية ملف صورة',
            'outside_photo.max' => 'حجم الصورة الخارجية يجب ألا يتجاوز 5 ميجابايت',
            'inside_photo.image' => 'يجب أن تكون الصورة الداخلية ملف صورة',
            'inside_photo.max' => 'حجم الصورة الداخلية يجب ألا يتجاوز 5 ميجابايت',
            'seats_count.min' => 'عدد المقاعد يجب أن يكون 1 على الأقل',
            'seats_count.max' => 'عدد المقاعد لا يمكن أن يتجاوز 80',
            'status.in' => 'حالة المركبة يجب أن تكون: نشطة، صيانة، أو غير نشطة',
            'seat_option.required' => 'يجب اختيار خيار تعديل المقاعد',
            'seat_option.in' => 'خيار تعديل المقاعد غير صالح',
            'seat_names.required_if' => 'يجب إدخال أسماء المقاعد عند الاختيار اليدوي',
            'seat_names.array' => 'أسماء المقاعد يجب أن تكون مصفوفة',
            'seat_prefix.max' => 'بادئة المقاعد لا يمكن أن تتجاوز 10 أحرف',
        ];
    }
}