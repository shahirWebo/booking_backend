<?php

return [
    'vendor_kyc' => [
        'disk' => 'upload_quarantine',
        'max_size_bytes' => 10 * 1024 * 1024,
        'accepted_mime_types' => [
            'application/pdf',
            'image/jpeg',
            'image/png',
        ],
    ],

    'scanner_provider' => env('FILE_SCANNER_PROVIDER', 'unavailable'),
];
