<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Http;

class Recaptcha implements Rule
{
    public function passes($attribute, $value)
    {
        $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => config('services.recaptcha.secret_key'),
            'response' => $value,
            'remoteip' => request()->ip(),
        ]);
            \Log::info('Recaptcha verification', [
        'success' => $response->json('success'),
        'response' => $response->json(),
    ]);
        return $response->json('success');
    }

    public function message()
    {
        return 'يرجى التحقق من أنك لست روبوت.';
    }
}