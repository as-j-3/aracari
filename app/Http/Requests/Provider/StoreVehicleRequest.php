<?php

namespace App\Http\Requests\Provider;

use Illuminate\Foundation\Http\FormRequest;

class StoreVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'vehicle_type_id' => 'required|exists:vehicle_types,id',
            'model' => 'nullable|string|max:255',
            'outside_photo' => 'nullable|image|max:5120',
            'inside_photo' => 'nullable|image|max:5120',
            'seats_count' => 'required|integer|min:1|max:80',
            'status' => 'nullable|in:active,maintenance,inactive',
            'seat_generation_type' => 'required|in:auto,manual',
            'seat_names' => 'required_if:seat_generation_type,manual|array',
            'seat_prefix' => 'nullable|string|max:10',
        ];
    }


    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $seatGenerationType = $this->input('seat_generation_type');
            $seatsCount = (int) $this->input('seats_count', 0);
            $seatNames = $this->input('seat_names', []);

            if ($seatGenerationType === 'manual' && is_array($seatNames)) {
                $actualSeatCount = count(array_filter($seatNames, fn($name) => !empty(trim($name))));

                if ($actualSeatCount > $seatsCount) {
                    $validator->errors()->add('seat_names', "عدد المقاعد المدخلة ($actualSeatCount) يتجاوز العدد المحدد ($seatsCount)");
                }

                if ($actualSeatCount < $seatsCount) {
                    $validator->errors()->add('seat_names', "عدد المقاعد المدخلة ($actualSeatCount) أقل من العدد المحدد ($seatsCount). يجب إدخال $seatsCount مقعد");
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'name.required' => 'اسم المركبة مطلوب',
            'name.max' => 'اسم المركبة يجب ألا يتجاوز 255 حرفاً',
            'vehicle_type_id.required' => 'نوع المركبة مطلوب',
            'vehicle_type_id.exists' => 'نوع المركبة المحدد غير موجود',
            'model.max' => 'الموديل يجب ألا يتجاوز 255 حرفاً',
            'outside_photo.image' => 'يجب أن تكون الصورة الخارجية ملف صورة',
            'outside_photo.max' => 'حجم الصورة الخارجية يجب ألا يتجاوز 5 ميجابايت',
            'inside_photo.image' => 'يجب أن تكون الصورة الداخلية ملف صورة',
            'inside_photo.max' => 'حجم الصورة الداخلية يجب ألا يتجاوز 5 ميجابايت',
            'seats_count.required' => 'عدد المقاعد مطلوب',
            'seats_count.min' => 'عدد المقاعد يجب أن يكون 1 على الأقل',
            'seats_count.max' => 'عدد المقاعد لا يمكن أن يتجاوز 80',
            'status.in' => 'حالة المركبة يجب أن تكون: نشطة، صيانة، أو غير نشطة',
            'seat_generation_type.required' => 'يجب اختيار طريقة إنشاء المقاعد',
            'seat_generation_type.in' => 'طريقة إنشاء المقاعد يجب أن تكون: تلقائي أو يدوي',
            'seat_names.required_if' => 'يجب إدخال أسماء المقاعد عند الاختيار اليدوي',
            'seat_names.array' => 'أسماء المقاعد يجب أن تكون مصفوفة',
            'seat_prefix.max' => 'بادئة المقاعد لا يمكن أن تتجاوز 10 أحرف',
        ];
    }
}
