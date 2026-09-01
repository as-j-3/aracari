<?php

namespace App\Http\Requests\Web\Vehicle;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $vehicle = $this->route('vehicle');
        $rules = [
            'name' => 'sometimes|string|max:255',
            'vehicle_type_id' => 'sometimes|exists:vehicle_types,id',
            'model' => 'sometimes|string|max:255',
            'outside_photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'inside_photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'seats_count' => 'sometimes|integer|min:1|max:80',
            'status' => 'sometimes|required|in:active,maintenance,inactive',
            'seat_option' => 'required|in:keep,regenerate,manual',
        ];
        
        // L10: إذا اختار إعادة التوليد (regenerate)
        if ($this->seat_option === 'regenerate') {
            $rules['seat_prefix'] = [
                'sometimes',
                'string',
                'min:1',
                'max:10',
                'regex:/^[A-Za-z0-9\x{0600}-\x{06FF}\-_]+$/u',
            ];
        }

        // إذا اختار تعديل يدوي - L6: seat_names_text parsing
        if ($this->seat_option === 'manual') {
            $rules['seat_names'] = [
                'required',
                'array',
                'min:1',
                'max:' . ($this->seats_count ?? $vehicle->seats_count),
            ];

            $rules['seat_names.*'] = [
                'required',
                'string',
                'max:15',
                'regex:/^[A-Za-z0-9\x{0600}-\x{06FF}\s\-_]+$/u',
                'not_in:admin,system,null,undefined,root,test',
            ];
        }
        
        return $rules;
    }

    public function messages(): array
    {
        return [
            'name.string' => 'الاسم يجب أن يكون نصاً',
            'name.max' => 'الاسم يجب ألا يتجاوز 255 حرفاً',
            'vehicle_type_id.exists' => 'نوع المركبة المحدد غير موجود',
            'model.string' => 'الموديل يجب أن يكون نصاً',
            'model.max' => 'الموديل يجب ألا يتجاوز 255 حرفاً',
            'outside_photo.image' => 'يجب أن تكون الصورة الخارجية ملف صورة',
            'outside_photo.mimes' => 'يجب أن تكون الصورة من نوع jpg, jpeg, png, webp',
            'outside_photo.max' => 'حجم الصورة الخارجية يجب ألا يتجاوز 2 ميجابايت',
            'inside_photo.image' => 'يجب أن تكون الصورة الداخلية ملف صورة',
            'inside_photo.mimes' => 'يجب أن تكون الصورة من نوع jpg, jpeg, png, webp',
            'inside_photo.max' => 'حجم الصورة الداخلية يجب ألا يتجاوز 2 ميجابايت',
            'seats_count.required' => 'عدد المقاعد مطلوب',
            'seats_count.integer' => 'عدد المقاعد يجب أن يكون رقماً صحيحاً',
            'seats_count.min' => 'عدد المقاعد يجب أن يكون 1 على الأقل',
            'seats_count.max' => 'عدد المقاعد لا يمكن أن يتجاوز 80',
            'status.required' => 'حالة المركبة مطلوبة',
            'status.in' => 'حالة المركبة يجب أن تكون: نشطة، صيانة، أو غير نشطة',
            'seat_option.required' => 'يجب اختيار خيار تعديل المقاعد',
            'seat_option.in' => 'خيار تعديل المقاعد يجب أن يكون: احتفاظ، إعادة توليد، أو يدوي',
            'seat_names.required' => 'يجب إدخال أسماء المقاعد عند الاختيار اليدوي',
            'seat_names.array' => 'أسماء المقاعد يجب أن تكون مصفوفة',
            'seat_names.min' => 'يجب إدخال اسم مقعد واحد على الأقل',
            'seat_names.max' => 'عدد المقاعد لا يمكن أن يتجاوز سعة المركبة (:max)',
            'seat_names.*.required' => 'اسم المقعد مطلوب',
            'seat_names.*.string' => 'اسم المقعد يجب أن يكون نصاً',
            'seat_names.*.max' => 'اسم المقعد لا يمكن أن يتجاوز 15 حرفاً',
            'seat_names.*.regex' => 'اسم المقعد يحتوي على رموز غير مسموحة',
            'seat_names.*.not_in' => 'اسم المقعد ":input" محجوز للنظام',
        ];
    }
}
