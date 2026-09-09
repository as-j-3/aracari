<?php

namespace App\Http\Requests\Web\Passenger;

use Illuminate\Foundation\Http\FormRequest;
use App\Constants\CountryCodes;

class UpdatePassengerRequest extends FormRequest
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
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name' => 'required|string|max:255',
            'gender_id' => 'nullable|exists:genders,id',
            'birthday' => 'required|date|before:today',
            'phone_number' => 'required|string|regex:/^\+[0-9]{7,15}$/',
            'country_code' => [
                'required_with:phone_number',
                'string',
                'max:5',
                function ($attr, $value, $fail) {
                    if ($value && !array_key_exists($value, CountryCodes::all())) {
                        $fail('رمز الدولة غير صالح.');
                    }
                },
            ],
            'personal_card_id' => 'nullable|string|max:50',
            'personal_country_place_of_issue' => 'nullable|integer|exists:countries,id',
            'personal_city_place_of_issue' => 'nullable|integer|exists:cities,id',
            'personal_card_date_issu' => 'nullable|date|before_or_equal:today',
            'personal_card_date_expiry' => 'nullable|date|after:personal_card_date_issu',
            'personal_card_id_photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'passport_number' => 'nullable|string|max:50',
            'passport_country_place_of_issue' => 'nullable|integer|exists:countries,id',
            'passport_city_place_of_issue' => 'nullable|integer|exists:cities,id',
            'passport_date_issu' => 'nullable|date|before_or_equal:today',
            'passport_date_expiry' => 'nullable|date|after:passport_date_issu',
            'passport_photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $pairs = [
                ['personal_country_place_of_issue', 'personal_city_place_of_issue'],
                ['passport_country_place_of_issue', 'passport_city_place_of_issue'],
            ];

            foreach ($pairs as [$countryField, $cityField]) {
                $countryId = $this->input($countryField);
                $cityId = $this->input($cityField);

                if ($countryId && $cityId) {
                    $city = \App\Models\City::find($cityId);
                    if (! $city || (int) $city->country_id !== (int) $countryId) {
                        $validator->errors()->add($cityField, 'المدينة المحددة لا تنتمي إلى الدولة المختارة');
                    }
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'full_name.required' => 'اسم المسافر مطلوب',
            'full_name.max' => 'اسم المسافر يجب ألا يزيد عن 255 حرف',
            'gender_id.exists' => 'الجنس المحدد غير موجود',
            'birthday.required' => 'تاريخ الميلاد مطلوب',
            'birthday.before' => 'تاريخ الميلاد يجب أن يكون قبل اليوم',
            'phone_number.regex' => 'رقم الهاتف يجب أن يحتوي على أرقام فقط (7-15 رقم)',
            'phone_number.required_without' => 'رقم الهاتف مطلوب',
            'country_code.required_with' => 'رمز الدولة مطلوب عند إدخال رقم الهاتف',
            'personal_card_id.max' => 'رقم البطاقة الشخصية يجب ألا يزيد عن 50 حرف',
            'personal_country_place_of_issue.exists' => 'دولة إصدار البطاقة المحددة غير موجودة',
            'personal_city_place_of_issue.exists' => 'مدينة إصدار البطاقة المحددة غير موجودة',
            'personal_card_date_issu.before_or_equal' => 'تاريخ إصدار البطاقة يجب أن يكون قبل أو يساوي اليوم',
            'personal_card_date_expiry.after' => 'تاريخ انتهاء البطاقة يجب أن يكون بعد تاريخ الإصدار',
            'personal_card_id_photo.image' => 'صورة البطاقة الشخصية يجب أن تكون صورة',
            'personal_card_id_photo.mimes' => 'صورة البطاقة الشخصية يجب أن تكون من نوع: jpg, jpeg, png, webp',
            'personal_card_id_photo.max' => 'صورة البطاقة الشخصية يجب ألا تزيد عن 2048 كيلوبايت',
            'passport_number.max' => 'رقم جواز السفر يجب ألا يزيد عن 50 حرف',
            'passport_country_place_of_issue.exists' => 'دولة إصدار الجواز المحددة غير موجودة',
            'passport_city_place_of_issue.exists' => 'مدينة إصدار الجواز المحددة غير موجودة',
            'passport_date_issu.before_or_equal' => 'تاريخ إصدار جواز السفر يجب أن يكون قبل أو يساوي اليوم',
            'passport_date_expiry.after' => 'تاريخ انتهاء جواز السفر يجب أن يكون بعد تاريخ الإصدار',
            'passport_photo.image' => 'صورة جواز السفر يجب أن تكون صورة',
            'passport_photo.mimes' => 'صورة جواز السفر يجب أن تكون من نوع: jpg, jpeg, png, webp',
            'passport_photo.max' => 'صورة جواز السفر يجب ألا تزيد عن 2048 كيلوبايت',
        ];
    }
}