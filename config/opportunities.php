<?php

return [
    'weekly_digest_email' => env('WEEKLY_DIGEST_EMAIL', env('ADMIN_EMAIL')),
    'frontend_url' => env('FRONTEND_URL', env('APP_URL', 'http://localhost')),
];
