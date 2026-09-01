<?php

namespace App\Http\Requests\Web\Vehicle;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Constants\UserTypes;
class StoreVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // الصلاحية في الراوت
    }

    public function rules(): array
    {
        $user = auth()->user();

        $rules = [
            'name' => 'required|string|max:255',
            'vehicle_type_id' => 'required|exists:vehicle_types,id',
            'model' => 'required|string|max:255',
            'outside_photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'inside_photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'seats_count' => 'required|integer|min:1|max:80',
            'status' => 'required|in:active,maintenance,inactive',
            'seat_generation_type' => 'required|in:auto,manual',
        ];

        // If admin is creating vehicle, require provider_id
        if ($user && $user->isAdmin()) {
            $rules['provider_id'] = 'required|exists:users,id,type_user_code,' . UserTypes::PROVIDER;
        }
        
        // إذا اختار تلقائي - L10: Seat prefix customizable
        if ($this->seat_generation_type === 'auto') {
            $rules['seat_prefix'] = [
                'required',
                'string',
                'min:1',
                'max:10',
                'regex:/^[A-Za-z0-9\x{0600}-\x{06FF}\-_]+$/u',
            ];
        }

        // إذا اختار يدوي - L6: seat_names_text parsing
        if ($this->seat_generation_type === 'manual') {
            $rules['seat_names_text'] = [
                'required',
                'string',
            ];

            // تحسين parsing للنص مع معالجة أفضل
            if ($this->has('seat_names_text')) {
                $seatNames = array_filter(
                    array_map(function($name) {
                        // إزالة مسافات زائدة و normalize
                        return preg_replace('/\s+/', ' ', trim($name));
                    }, explode("\n", $this->seat_names_text)),
                    function($name) {
                        // تجنب الأسطر الفارغة بعد التنظيف
                        return !empty($name) && strlen($name) > 0;
                    }
                );

                // التحقق من عدم تجاوز السعة
                $seatNames = array_slice($seatNames, 0, $this->seats_count);

                $this->merge(['seat_names' => $seatNames]);
            }

            $rules['seat_names'] = [
                'required',
                'array',
                'min:1',
                'max:' . $this->seats_count,
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
            'name.required' => 'الاسم مطلوب',
            'name.string' => 'الاسم يجب أن يكون نصاً',
            'name.max' => 'الاسم يجب ألا يتجاوز 255 حرفاً',
            'vehicle_type_id.required' => 'نوع المركبة مطلوب',
            'vehicle_type_id.exists' => 'نوع المركبة المحدد غير موجود أو تم حذفه',
            'model.required' => 'الموديل مطلوب',
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
            'seat_generation_type.required' => 'يجب اختيار طريقة إنشاء المقاعد',
            'seat_generation_type.in' => 'طريقة إنشاء المقاعد يجب أن تكون: تلقائي أو يدوي',
            'seat_prefix.required' => 'بادئة المقاعد مطلوبة في الوضع التلقائي',
            'seat_prefix.min' => 'بادئة المقاعد يجب أن تكون حرفاً واحداً على الأقل',
            'seat_prefix.max' => 'بادئة المقاعد لا يمكن أن تتجاوز 10 أحرف',
            'seat_prefix.regex' => 'بادئة المقاعد تحتوي على رموز غير صالحة',
            'seat_names_text.required' => 'يجب إدخال أسماء المقاعد عند الاختيار اليدوي',
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
