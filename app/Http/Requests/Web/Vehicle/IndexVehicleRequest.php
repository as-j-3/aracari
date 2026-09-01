<?php

namespace App\Http\Requests\Web\Vehicle;

use Illuminate\Foundation\Http\FormRequest;

class IndexVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search'          => ['nullable', 'string', 'max:100'],
            'vehicle_type_id' => ['nullable', 'integer', 'exists:vehicle_types,id'],
            'status'          => ['nullable', 'string', 'in:active,inactive,maintenance'],
            'provider_id'     => ['nullable', 'integer', 'exists:users,id'],
            'per_page'        => ['nullable', 'integer', 'min:5', 'max:100'],
        ];
    }
}
