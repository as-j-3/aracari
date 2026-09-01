<?php

namespace App\Http\Requests\Web\ExchangeRate;

use Illuminate\Foundation\Http\FormRequest;

class UpdateExchangeRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rate'      => ['required', 'numeric', 'min:0.0001', 'max:999999.9999'],
            'rate_date' => ['required', 'date', 'before_or_equal:today'],
        ];
    }
}