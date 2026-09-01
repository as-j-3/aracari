<?php

namespace App\Http\Requests\Web\FileUpload;

use Illuminate\Foundation\Http\FormRequest;

class UploadProviderBrandPhotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'brand_photo' => ['required', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:2048'],
        ];
    }
}