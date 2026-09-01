<?php

namespace App\Http\Requests\Web\ProviderBranch;

use Illuminate\Foundation\Http\FormRequest;

class CreateProviderBranchRequest extends FormRequest
{
        protected function prepareForValidation(): void
    {
        if ($this->filled('phone_number') && $this->filled('country_code')) {
            if (!str_starts_with($this->phone_number, '+')) {
                $fullPhone = '+' . $this->country_code . ltrim($this->phone_number, '0');
                $this->merge(['phone_number' => $fullPhone]);
            }
        }
    }
    public function authorize(): bool
    {
        return auth()->check() && (auth()->user()->isAdmin() || auth()->user()->isProvider());
    }

    public function rules(): array
    {
        return [
            'provider_id' => 'required|exists:provider_services,id',
            'phone_number' => 'required|string|regex:/^\+?[0-9]{7,15}$/',
            'country_code' => [
                'nullable',
                'string',
                'max:5',
                function ($attr, $value, $fail) {
                    if ($value && !array_key_exists($value, \App\Constants\CountryCodes::all())) {
                        $fail('رمز الدولة غير صالح.');
                    }
                },
            ],
            'country_id' => 'required|exists:countries,id',
            'city_id' => 'required|exists:cities,id',
            'address' => 'required|string|max:500',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'status' => 'nullable|string|in:active,inactive',
        ];
    }

    public function messages(): array
    {
        return [
            'provider_id.required' => 'المزود مطلوب',
            'provider_id.exists' => 'المزود المحدد غير موجود',
            'phone_number.required' => 'رقم الهاتف مطلوب',
            'phone_number.max' => 'رقم الهاتف يجب ألا يتجاوز 20 حرف',
            'country_id.required' => 'الدولة مطلوبة',
            'country_id.exists' => 'الدولة المحددة غير موجودة',
            'city_id.required' => 'المدينة مطلوبة',
            'city_id.exists' => 'المدينة المحددة غير موجودة',
            'address.required' => 'العنوان مطلوب',
            'address.max' => 'العنوان يجب ألا يتجاوز 500 حرف',
            'latitude.between' => 'خط العرض يجب أن يكون بين -90 و 90',
            'longitude.between' => 'خط الطول يجب أن يكون بين -180 و 180',
        ];
    }
}
