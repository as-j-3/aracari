<?php

namespace App\Http\Requests\Api\SecurityIncident;

use Illuminate\Foundation\Http\FormRequest;

class BlockIpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ip_address' => 'required|ip',
        ];
    }

    public function messages(): array
    {
        return [
            'ip_address.required' => 'عنوان IP مطلوب.',
            'ip_address.ip' => 'عنوان IP غير صحيح.',
        ];
    }
}
