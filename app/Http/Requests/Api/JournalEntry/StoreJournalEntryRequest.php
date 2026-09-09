<?php

namespace App\Http\Requests\Api\JournalEntry;

use Illuminate\Foundation\Http\FormRequest;

class StoreJournalEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => 'required|date',
            'description' => 'required|string',
            'lines' => 'required|array|min:1',
            'lines.*.account_code' => 'required|string',
            'lines.*.debit' => 'required|numeric|min:0',
            'lines.*.credit' => 'required|numeric|min:0',
            'lines.*.description' => 'nullable|string',
        ];
    }
}
