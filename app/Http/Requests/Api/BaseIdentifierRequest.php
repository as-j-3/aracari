<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\User;
use Illuminate\Validation\ValidationException;

abstract class BaseIdentifierRequest extends FormRequest
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
    protected function findUser(): ?User
    {
        if ($this->uuid) {
            return User::where('uuid', $this->uuid)->first();
        } elseif ($this->phone_number) {
            $phone = $this->phone_number;
            if (!str_starts_with($phone, '+')) {
                $phone = '+' . $phone;
            }
            return User::where('phone_number', $phone)->first();
        } elseif ($this->email) {
            return User::where('email', $this->email)->first();
        }
        
        return null;
    }

    protected function validateUserExists(): void
    {
        $user = $this->findUser();
        
        if (!$user) {
            throw ValidationException::withMessages([
                'identifier' => 'بيانات الاعتماد غير صحيحة'
            ]);
        }
    }

    protected function validateSingleIdentifier(): void
    {
        $identifiers = 0;
        if ($this->uuid) $identifiers++;
        if ($this->phone_number) $identifiers++;
        if ($this->email) $identifiers++;
        
        if ($identifiers > 1) {
            throw ValidationException::withMessages([
                'identifier' => 'يجب على الاقل ادخال قيمه واحده'
            ]);
        }
    }

    protected function validateAtLeastOneIdentifier(): void
    {
        if (!$this->uuid && !$this->phone_number && !$this->email) {
            throw ValidationException::withMessages([
                'identifier' => 'يجب على الاقل ادخال قيمه واحده'
            ]);
        }
    }

    public function validated($key = null, $default = null): array
    {
        $data = parent::validated($key, $default);
        $user = $this->findUser();
        $data['user'] = $user;
        
        return $data;
    }
}
