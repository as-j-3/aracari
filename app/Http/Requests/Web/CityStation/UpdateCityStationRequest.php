<?php

namespace App\Http\Requests\Web\CityStation;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCityStationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isAdmin();
    }

    public function rules(): array
    {
        $stationId = $this->route('cityStation')->id;

        return [
            'city_id' => 'required|exists:cities,id',
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('city_stations', 'name')
                    ->where('city_id', $this->city_id)
                    ->ignore($stationId),
            ],
            'status' => 'required|in:active,inactive',
        ];
    }

    public function messages(): array
    {
        return [
            'city_id.required' => 'المدينة مطلوبة',
            'city_id.exists' => 'المدينة غير موجودة',
            'name.required' => 'اسم المحطة مطلوب',
            'name.unique' => 'يوجد محطة بنفس الاسم في هذه المدينة',
            'status.required' => 'حالة المحطة مطلوبة',
            'status.in' => 'حالة المحطة يجب أن تكون نشطة أو متوقفة',
        ];
    }
}