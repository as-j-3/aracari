<?php

namespace App\Http\Requests\Web\FileUpload;

use Illuminate\Foundation\Http\FormRequest;

class UploadProviderFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'provider_id' => ['required', 'integer', 'exists:provider_services,id'],
            'name'        => ['required', 'string', 'max:255'],
            'file'        => ['required', 'file', 'max:10240'],
            'file_type'   => ['nullable', 'string', 'in:document,image'],
            'note'        => ['nullable', 'string', 'max:500'],
        ];
    }
}