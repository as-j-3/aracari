<?php

namespace App\Http\Requests\Web\CityStation;

use Illuminate\Foundation\Http\FormRequest;

class StoreCityStationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'city_id' => 'required|exists:cities,id',
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:city_stations,name,NULL,id,city_id,' . $this->city_id,
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