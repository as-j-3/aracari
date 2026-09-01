<?php

namespace App\Http\Requests\Web\ProviderService;

use Illuminate\Foundation\Http\FormRequest;

class CreateTypeOfServiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type_name' => 'required|string|max:255|unique:type_of_services,type_name',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'type_name.required' => 'اسم الخدمة التشغيلية مطلوب',
            'type_name.string' => 'اسم الخدمة يجب أن يكون نصاً',
            'type_name.max' => 'اسم الخدمة يجب ألا يتجاوز 255 حرف',
            'type_name.unique' => 'اسم الخدمة مستخدم مسبقاً',
        ];
    }
}
