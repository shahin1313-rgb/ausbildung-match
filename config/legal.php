<?php

return [
    'terms_version' => env('LEGAL_TERMS_VERSION', '2026-09-18'),
    'privacy_version' => env('LEGAL_PRIVACY_VERSION', '2026-09-18'),
    'resume_retention_days' => (int) env('LEGAL_RESUME_RETENTION_DAYS', 180),

    'provider' => [
        'name' => env('LEGAL_PROVIDER_NAME'),
        'legal_form' => env('LEGAL_PROVIDER_LEGAL_FORM'),
        'representative' => env('LEGAL_PROVIDER_REPRESENTATIVE'),
        'street_address' => env('LEGAL_PROVIDER_STREET_ADDRESS'),
        'postal_code' => env('LEGAL_PROVIDER_POSTAL_CODE'),
        'city' => env('LEGAL_PROVIDER_CITY'),
        'country' => env('LEGAL_PROVIDER_COUNTRY', 'Deutschland'),
        'email' => env('LEGAL_PROVIDER_EMAIL'),
        'phone' => env('LEGAL_PROVIDER_PHONE'),
        'register_name' => env('LEGAL_PROVIDER_REGISTER_NAME'),
        'register_number' => env('LEGAL_PROVIDER_REGISTER_NUMBER'),
        'vat_id' => env('LEGAL_PROVIDER_VAT_ID'),
        'supervisory_authority' => env('LEGAL_PROVIDER_SUPERVISORY_AUTHORITY'),
        'responsible_content' => env('LEGAL_PROVIDER_RESPONSIBLE_CONTENT'),
        'data_protection_email' => env('LEGAL_DATA_PROTECTION_EMAIL', env('LEGAL_PROVIDER_EMAIL')),
    ],
];
