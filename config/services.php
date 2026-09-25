<?php

return [
    'bundesagentur' => [
        'enabled' => (bool) env('BUNDESAGENTUR_API_ENABLED', false),
        'base_url' => env('BUNDESAGENTUR_API_BASE_URL', 'https://rest.arbeitsagentur.de'),
        'api_key' => env('BUNDESAGENTUR_API_KEY', 'jobboerse-jobsuche'),
        'page_size' => (int) env('BUNDESAGENTUR_API_PAGE_SIZE', 25),
        'max_pages' => (int) env('BUNDESAGENTUR_API_MAX_PAGES', 3),
        'published_within_days' => (int) env('BUNDESAGENTUR_API_PUBLISHED_WITHIN_DAYS', 14),
        'radius_km' => (int) env('BUNDESAGENTUR_API_RADIUS_KM', 200),
        'delay_ms' => (int) env('BUNDESAGENTUR_API_DELAY_MS', 250),
        'category_slug' => env('BUNDESAGENTUR_CATEGORY_SLUG', 'ausbildung'),
    ],
];
