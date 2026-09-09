<?php

namespace App\Http\Requests\Api\LoginActivity;

use Illuminate\Foundation\Http\FormRequest;

class CleanupLoginActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'days' => 'nullable|integer|min:30|max:365',
        ];
    }

    public function messages(): array
    {
        return [
            'days.min' => 'الحد الأدنى لعدد الأيام هو 30.',
            'days.max' => 'الحد الأقصى لعدد الأيام هو 365.',
        ];
    }
}
