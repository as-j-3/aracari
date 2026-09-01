<?php

namespace App\Http\Requests\Provider\Branch;

use Illuminate\Foundation\Http\FormRequest;

class IndexBranchRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'search'   => ['nullable', 'string', 'max:100'],
            'status'   => ['nullable', 'string', 'in:active,inactive,pending'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ];
    }
}