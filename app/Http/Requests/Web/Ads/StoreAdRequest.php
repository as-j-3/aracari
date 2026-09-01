<?php

namespace App\Http\Requests\Web\Ads;

use Illuminate\Foundation\Http\FormRequest;

class StoreAdRequest extends FormRequest
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
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'image' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
            'target_url' => 'nullable|url|max:500',
            'status' => 'sometimes|in:active,inactive,pending,rejected',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',
        ];
    }

    /**
     * Get the custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'عنوان الإعلان مطلوب',
            'title.string' => 'عنوان الإعلان يجب أن يكون نصاً',
            'title.max' => 'عنوان الإعلان يجب ألا يزيد عن 255 حرف',
            'description.required' => 'وصف الإعلان مطلوب',
            'description.string' => 'الوصف يجب أن يكون نصاً',
            'image.required' => 'صورة الإعلان مطلوبة',
            'image.image' => 'الملف يجب أن يكون صورة',
            'image.mimes' => 'صورة الإعلان يجب أن تكون من نوع: jpg, jpeg, png, webp',
            'image.max' => 'حجم الصورة يجب ألا يزيد عن 5 ميجابايت',
            'target_url.url' => 'رابط الهدف يجب أن يكون رابطاً صحيحاً',
            'target_url.max' => 'رابط الهدف طويل جداً',
            'status.in' => 'حالة الإعلان غير صالحة',
            'start_date.required' => 'تاريخ البدء مطلوب',
            'start_date.date' => 'تاريخ البدء يجب أن يكون تاريخاً صحيحاً',
            'start_date.after_or_equal' => 'تاريخ البدء يجب أن يكون اليوم أو في المستقبل',
            'end_date.required' => 'تاريخ الانتهاء مطلوب',
            'end_date.date' => 'تاريخ الانتهاء يجب أن يكون تاريخاً صحيحاً',
            'end_date.after' => 'تاريخ الانتهاء يجب أن يكون بعد تاريخ البدء',
        ];
    }
}
