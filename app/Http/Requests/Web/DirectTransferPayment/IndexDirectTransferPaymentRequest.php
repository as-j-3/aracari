<?php

namespace App\Http\Requests\Web\DirectTransferPayment;

use Illuminate\Foundation\Http\FormRequest;

class IndexDirectTransferPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search'            => ['nullable', 'string', 'max:100'],
            'payment_method_id' => ['nullable', 'integer', 'exists:payment_methods,id'],
            'per_page'          => ['nullable', 'integer', 'min:5', 'max:100'],
        ];
    }
}