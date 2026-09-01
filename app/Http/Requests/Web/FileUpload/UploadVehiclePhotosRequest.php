<?php

namespace App\Http\Requests\Web\FileUpload;

use Illuminate\Foundation\Http\FormRequest;

class UploadVehiclePhotosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'outside_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:5120'],
            'inside_photo'  => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'outside_photo.image' => 'الصورة الخارجية يجب أن تكون صورة صالحة.',
            'outside_photo.max'   => 'حجم الصورة الخارجية يجب ألا يتجاوز 5 ميجابايت.',
            'inside_photo.image'  => 'الصورة الداخلية يجب أن تكون صورة صالحة.',
            'inside_photo.max'    => 'حجم الصورة الداخلية يجب ألا يتجاوز 5 ميجابايت.',
        ];
    }
}