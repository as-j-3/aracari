<?php

namespace App\Http\Requests\Web\Commission;

use Illuminate\Foundation\Http\FormRequest;

class AdminIndexCommissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search'  => ['nullable', 'string', 'max:100'],
            'type'    => ['nullable', 'string', 'in:fixed,percentage'],
            'level'   => ['nullable', 'string', 'in:service,booking'],
            'per_page'=> ['nullable', 'integer', 'min:5', 'max:100'],
        ];
    }
}
