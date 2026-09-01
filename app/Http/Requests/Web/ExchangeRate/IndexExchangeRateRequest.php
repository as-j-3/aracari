<?php

namespace App\Http\Requests\Web\ExchangeRate;

use Illuminate\Foundation\Http\FormRequest;

class IndexExchangeRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search'           => ['nullable', 'string', 'max:100'],
            'from_currency_id' => ['nullable', 'integer', 'exists:currencies,id'],
            'to_currency_id'   => ['nullable', 'integer', 'exists:currencies,id'],
            'date_from'        => ['nullable', 'date'],
            'date_to'          => ['nullable', 'date', 'after_or_equal:date_from'],
            'per_page'         => ['nullable', 'integer', 'min:5', 'max:100'],
        ];
    }
}