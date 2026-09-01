<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateAdRequest extends FormRequest
{

    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isAdmin();
    }


    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'image_url' => 'required|url|max:500',
            'target_url' => 'required|url|max:500',
            'status' => 'sometimes|in:active,inactive,pending',
            'start_date' => 'required|date|after_or_equal:now',
            'end_date' => 'required|date|after:start_date',
        ];
    }


    public function messages(): array
    {
        return [
            'title.required' => 'عنوان الإعلان مطلوب',
            'title.max' => 'عنوان الإعلان يجب ألا يزيد عن 255 حرف',
            'description.required' => 'وصف الإعلان مطلوب',
            'image_url.required' => 'رابط الصورة مطلوب',
            'image_url.url' => 'رابط الصورة يجب أن يكون رابط صحيح',
            'target_url.required' => 'رابط الهدف مطلوب',
            'target_url.url' => 'رابط الهدف يجب أن يكون رابط صحيح',
            'start_date.after_or_equal' => 'تاريخ البدء يجب أن يكون الآن أو في المستقبل',
            'end_date.after' => 'تاريخ الانتهاء يجب أن يكون بعد تاريخ البدء',
        ];
    }
}
