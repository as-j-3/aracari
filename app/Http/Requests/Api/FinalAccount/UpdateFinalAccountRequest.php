<?php

namespace App\Http\Requests\Api\FinalAccount;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFinalAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'اسم الحساب الختامي مطلوب.',
            'name.max'      => 'اسم الحساب الختامي لا يتجاوز 255 حرفاً.',
        ];
    }
}
