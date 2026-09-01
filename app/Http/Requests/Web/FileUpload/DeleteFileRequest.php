<?php

namespace App\Http\Requests\Web\FileUpload;

use Illuminate\Foundation\Http\FormRequest;

class DeleteFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file_path'    => ['required', 'string', 'max:500'],
            'entity_type'  => ['required', 'string', 'in:provider_file,vehicle,passenger,provider_service,ad'],
            'entity_id'    => ['required', 'integer'],
        ];
    }
}