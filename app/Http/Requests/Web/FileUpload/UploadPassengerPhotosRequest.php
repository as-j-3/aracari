<?php

namespace App\Http\Requests\Web\FileUpload;

use Illuminate\Foundation\Http\FormRequest;

class UploadPassengerPhotosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'personal_card_id_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:2048'],
            'passport_photo'         => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:2048'],
        ];
    }
}