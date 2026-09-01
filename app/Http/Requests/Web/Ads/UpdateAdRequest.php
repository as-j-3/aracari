<?php

namespace App\Http\Requests\Web\Ads;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAdRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'image' => 'sometimes|image|mimes:jpg,jpeg,png,webp|max:5120',
            'target_url' => 'nullable|url|max:500',
            'status' => 'sometimes|in:active,inactive,pending,rejected',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after:start_date',
        ];
    }

    /**
     * Get the custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'title.string' => 'عنوان الإعلان يجب أن يكون نصاً',
            'title.max' => 'عنوان الإعلان يجب ألا يزيد عن 255 حرف',
            'description.string' => 'الوصف يجب أن يكون نصاً',
            'image.image' => 'الملف يجب أن يكون صورة',
            'image.mimes' => 'صورة الإعلان يجب أن تكون من نوع: jpg, jpeg, png, webp',
            'image.max' => 'حجم الصورة يجب ألا يزيد عن 5 ميجابايت',
            'target_url.url' => 'رابط الهدف يجب أن يكون رابطاً صحيحاً',
            'target_url.max' => 'رابط الهدف طويل جداً',
            'status.in' => 'الحالة يجب أن تكون إحدى: active, inactive, pending',
            'start_date.date' => 'تاريخ البدء يجب أن يكون تاريخاً صحيحاً',
            'end_date.date' => 'تاريخ الانتهاء يجب أن يكون تاريخاً صحيحاً',
            'end_date.after' => 'تاريخ الانتهاء يجب أن يكون بعد تاريخ البدء',
        ];
    }
}
