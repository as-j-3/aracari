<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAdRequest extends FormRequest
{

    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isAdmin();
    }


    public function rules(): array
    {
        return [
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'image_url' => 'sometimes|url|max:500',
            'target_url' => 'sometimes|url|max:500',
            'status' => 'sometimes|in:active,inactive,pending',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after:start_date',
        ];
    }


    public function messages(): array
    {
        return [
            'title.max' => 'عنوان الإعلان يجب ألا يزيد عن 255 حرف',
            'image_url.url' => 'رابط الصورة يجب أن يكون رابط صحيح',
            'target_url.url' => 'رابط الهدف يجب أن يكون رابط صحيح',
            'status.in' => 'الحالة يجب أن تكون إحدى: active, inactive, pending',
            'end_date.after' => 'تاريخ الانتهاء يجب أن يكون بعد تاريخ البدء',
        ];
    }
}
