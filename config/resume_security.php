<?php

return [
    // Production fails closed when the scanner is unavailable or returns an error.
    'malware_scan' => (bool) env('RESUME_MALWARE_SCAN', env('APP_ENV') === 'production'),
    'scanner_binary' => env('RESUME_SCANNER_BINARY', 'clamdscan'),
    'scanner_timeout' => max(5, (int) env('RESUME_SCANNER_TIMEOUT', 30)),
];
