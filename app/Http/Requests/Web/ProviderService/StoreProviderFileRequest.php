<?php

namespace App\Http\Requests\Web\ProviderService;

use Illuminate\Foundation\Http\FormRequest;
use App\Constants\ProviderFileTypes;

class StoreProviderFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file'      => ['required', 'file', 'max:10240'],
            'file_type' => ['required', 'string', 'in:document,image'],
            'name'      => ['required', 'string', 'in:' . implode(',', array_keys(ProviderFileTypes::all()))],
            'note'      => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required'      => 'يجب اختيار ملف.',
            'file.file'          => 'الملف المرفوع غير صالح.',
            'file.max'           => 'حجم الملف يجب ألا يتجاوز 10 ميجابايت.',
            'file_type.required' => 'نوع الملف مطلوب.',
            'file_type.in'       => 'نوع الملف يجب أن يكون document أو image.',
            'name.required'      => 'اسم نوع الملف مطلوب.',
            'name.in'            => 'اسم نوع الملف غير صالح.',
            'note.nullable'      => 'ملاحظات الملف غير صالحة.',
            'note.string'        => 'ملاحظات الملف يجب أن تكون نصية.',
        ];
    }
}