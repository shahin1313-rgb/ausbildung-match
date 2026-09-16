<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Demo data
    |--------------------------------------------------------------------------
    |
    | Demo records are useful locally and in the automated test suite, but they
    | must never be created in production. Production is denied in code as a
    | second line of defence, even if this flag is accidentally enabled.
    |
    */
    'demo_enabled' => env('SEED_DEMO_DATA', env('APP_ENV', 'production') === 'local'),

    /*
    | Creating an administrator is an explicit deployment action. Keep this
    | disabled by default and provide non-placeholder credentials when enabled.
    */
    'admin_enabled' => env('SEED_ADMIN_USER', false),
    'admin_name' => env('ADMIN_NAME', 'مدیر سایت'),
    'admin_email' => env('ADMIN_EMAIL'),
    'admin_password' => env('ADMIN_PASSWORD'),
];
