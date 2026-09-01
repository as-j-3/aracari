<?php

namespace App\Http\Requests\Web\FileUpload;

use Illuminate\Foundation\Http\FormRequest;

class UploadAdImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:3072'],
        ];
    }
}