<?php

/*
|--------------------------------------------------------------------------
| Notifications Configuration (welfare/notifications package)
|--------------------------------------------------------------------------
|
| This is the template config the package ships. The host app's own
| config/welfare-notifications.php (published via the command below) takes
| precedence for any key it defines. Deliberately namespaced (not just
| "notifications") — a host app may already own a plain
| config/notifications.php for unrelated settings, and Laravel's
| mergeConfigFrom() only shallow-merges, so a generic key name risks a
| silent collision (this happened wiring the package into welfareMFB).
|
|   php artisan vendor:publish --tag=notifications-config
|
| Swap providers by changing SMS_PROVIDER / PUSH_PROVIDER — no code
| changes required in the host app.
|
*/

return [
    'sms' => [
        // termii | africastalking | twilio
        'driver' => env('SMS_PROVIDER', 'termii'),

        'termii' => [
            'api_key' => env('TERMII_API_KEY', ''),
            'sender_id' => env('TERMII_SENDER_NAME', ''),
            'endpoint' => env('TERMII_BASE_URL', 'https://api.ng.termii.com'),
            'channel' => env('TERMII_CHANNEL', 'dnd'),
        ],

        'africastalking' => [
            'api_key' => env('AFRICASTALKING_API_KEY', ''),
            'username' => env('AFRICASTALKING_USERNAME', ''),
            'sender_id' => env('AFRICASTALKING_SENDER_ID', ''),
            // Use the sandbox endpoint while testing without live credit.
            'sandbox' => env('AFRICASTALKING_SANDBOX', false),
        ],

        'twilio' => [
            'account_sid' => env('TWILIO_ACCOUNT_SID', ''),
            'auth_token' => env('TWILIO_AUTH_TOKEN', ''),
            'from_number' => env('TWILIO_FROM_NUMBER', ''),
        ],
    ],

    'push' => [
        // firebase (only provider today; more can be added under Push/Drivers)
        'driver' => env('PUSH_PROVIDER', 'firebase'),

        'firebase' => [
            // Service-account JSON key from Firebase Console -> Project
            // Settings -> Service Accounts. Never commit the actual file.
            'credentials' => env('FIREBASE_CREDENTIALS', storage_path('app/firebase-credentials.json')),
        ],
    ],
];
