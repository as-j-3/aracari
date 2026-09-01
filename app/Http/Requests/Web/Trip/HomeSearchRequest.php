<?php

namespace App\Http\Requests\Web\Trip;

use Illuminate\Foundation\Http\FormRequest;

class HomeSearchRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'from_city'      => 'nullable|string|exists:cities,uuid',
            'to_city'        => 'nullable|string|exists:cities,uuid',
            'departure_date' => 'nullable|date|after_or_equal:today',
            'vehicle_types'  => 'nullable|array',
            'vehicle_types.*' => 'integer|exists:vehicle_types,id',
            'min_price'      => 'nullable|numeric|min:0',
            'max_price'      => 'nullable|numeric|min:0|gte:min_price',
            'sort'           => 'nullable|in:price_asc,price_desc,departure_asc,departure_desc',
            'provider'       => 'nullable|integer|exists:users,id',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'from_city.exists' => __('messages.city_not_found'),
            'to_city.exists' => __('messages.city_not_found'),
            'departure_date.after_or_equal' => __('messages.date_must_be_future'),
            'vehicle_type_id.exists' => __('messages.vehicle_type_not_found'),
            'min_price.numeric' => __('messages.price_must_be_numeric'),
            'max_price.numeric' => __('messages.price_must_be_numeric'),
            'sort.in' => __('messages.invalid_sort_option'),
        ];
    }
}
