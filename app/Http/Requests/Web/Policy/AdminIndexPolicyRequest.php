<?php

namespace App\Http\Requests\Web\Policy;

use Illuminate\Foundation\Http\FormRequest;

class AdminIndexPolicyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'search'   => 'nullable|string|max:255',
            'type'     => 'nullable|string|in:cancellation,booking,other',
            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }
}
