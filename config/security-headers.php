<?php

return [
    'enabled' => (bool) env('SECURITY_HEADERS_ENABLED', true),

    'headers' => [
        'X-Content-Type-Options' => 'nosniff',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'Permissions-Policy' => 'accelerometer=(), autoplay=(), camera=(), geolocation=(), gyroscope=(), magnetometer=(), microphone=(), payment=(), usb=()',
        'X-Frame-Options' => 'DENY',
    ],

    'csp' => [
        'report_only' => (bool) env('CSP_REPORT_ONLY', false),

        'directives' => [
            'default-src' => ["'self'"],
            'base-uri' => ["'self'"],
            'connect-src' => ["'self'"],
            'font-src' => ["'self'", 'data:'],
            'form-action' => ["'self'"],
            'frame-ancestors' => ["'none'"],
            'img-src' => ["'self'", 'data:', 'blob:'],
            'object-src' => ["'none'"],
            'script-src' => ["'self'"],
            // React uses style attributes for some dynamic UI elements.
            'style-src' => ["'self'", "'unsafe-inline'"],
        ],

        // Filament/Livewire currently require inline/evaluated scripts. Keep this
        // exception scoped to admin framework endpoints instead of the whole site.
        'admin_directives' => [
            'script-src' => ["'self'", "'unsafe-inline'", "'unsafe-eval'"],
        ],
    ],

    'hsts' => [
        'enabled' => (bool) env('HSTS_ENABLED', env('APP_ENV', 'production') === 'production'),
        'value' => env('HSTS_VALUE', 'max-age=31536000; includeSubDomains'),
    ],
];
