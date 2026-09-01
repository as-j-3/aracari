<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // SMS Service Configuration
    'sms' => [
        'provider' => env('SMS_PROVIDER', 'twilio'), // twilio, vonage, custom
        'api_key' => env('SMS_API_KEY'),
        'api_secret' => env('SMS_API_SECRET'),
        'sender' => env('SMS_SENDER', 'Aracari'),
        
        // Twilio Configuration
        'twilio' => [
            'sid' => env('TWILIO_SID'),
            'token' => env('TWILIO_TOKEN'),
            'from' => env('TWILIO_FROM'),
        ],
        
        // Vonage Configuration
        'vonage' => [
            'api_key' => env('VONAGE_API_KEY'),
            'api_secret' => env('VONAGE_API_SECRET'),
            'from' => env('VONAGE_FROM'),
        ],
    ],

    // Firebase Configuration
        'firebase' => [
            'credentials_path' => env('FIREBASE_CREDENTIALS_PATH', 'firebase/aracari-app-firebase-service-account.json'),
            'project_id' => env('FIREBASE_PROJECT_ID', 'aracari-app'),
        ],

        // Twilio Configuration
        'twilio' => [
            'sid' => env('TWILIO_SID'),
            'auth_token' => env('TWILIO_AUTH_TOKEN'),
            'phone_number' => env('TWILIO_PHONE_NUMBER'),
        ],


        // reCAPTCHA Configuration
        'recaptcha' => [
            'site_key' => env('RECAPTCHA_SITE_KEY'),
            'secret_key' => env('RECAPTCHA_SECRET_KEY'),
        ],
];
